<?php
/*
PHPSteam: Steam API for PHP.
Copyright (C) 2009 Alican Çubukçuoglu

This file is part of PHPSteam.

PHP Steam is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

The Rinner is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with The Rinner.  If not, see <http://www.gnu.org/licenses/>.
*/

/*--------------+
|    Credits    |
+---------------+
Alican "Shaman" Cubukcuoglu		General coding
Valve Corporation			Original template and graphics
*/

/*-----------------+
|    Change Log    |
+------------------+
1.0.0
-First release.
*/

/*-------------------------+
|    PHPSteam Inclusion    |
+-------------------------*/
define('PHPSTEAM_XMLADDRESSCACHETIME', 0);
define('PHPSTEAM_XMLDATACACHETIME', 0);
require_once('../PHPSteam.php');

/*----------------+
|    Constants    |
+----------------*/
//General
define('PHPSTEAMVIEW_VERSION', '1.0.0');

/*--------------------+
|    CPHPSteamView    |
+--------------------*/
abstract class CPHPSteamView
	{
	public static function PrintHTMLCredits($full= true)
		{
		$copyright= 'PHPSteamView '.PHPSTEAMVIEW_VERSION.' &copy; 2009 Alican Cubukcuoglu';
		
		$credits= 'Alican \"Shaman\" Cubukcuoglu\n\tGeneral coding\n\nValve Corporation\n\tOriginal template and graphics';
		
		echo $copyright;
		if($full)
			echo ' - <a href=\'javascript:void(0);\' onclick=\'javascript:if(confirm("'.$copyright.'\nLicensed under GPL3\n\n'.$credits.'\n\nVisit web page?")) window.open("http://cs.rin.ru/forum/viewtopic.php?f=10&t=51235", "_blank");\'>Credits</a>';
		}
	}

if(!empty($_GET['SteamID64']))
	$user= new CSteamUser($_GET['SteamID64']);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title>
			<?php if($user instanceof CSteamUser && $user->Valid): ?>
			PHPSteamView <?=PHPSTEAMVIEW_VERSION;?> :: ID :: <?=$user->GetFriendsName();?>
			<?php else: ?>
			PHPSteamView <?=PHPSTEAMVIEW_VERSION;?>
			<?php endif; ?>
		</title>
		<link rel='shortcut icon' type='image/x-icon' href='favicon.ico'>
		<link rel='stylesheet' type='text/css' href='css/global.css'>
		<link rel='stylesheet' type='text/css' href='css/profile.css'>
		<link rel='stylesheet' type='text/css' href='css/header.css'>
	</head>
	<body>
		<div id='headerBar'>
			<div id='subHeader'>
				<div class='subHeaderMargin'>
					<form method='GET' action='<?=$_SERVER['PHP_SELF'];?>'>
						<input name='SteamID64' type='text' value='<?=(!empty($_GET['SteamID64']))?$_GET['SteamID64']:'Enter a SteamID64 to view...';?>' onfocus='if(this.value=="Enter a SteamID64 to view...") this.value="";' size='40'> &nbsp; &nbsp; <input type='submit' value='View'>
					</form>
				</div>
			</div>
		</div>
		<?php if($user instanceof CSteamUser && $user->Valid): ?>
		<div align='center'>
			<div id='mainBody'>
				<div id='mainContents' class='clearfix'>
					<div id='rightContents'>
						
						<!-- Online Status -->
						<div id='OnlineStatus'>
							<div id='inCommon'>
								<?php if($user->IsInGame()): ?>
								<?php $InGameInfo= $user->InGameInfo; ?>
								<div id='currentlyPlayingIcon'>
									<div class='iconHolder_in-game'>
										<div class='avatarIcon'>
											<a href='<?=$InGameInfo->Link;?>'>
												<img src='<?=$InGameInfo->Icon;?>'>
											</a>
										</div>
									</div>
								</div>
								<img src='images/status_in-game.gif' width='120' height='14' border='0'>
								<br>
								<p id='statusInGameText'><?=$InGameInfo->Name;?></p>
								<br clear='left'>
								<?php elseif($user->IsOnline()): ?>
								<div id='OnlineStatus'>
									<div id='inCommon'>
										<div id='statusOnlineText'>
											<img src='images/status_online.gif' border='0' height='14' width='102'>
										</div>
										<br>
									</div>
								</div>
								<?php else: ?>
								<p id='statusOfflineText'>Last Online: 1 hrs, 47 mins ago</p>
								<?php endif; ?>
							</div>
						</div>
						
						<!-- Actions -->
						<div class='rightSectionHeader'>Actions</div>
						<div id='rightActionBlockHeader'><img src='images/trans.gif' width='254' height='8' border='0'></div>
						<div id='rightActionBlock'>
							<?php if(!empty($user->InGameInfo)): ?>
							<?php $InGameInfo= $user->InGameInfo; ?>
							<div class='actionItem'>
								<div class='actionItemIcon'>
									<a href='steam://friends/joingame/<?=$user->SteamID64;?>'>
										<img src='images/iconJoinGame.gif' border='0' height='16' width='16'>
									</a>
								</div>
								<a class='linkActionInGame' href='steam://friends/joingame/<?=$user->SteamID64;?>'>Join game in progress</a>
							</div>
							<?php endif; ?>
							<div class='actionItem'>
								<div class='actionItemIcon'>
									<a href='steam://friends/message/<?=$user->SteamID64;?>'>
										<img src='images/iconAddFriend.gif' width='16' height='16' border='0'>
									</a>
								</div>
								<a class='linkAction steamLink' href='steam://friends/add/<?=$user->SteamID64;?>'>Add to your friends list</a>
							</div>
							<div class='actionItem'>
								<div class='actionItemIcon'>
									<a href='steam://friends/message/<?=$user->SteamID64;?>'>
										<img src='images/iconChat.gif' width='16' height='16' border='0'>
									</a>
								</div>
								<a class='linkAction steamLink' href='steam://friends/message/<?=$user->SteamID64;?>'>Send a message</a>
							</div>
						</div>
						<div id='rightActionBlockFooter'><img src='images/trans.gif' width='254' height='8' border='0'></div>
						
						<!-- Stats (Public Only) -->
						<?php if($user->PrivacyState=='public'): ?>
						<div class='rightSectionHeader'>Gameplay Stats</div>
						<div id='rightStatsBlockHeader'><img src='images/trans.gif' width='254' height='8' border='0'></div>
						<div id='rightStatsBlock'>
							<div class='statsItem'>
								<div class='statsItemName'>Member since:</div>
								<?=$user->MemberSince;?>
							</div>
							<div class='rightGreyHR'><img src='images/trans.gif' width='254' height='1' border='0'></div>
							<div class='statsItem'>
								<div class='statsItemName'>Steam Rating:</div>
								<?=$user->SteamRating;?>
							</div>
							<div class='rightGreyHR'><img src='images/trans.gif' width='254' height='1' border='0'></div>
							<div class='statsItem'>
								<div class='statsItemName'>Playing time:</div>
								<?=$user->HoursPlayed;?>
							</div>
							<?php if($user->VACBanned): ?>
							<div class='rightGreyHR'><img src='images/trans.gif' width='254' height='1' border='0'></div>
							<div class='statsItem'>
								<div class='statsItemName'>VAC status:</div>
								<span class="vacText">ban(s) on record</span>
								<span class="infoBreak">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
								<a class="linkStandard" href="http://steamcommunity.com/actions/WhatIsVAC">?</a>
							</div>
							<?php endif; ?>
							<?php if($user->Games): ?>
							<div class='rightGreyHR'><img src='images/trans.gif' width='254' height='1' border='0'></div>
							<?php foreach($user->Games as $Index=>$Game): ?>
							<?php if($Index!=0): ?>
							<div class='rightGreyHR'><img src='images/trans.gif' width='254' height='1' border='0'></div>
							<?php endif; ?>
							<div class='mostPlayedBlock'>
								<div class='mostPlayedBlockIcon'>
									<div class='iconHolder_default'>
										<div class='avatarIcon'>
											<a href='<?=$Game->Link;?>'>
												<img src='<?=$Game->Icon;?>'>
											</a>
										</div>
									</div>
								</div>
								<?=$Game->Name?>
								<br>
								<span class='mostPlayedSmallText'><?=$Game->HoursPlayed;?> hrs</span>
								<?php if(!empty($Game->StatsName)): ?>
								<br>
								<a href='http://steamcommunity.com/profiles/<?=$user->SteamID64?>/stats/<?=$Game->StatsName;?>'>View stats</a>
								<?php endif; ?>
							</div>
							<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<div id='rightStatsBlockFooter'><img src='images/trans.gif' width='254' height='8' border='0'></div>
						<?php endif; ?>
						
						<!-- Friends (Public Only) -->
						<?php if($user->PrivacyState=='public' && $user->Friends): ?>
						<div class='rightSectionHeader'>Friends</div>
						<div id='friendBlocks'>
							<?php foreach($user->Friends as $Friend): ?>
							<div class='friendBlock_<?=$Friend->OnlineState;?>' onclick='window.location="<?=$Friend->Link;?>";'>
								<div class='friendBlockIcon'>
									<div class='iconHolder_<?=$Friend->OnlineState;?>'>
										<div class='avatarIcon'>
											<a href='<?=$Friend->Link;?>'>
												<img src='<?=$Friend->AvatarSmall;?>'>
											</a>
										</div>
									</div>
								</div>
								<p>
									<a class='linkFriend_<?=$Friend->OnlineState;?>' href='<?=$Friend->Link;?>'>
										<?=$Friend->FriendsName;?>
									</a>
									<br>
									<span class='friendSmallText'>
										<span class='linkFriend_<?=$Friend->OnlineState;?>'>
											<?=$Friend->StateMessage;?>
										</span>
									</span>
								</p>
							</div>
							<?php endforeach; ?>
							<p class='rightText'><a class='linkStandard' href='http://steamcommunity.com/profiles/<?=$user->SteamID64;?>/friends'>View all friends</a></p>
						</div>
						<?php endif; ?>
					</div>
					
					<h2>Steam Profile</h2>
					<h1><?=$user->FriendsName;?> (<?=CPHPSteam::GetSteamID($user->SteamID64);?>)</h1>
					
					<!-- Profile -->
					<div class='mainSectionHeader'>Profile</div>
					<div id='profileBlock' class='clearfix'>
						<div id='profileAvatar'>
							<div class='avatarHolder_default'>
								<div class='avatarFull'>
									<img src='<?=$user->AvatarLarge;?>'>
								</div>
							</div>
						</div>
						<?php if($user->PrivacyState=='friendsonly'): ?>
						<p class='errorPrivate'>This profile is only viewable by <?=$user->FriendsName;?>'s friends.</p>
						<?php elseif($user->PrivacyState=='private'): ?>
						<p class='errorPrivate'>This profile is private and can't be viewed by anyone.</p>
						<?php else: ?>
						<h1><?=$user->Headline;?></h1>
						<?php if($user->RealName): ?>
						<h2><?=$user->RealName;?></h2>
						<?php endif; ?>
						<?php if($user->Location): ?>
						<h2><?=$user->Location;?></h2>
						<?php endif; ?>
						<?php if($user->Summary): ?>
						<p class='sectionText'><?=$user->Summary;?></p>
						<?php else: ?>
						<p class='sectionText'>No information given</p>
						<?php endif; ?>
						<?php if($user->WebLinks) foreach($user->WebLinks as $WebLink): ?>
						<a class='linkStandard externalLink' href='<?=$WebLink->Link;?>' target='_blank'>
							<?=$WebLink->Name;?>
							<img src='images/iconExternalLink.gif' width='8' height='8' border='0'>
						</a>
						<br>
						<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<br clear='left'>
					
					<!-- Groups -->
					<?php if($user->Groups): ?>
					<div class='mainSectionHeader'>Groups</div>
					<?php if($user->PrimaryGroupIndex!==false && $Group= $user->GetGroups($user->PrimaryGroupIndex)): ?>
					<div id='primaryGroupBlock'>
						<div id='primaryGroupAvatar'>
							<div class='avatarHolder_default'>
								<div class='avatarFull'>
									<a href='http://steamcommunity.com/groups/<?=$Group->Link;?>'>
										<img src='<?=$Group->AvatarLarge;?>' border='0'>
									</a>
								</div>
							</div>
						</div>
						<a class='linkTitle' href='<?=$Group->Link;?>'><?=$Group->Name;?></a>
						<div class='memberRow'>
							<a class='linkStandard' href='<?=$Group->Link;?>members'><?=$Group->Members;?> Members</a>
							<span class='infoBreak'>&nbsp;&nbsp;|&nbsp;&nbsp;</span>
							<span class='membersInGame'><?=$Group->MembersInGame;?> In-Game</span>
							<span class='infoBreak'>&nbsp;&nbsp;|&nbsp;&nbsp;</span>
							<span class='membersOnline'><?=$Group->MembersOnline;?> Online</span>
							<span class='infoBreak'>&nbsp;&nbsp;|&nbsp;&nbsp;</span>
							<a class='linkStandard steamLink' href='steam://friends/joinchat/<?=$Group->GroupID64;?>'><?=$Group->MembersInChat;?> In Group Chat</a>
						</div>
						<h1><?=$Group->Headline;?></h1>
						<p class='sectionText'><?=$Group->Summary;?></p>
						<a class='linkStandard' href='<?=$Group->Link;?>'>Visit <?=$Group->Name;?>'s profile</a>
						<br>
					</div>
					<br clear='left'>
					<?php endif; ?>
					<?php foreach($user->Groups as $Index=>$Group): ?>
					<?php if($Index!=$user->PrimaryGroupIndex): ?>
					<div class='groupBlock'>
						<p>
							<div class='groupBlockMedium'>
								<div class='mediumHolder_default'>
									<div class='avatarMedium'>
										<a href='<?=$Group->Link;?>'>
											<img src='<?=$Group->AvatarMedium;?>'>
										</a>
									</div>
								</div>
							</div>
							<a class='linkTitle' href='<?=$Group->Link;?>'><?=$Group->Name;?></a>
							<div class='memberRow'>
								<a class='linkStandard' href='<?=$Group->Link;?>members'><?=$Group->Members;?> Members</a>
								<span class='infoBreak'>&nbsp;&nbsp;|&nbsp;&nbsp;</span>
								<span class='membersInGame'><?=$Group->MembersInGame;?> In-Game</span>
								<span class='infoBreak'>&nbsp;&nbsp;|&nbsp;&nbsp;</span>
								<span class='membersOnline'><?=$Group->MembersOnline;?> Online</span>
								<span class='infoBreak'>&nbsp;&nbsp;|&nbsp;&nbsp;</span>
								<a class='linkStandard steamLink' href='steam://friends/joinchat/<?=$Group->GroupID64;?>'><?=$Group->MembersInChat;?> In Group Chat</a>
							</div>
						</p>
					</div>
					<?php endif; ?>
					<?php endforeach; ?>
					<p class='indentText' id='moreGroupsLink'>
						<a class='linkStandard' href='http://steamcommunity.com/profiles/<?=$user->SteamID64;?>/groups'>View all groups</a>
					</p>
					<?php endif; ?>
					<br clear='left'>
					<br clear='left'>
					<br clear='all'>
				</div>
			</div>
			<div id='footer'>
				<div id='footerText'>
					<?php CPHPSteamView::PrintHTMLCredits(); ?>
					<br><?php CPHPSteam::PrintHTMLCredits(); ?>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</body>
</html>