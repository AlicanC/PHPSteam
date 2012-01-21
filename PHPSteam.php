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
RBPFC1							Original CSteamGDS and CSteamCS classes
steamCooker						cdrTool
*/

/*-----------------+
|    Change Log    |
+------------------+
1.5.0
-Removed some useless code.
-Removed "AvatarIcons" property alias. Use "Avatars" instead.
-"CSteamUser::Groups" property now returns an array of "CSteamUserGroup"s.
-"CSteamUser::Friends" property now returns an array of "CSteamUserFriend"s.
-"CSteamUser::InGameInfo" property now returns "CSteamUserInGameInfo".
-"CSteamUser::MostPlayedGames" property now returns an array of "CSteamUserMostPlayedGame"s.
-"CSteamUser::WebLinks" property now returns an array of "CSteamUserWebLink"s.
-Added "CSteamUser::IsOnline()" and "CSteamUser::IsInGame()" methods. Remember: unlike "$user->OnlineState=='online'", "$user->IsOnline()" also returns true if the user is in a game.
-Original source code now contains multiple files and gets compiled into one file before release.
-Renamed "PSCache" to "CPHPSteamCache".
-Added new abstract class "CSteamMagicComponent".
-Fixed a bug in caching.
-Changed "CSteamUser::GetMergedXMLData()" and "CSteamUser::GetMergedXMLDataArray()" functions' to continue instead of returning false when missing data found.
-Renamed all "Steam64ID"s to "SteamID64". (Typo correction.)
-Sending a HEAD request instead of a GET request to detect profile page redirections. This method should be faster and IS more compatible.
-Added "CPHPSteam::CDR2XML()" and "CPHPSteam::CDR2CSV()" functions. (These functions use steamCooker's "cdrTool".)
1.4.0
-Fixed $XMLAddress not being cached.
-Merged "CSteamTools" and "CCDRTools" into "CPHPSteam".
-Removed "CSteamUser::GetProfileInfoValue()". You can use "$user->PINAME" instead of "$user->GetProfileInfoValue('PINAME')".
-Improved behaviour of the script against unexistant user profile information.
-Added "PrimaryGroupIndex" to "CSteamUser" propreties. ($user->PrimaryGroupIndex, $user->GetPrimaryGroupIndex())
-"$user->XMLAddress" is not available for public access anymore. That's because it might not be available in some times.
-Improved caching in "CSteamUser::__construct()".
-Reorganized caching constants.
-Moved command definitions for "CSteamGDS" and "CSteamCS" in classes as constants.
-Removed "GDS_CLIENT_VERSION" and "CS_CLIENT_VERSION" constants.
-Added support for using "$user->Valid".
-Added support for getting an array value at a specific index by using "Get*" commands like "$user->GetGroups($user->PrimaryGroupIndex)". (That get's the primary group info.)
1.3.0
-Ugh, removed "SteamUniverse" and "SteamAccountType" abstract classes. Couldn't find a decent method to make use of them.
-Implemented RBPFC1's "CSteamGDS" and "CSteamCS" classes.
-Added "CCDRTools" for easier use.
-Overall improvements and fixes.
1.2.0
-Removed deprecated methods.
-Added dynamic "Get*" function support. You can use "CSteamUser::Get[InfoName]()" to get any "string" you want from "UserXML/Profile".
-Added direct access to results of "Get*" functions. (For example "$user->Groups" gives the same result with "$user->GetGroups()".)
-Added "GetGroups', "GetFriends', "GetGames', "GetInGameInfo', "GetFavoriteGame" and "GetWebLinks" for easily getting arrays from complicated XML parsing. (Dump them to see what you can get...)
-Fixed "CSteamTools::GetSteamID()".
-Unexistence of group and friend data is not fatal anymore.
-Removed "USAGE" file from the package. (Example PHP script will be added in the future...)
1.1.0
-Moved "CSU_*" functions in "CSteamUser" to new "CSteamTools" class and added some more little tools.
-Added "SteamUniverse" and "SteamAccountType" abstract classes for future use.
-Removed "Full Headers" functionality.
-Improved speed and functionality of "CSteamTools::GetCustomURL" and renamed it to "GetXMLAddress".
-Removed "PHPSTEAM_STEAMCOMMUNITY" and "PHPSTEAM_CURLFORMAT" constants.
-Renamed "PHPSTEAM_PROFILESFORMAT" constant to "PHPSTEAM_PROFILE".
-Added support for both Direct and Custom URL profiles.
-Fixed values being messed up with public accounts.
1.0.0
-First release.
*/

/*----------------+
|    Constants    |
+----------------*/
//General
define('PHPSTEAM_VERSION', '1.5.0');
define('PHPSTEAM_PROFILE', '/profiles/%s/');								//%s: SteamID64
define('PHPSTEAM_GROUPLINK', 'http://steamcommunity.com/groups/%s/');		//%s: GroupURL
define('PHPSTEAM_FRIENDLINK', 'http://steamcommunity.com/profiles/%s/');	//%s: SteamID64
//Cache
define('PHPSTEAM_USECACHE', true);
define('PHPSTEAM_CACHEPATH', 'cache/%s.cache');
define('PHPSTEAM_XMLADDRESSCACHETIME', 1800);								//(1800) "false" to disable, "0" or negative value to always use the cache. (Remember, cached URL will be invalid if user changes the "Custom URL".)
define('PHPSTEAM_XMLDATACACHETIME', 120);									//(120) "false" to disable, "0" or negative value to always use the cache.
//Other
define('PHPSTEAM_CDRTEMPFILEPATH', './CDRTEMP');
if(strpos(PHP_OS, 'WIN')!==false) define('PHPSTEAM_WINDOWS', true);

/*---------------------------+
|    CSteamMagicComponent    |
+---------------------------*/
abstract class CSteamMagicComponent
	{
	/*--------------+
	|    Members    |
	+--------------*/
	protected $PMData;
	
	/*-------------------+
	|    Construction    |
	+-------------------*/
	public function __construct(array $Data= array())
		{
		$this->PMData= $Data;
		//Yes, that's it. Magic will do the rest ;)
		}
	
	/*-------------+
	|    Magic!    |
	+-------------*/
	public function __get($Member)
		{
		//Aliasing
		if(method_exists($this, '__getAlias'))
			$this->__getAlias($Member);
		
		//Check for a custom value
		if(method_exists($this, '__getCustom'))
			$this->__getCustom($Member, $Value);
		if(isset($Value))
			return $Value;
		
		//Search for value in data
		foreach($this->PMData as $PDName=>$PDValue)
			if(strtolower($Member)==strtolower($PDName))
				return $PDValue;
		
		return false;
		}
	
	public function __call($Method, $Arguments)
		{
		//Aliasing
		if(method_exists($this, '__callAlias'))
			$this->__callAlias($Method);
		
		//Check for a custom value
		if(method_exists($this, '__callCustom'))
			$this->__callCustom($Method, $Value);
		if(isset($Value))
			return $Value;
		
		//"Get*" methods will be redirected to "__get"
		if(strpos($Method, 'Get')===0)
			{
			$Value= $this->__get(substr($Method, 3));
			
			//Array index returning
			if(isset($Arguments[0]) && is_array($Value))
				{
				if(isset($Value[$Arguments[0]]))
					return $Value[$Arguments[0]];
				
				return false;
				}
			
			return $Value;
			}
		
		return false;
		}
	}

/*--------------------+
|    PHPSteamCache    |
+--------------------*/
abstract class CPHPSteamCache
	{
	public static function Load($Name, &$Data, $TimeLimit= false)
		{
		$Path= sprintf(PHPSTEAM_CACHEPATH, $Name);
		if(!file_exists($Path))
			return false;
		
		if($TimeLimit>0 && (time()-CPHPSteamCache::GetTime($Name))>$TimeLimit)
			return false;
		
		if(!$SerializedData= @file_get_contents($Path))
			return false;
		
		$Data= unserialize($SerializedData);
		
		return true;
		}
	
	public static function Save($Name, $Data)
		{
		$Path= sprintf(PHPSTEAM_CACHEPATH, $Name);
		if(!file_exists(dirname($Path)))
			if(!@mkdir(dirname($Path), 0777, true))
				return false;
		
		if(!@file_put_contents($Path, serialize($Data)))
			return false;
		
		return true;
		}
	
	public static function GetTime($Name)
		{
		$Path= sprintf(PHPSTEAM_CACHEPATH, $Name);
		if(!file_exists($Path))
			return false;
		
		clearstatcache();
		
		return filemtime($Path);
		}
	}

/*----------------+
|    CPHPSteam    |
+----------------*/
abstract class CPHPSteam
	{
	public static function PrintHTMLCredits($full= true)
		{
		$copyright= 'PHPSteam '.PHPSTEAM_VERSION.' &copy; 2009 Alican Cubukcuoglu';
		
		$credits= 'Alican \"Shaman\" Cubukcuoglu\n\tGeneral coding\n\nRBPFC1\n\tOriginal CSteamGDS and CSteamCS classes';
		
		echo $copyright;
		if($full)
			echo ' - <a href=\'javascript:void(0);\' onclick=\'javascript:if(confirm("'.$copyright.'\nLicensed under GPL3\n\n'.$credits.'\n\nVisit web page?")) window.open("http://cs.rin.ru/forum/viewtopic.php?f=10&t=51235", "_blank");\'>Credits</a>';
		}
	
	public static function GetSteamID($SteamID64)
		{
		$AccountType= bcmod($SteamID64, '2');
		return 'STEAM_0:'.$AccountType.':'.bcdiv(bcsub(bcsub($SteamID64, $AccountType), '76561197960265728'), '2');
		}
	
	public static function GetCDR(&$CDR, $OldCDRHash= null)
		{
		$CDR= null;
		
		//Connect to GDS
		$GDS= new CSteamGDS();
		if(!$GDS->Connect($GDS->GetServerCount()))
			return false;
		
		//Fetch IPs
		if(!$IPs= $GDS->SendCommand(CSteamGDS::FindAllConfigServerClientIPAddrPorts))
			return false;
		
		//Connect to CS
		$CS= new CSteamCS();
		if(!$CS->Connect($IPs, count($IPs)))
			return false;
		
		//Send hash (or blank hash)
		if(empty($OldCDRHash))
			$OldCDRHash= str_repeat("\x00", 20);
		if(($CDRSize= $CS->SendCommand(CSteamCS::GetCDDBAndFailSafeModes, $OldCDRHash))===false)
			return false;
		
		//Check if the CDR is up to date
		if($CDRSize===0)
			return true;
		
		//Get CDR from the server
		while(($Recieved= $CS->GetContent())!==false)
			$CDR.= $Recieved;
		
		return true;
		}
	
	public static function CheckCDRHash($CDRHash)
		{
		//Connect to GDS
		$GDS= new CSteamGDS();
		if(!$GDS->Connect($GDS->GetServerCount()))
			return false;
		
		//Fetch IPs
		if(!$IPs= $GDS->SendCommand(CSteamGDS::FindAllConfigServerClientIPAddrPorts))
			return false;
		
		//Connect to CS
		$CS= new CSteamCS();
		if(!$CS->Connect($IPs, count($IPs)))
			return false;
		
		//Send hash (or blank hash)
		if($CS->SendCommand(CSteamCS::GetCDDBAndFailSafeModes, $CDRHash)!==0)
			return false;
		
		return true;
		}
	
	public static function CDR2XML($CDR, $Output)
		{
		file_put_contents(PHPSTEAM_CDRTEMPFILEPATH, $CDR);
		
		$Arguments= 'file "'.PHPSTEAM_CDRTEMPFILEPATH.'" xml "'.$Output.'"';
		
		if(PHPSTEAM_WINDOWS)
			exec('cdrTool.exe '.$Arguments);
		else
			exec('cdrTool '.$Arguments);
		}
	
	public static function CDR2CSV($CDR, $Output)
		{
		file_put_contents(PHPSTEAM_CDRTEMPFILEPATH, $CDR);
		
		$Arguments= 'file "'.PHPSTEAM_CDRTEMPFILEPATH.'" csv "'.$Output.'"';
		
		if(PHPSTEAM_WINDOWS)
			exec('cdrTool.exe '.$Arguments);
		else
			exec('cdrTool '.$Arguments);
		}
	}

/*------------------+
|    CSteamUser*    |
+------------------*/
final class CSteamUserGroup extends CSteamMagicComponent
	{
	/*--------------+
	|    Aliases    |
	+--------------*/
	protected function __getAlias(&$Member)
		{
		switch(strtolower($Member))
			{
			//Easier to remember this way...
			case 'avatarsmall':
				{
				$Member= 'avatarIcon';
				break;
				}
			case 'avatarlarge':
				{
				$Member= 'avatarFull';
				break;
				}
			//Simple shortcuts
			case 'name':
				{
				$Member= 'groupName';
				break;
				}
			case 'url':
				{
				$Member= 'groupURL';
				break;
				}
			case 'members':
				{
				$Member= 'memberCount';
				break;
				}
			}
		}
	
	/*---------------------+
	|    Custom Members    |
	+---------------------*/
	protected function __getCustom(&$Member, &$Value)
		{
		switch(strtolower($Member))
			{
			//Avatar array
			case 'avatars':
				{
				$icons= array();
				$icons['small']= $this->__get('avatarIcon');
				$icons['medium']= $this->__get('avatarMedium');
				$icons['large']= $this->__get('avatarFull');
				
				$Value= $icons;
				break;
				}
			//Link
			case 'link':
				{
				$Value= sprintf(PHPSTEAM_GROUPLINK, $this->__get('groupURL'));
				break;
				}
			}
		}
	}

final class CSteamUserFriend extends CSteamMagicComponent
	{
	/*--------------+
	|    Aliases    |
	+--------------*/
	protected function __getAlias(&$Member)
		{
		switch(strtolower($Member))
			{
			//Just a more user-friendly name for it
			case 'friendsname':
			case 'friendsnick':
				{
				$Member= 'steamID'; //That's how Valve puts it in the XML. Weird huh?...
				break;
				}
			//Easier to remember this way...
			case 'avatarsmall':
				{
				$Member= 'avatarIcon';
				break;
				}
			case 'avatarlarge':
				{
				$Member= 'avatarFull';
				break;
				}
			}
		}
	
	/*---------------------+
	|    Custom Members    |
	+---------------------*/
	protected function __getCustom(&$Member, &$Value)
		{
		switch(strtolower($Member))
			{
			//Avatar array
			case 'avatars':
				{
				$icons= array();
				$icons['small']= $this->__get('avatarIcon');
				$icons['medium']= $this->__get('avatarMedium');
				$icons['large']= $this->__get('avatarFull');
				
				$Value= $icons;
				break;
				}
			case 'link':
				{
				$Value= sprintf(PHPSTEAM_FRIENDLINK, $this->__get('steamID64'));
				break;
				}
			}
		}
	}

final class CSteamUserInGameInfo extends CSteamMagicComponent
	{
	/*--------------+
	|    Aliases    |
	+--------------*/
	protected function __getAlias(&$Member)
		{
		switch(strtolower($Member))
			{
			//Shortcuts
			case 'name':
			case 'link':
			case 'icon':
			case 'logo':
			case 'logosmall':
			case 'joinlink':
				{
				$Member= 'Game'.$Member;
				break;
				}
			}
		}
	}

final class CSteamUserMostPlayedGame extends CSteamMagicComponent
	{
	/*--------------+
	|    Aliases    |
	+--------------*/
	protected function __getAlias(&$Member)
		{
		switch(strtolower($Member))
			{
			//Shortcuts
			case 'name':
			case 'link':
			case 'icon':
			case 'logo':
			case 'logosmall':
				{
				$Member= 'Game'.$Member;
				break;
				}
			}
		}
	}

final class CSteamUserWebLink extends CSteamMagicComponent
	{
	/*--------------+
	|    Aliases    |
	+--------------*/
	protected function __getAlias(&$Member)
		{
		switch(strtolower($Member))
			{
			//Shortcuts
			case 'name':
				{
				$Member= 'title';
				break;
				}
			}
		}
	}

/*-----------------+
|    CSteamUser    |
+-----------------*/
class CSteamUser
	{
	/*--------------+
	|    Members    |
	+--------------*/
	private $PMValid;
	
	private $PMSteamID64;			//String (64-bit integer as string)
	private $PMXMLData;			//Array (Parsed XML)
	
	private $PMProfileInfo;			//Array
	private $PMOptionalInfo;		//Array
	
	/*-------------------+
	|    Construction    |
	+-------------------*/
	public function __construct($SteamID64)
		{
		//Get XMLData from cache
		if(PHPSTEAM_USECACHE===true && PHPSTEAM_XMLDATACACHETIME!==false)
			CPHPSteamCache::Load($SteamID64.'_XMLData', $XMLData, PHPSTEAM_XMLDATACACHETIME);
		
		//Get XMLData from server if we couldn't get it from the cache
		if(empty($XMLData))
			{
			//Get XMLAddress from cache
			if(PHPSTEAM_USECACHE===true && PHPSTEAM_XMLADDRESSCACHETIME!==false)
				CPHPSteamCache::Load($SteamID64.'_XMLAddress', $XMLAddress, PHPSTEAM_XMLADDRESSCACHETIME);
			
			//Get XMLAddress from server if we couldn't get it from the cache
			if(empty($XMLAddress) && !$this->GetXMLAddress($SteamID64, $XMLAddress))
				return;
			
			//Finally we can get XMLData from the server
			if(!$this->GetXMLData($XMLAddress, $XMLData))
				return;
			}
		
		//Cache results
		if(PHPSTEAM_USECACHE===true && PHPSTEAM_XMLDATACACHETIME!==false)
			CPHPSteamCache::Save($SteamID64.'_XMLData', $XMLData);
		if(PHPSTEAM_USECACHE===true && PHPSTEAM_XMLADDRESSCACHETIME!==false && !empty($XMLAddress))
			CPHPSteamCache::Save($SteamID64.'_XMLAddress', $XMLAddress);
		
		//Get merged ProfileInfo (This must exist in all situations)
		if(!$this->GetMergedXMLData($XMLData, 'profile/0/value', $ProfileInfo))
			return;
		
		//Compare the given and recived IDs for verification
		if(empty($ProfileInfo['steamID64']) || $SteamID64!=$ProfileInfo['steamID64'])
			return;
		
		//Set required data
		$this->PMSteamID64= $SteamID64;
		$this->PMXMLData= $XMLData;
		$this->PMProfileInfo= $ProfileInfo;
		
		//Get optional data
		$this->GetMergedXMLDataArray($XMLData, 'profile/0/value/groups/0/value/group', $this->PMOptionalInfo['Groups']);				//Group list
		$this->GetMergedXMLDataArray($XMLData, 'profile/0/value/friends/0/value/friend', $this->PMOptionalInfo['Friends']);				//Friend list
		$this->GetMergedXMLData($XMLData, 'profile/0/value/inGameInfo/0/value', $this->PMOptionalInfo['InGameInfo']);					//InGameInfo
		$this->GetMergedXMLDataArray($XMLData, 'profile/0/value/mostPlayedGames/0/value/mostPlayedGame', $this->PMOptionalInfo['MostPlayedGames']);	//MostPlayedGame list
		$this->GetMergedXMLData($XMLData, 'profile/0/value/favoriteGame/0/value', $this->PMOptionalInfo['FavoriteGame']);				//FavoriteGame
		$this->GetMergedXMLDataArray($XMLData, 'profile/0/value/weblinks/0/value/weblink', $this->PMOptionalInfo['WebLinks']);				//WebLink list
		
		//Convert "Groups" array content to "CSteamUserGroup"s
		if($this->PMOptionalInfo['Groups'])
			foreach($this->PMOptionalInfo['Groups'] as $Index=>$Data)
				$this->PMOptionalInfo['Groups'][$Index]= new CSteamUserGroup($Data);
		
		//Convert "Friends" array content to "CSteamUserFriend"s
		if($this->PMOptionalInfo['Friends'])
			foreach($this->PMOptionalInfo['Friends'] as $Index=>$Data)
				$this->PMOptionalInfo['Friends'][$Index]= new CSteamUserFriend($Data);
		
		//Convert "InGameInfo" array content to "CSteamUserInGameInfo"s
		if($this->PMOptionalInfo['InGameInfo'])
			$this->PMOptionalInfo['InGameInfo']= new CSteamUserInGameInfo($this->PMOptionalInfo['InGameInfo']);
		
		//Convert "MostPlayedGames" array content to "CSteamUserMostPlayedGame"s
		if($this->PMOptionalInfo['MostPlayedGames'])
			foreach($this->PMOptionalInfo['MostPlayedGames'] as $Index=>$Data)
				$this->PMOptionalInfo['MostPlayedGames'][$Index]= new CSteamUserMostPlayedGame($Data);
		
		//Convert "WebLinks" array content to "CSteamUserWebLink"s
		if($this->PMOptionalInfo['WebLinks'])
			foreach($this->PMOptionalInfo['WebLinks'] as $Index=>$Data)
				$this->PMOptionalInfo['WebLinks'][$Index]= new CSteamUserWebLink($Data);
		
		//Mark as valid (Otherwise functions will not, uhm, function :))
		$this->PMValid= true;
		}
	
	/*----------------------+
	|    Private Methods    |
	+----------------------*/
	private function GetXMLAddress($SteamID64, &$XMLAddress)
		{
		//Prepare and send the HEAD request
		$Request= 'HEAD '.sprintf(PHPSTEAM_PROFILE, $SteamID64).' HTTP/1.1'."\r\n";
		$Request.= 'Host: steamcommunity.com'."\r\n";
		$Request.= 'Connection: Close'."\r\n";
		$Request.= "\r\n";
		
		fwrite($SocketHandle= fsockopen('steamcommunity.com', 80), $Request);
		
		//Read header from the socket and close it
		$Data= '';
		while(!feof($SocketHandle))
			$Data.= fread($SocketHandle, 1024);
		fclose($SocketHandle);
		
		//Find the real XML address
		$lines= explode("\r\n", $Data);
		if($lines[0]=='HTTP/1.1 200 OK')
			{
			//User doesn't have Custom URL so we don't need to anything
			$XMLAddress= 'http://steamcommunity.com'.sprintf(PHPSTEAM_PROFILE, $SteamID64).'?xml=1';
			
			return true;
			}
			elseif($lines[0]=='HTTP/1.1 302 Found')
			{
			//Get "Location" from the data
			foreach($lines as $line)
				if(strpos($line, 'Location: ')===0)
					{
					list(, $location)= explode(': ', $line, 2);
					break;
					}
			
			//User has Custom URL so we will use this XML address
			$XMLAddress= $location.'?xml=1';
			
			return true;
			}
			else
			{
			//Something wrong happened
			return false;
			}
		}
	
	private function GetXMLData($XMLAddress, &$XMLData)
		{
		//We need XML2Array function to parse the XML
		if(!function_exists('XML2Array'))
			{
			function XML2Array($XMLReader)
				{
				$result= null;
				
				while($XMLReader->read())
					{
					switch($XMLReader->nodeType)
						{
						case XMLReader::ELEMENT:
							{
							$result[$XMLReader->name][]= array('value'=>$XMLReader->isEmptyElement?'':XML2Array($XMLReader));
							if($XMLReader->hasAttributes)
								{
								$element= &$result[$XMLReader->name][count($result[$XMLReader->name])-1];
								while($XMLReader->moveToNextAttribute())
									$element['attributes'][$XMLReader->name]= $XMLReader->value;
								}
							break;
							}
						case XMLReader::TEXT:
						case XMLReader::CDATA:
							{
							$result.= $XMLReader->value;
							break;
							}
						case XMLReader::END_ELEMENT:
							{
							return $result;
							break;
							}
						}
					}
				
				return $result;
				}
			}
		
		//Open the XML file
		$XMLReader= new XMLReader();
		if(!$XMLReader->open($XMLAddress))
			return false;
		
		//Read the XML file
		$XMLData= XML2Array($XMLReader);
		
		//Close the XML file
		$XMLReader->close();
		
		return true;
		}
	
	private function GetXMLDataValue($XMLData, $path, &$value)
		{
		$value= null;
		
		//Explode and reverse
		if(!is_array($path))
			$path= explode('/', $path);
		$path= array_reverse($path);
		
		//Walk through the data
		$current= $XMLData;
		while(count($path)!=0)
			{
			$route= array_pop($path);
			
			if($route=='')
				continue;
			
			if(!isset($current[$route]))
				return false;
			
			$current= $current[$route];
			}
		
		//Set the value
		$value= $current;
		
		return true;
		}
	
	private function GetMergedXMLData($XMLData, $path, &$MergedData, $IgnoreArrays= true)
		{
		$MergedData= null;
		
		//Get the tree to merge
		if(!$this->GetXMLDataValue($XMLData, $path, $XMLTree))
			return false;
		
		//Merge tree contents
		$Merge= array();
		foreach($XMLTree as $Name=>$SubTree)
			{
			//Try to get the value
			if(!$this->GetXMLDataValue($SubTree, '0/value', $Value))
				continue;
			
			//Merge arrays again
			if(!is_array($Value))
				$Merge[$Name]= $Value;
			else if(!$IgnoreArrays)
				$Merge[$Name]= $this->GetMergedXMLData($Value);
			}
		
		//Set the value
		$MergedData= $Merge;
		
		return true;
		}
	
	private function GetMergedXMLDataArray($XMLData, $path, &$MergedDataArray, $IgnoreArrays= true)
		{
		$MergedDataArray= null;
		
		//Get the array tree to merge
		if(!$this->GetXMLDataValue($XMLData, $path, $XMLTree))
			return;
		
		$Merge= array();
		foreach($XMLTree as $Name=>$SubTree)
			{
			if(!$this->GetXMLDataValue($SubTree, 'value', $UnmergedTree))
				continue;
			
			if(!$this->GetMergedXMLData($UnmergedTree, array(), $Value))
				continue;
			
			$Merge[$Name]= $Value;
			}
		
		//Set the value
		$MergedDataArray= $Merge;
		
		return true;
		}
	
	/*-------------+
	|    Magic!    |
	+-------------*/
	public function __get($Member)
		{
		if(!$this->IsValid())
			return false;
		
		//Aliasing
		switch(strtolower($Member))
			{
			//Just a more user-friendly name for it
			case 'friendsname':
			case 'friendsnick':
				{
				$Member= 'steamID'; //That's how Valve puts it in the XML. Weird huh?...
				break;
				}
			//Easier to remember this way...
			case 'avatarsmall':
				{
				$Member= 'avatarIcon';
				break;
				}
			case 'avatarlarge':
				{
				$Member= 'avatarFull';
				break;
				}
			//Simple shortcuts
			case 'games':
				{
				$Member= 'MostPlayedGames';
				break;
				}
			case 'hoursplayed':
				{
				$Member= 'HoursPlayed2Wk';
				break;
				}
			//Common spelling difference
			case 'favouritegame':
				{
				$Member= 'FavoriteGame';
				break;
				}
			}
		
		//Return the value
		switch(strtolower($Member))
			{
			//Primary group index
			case 'primarygroupindex':
				{
				if(empty($this->PMXMLData['profile'][0]['value']['groups'][0]['value']['group']))
					return false;
				
				foreach($this->PMXMLData['profile'][0]['value']['groups'][0]['value']['group'] as $Index=>$Data)
					if(!empty($Data['attributes']['isPrimary']) && $Data['attributes']['isPrimary']=='1')
						return $Index;
				
				return false;
				}
			//Stored data in private members
			case 'steamid64':
			case 'valid':
			case 'profileinfo':
				return $this->{'PM'.$Member};
			//Avatar array
			case 'avatars':
				{
				$icons= array();
				$icons['small']= $this->__get('avatarIcon');
				$icons['medium']= $this->__get('avatarMedium');
				$icons['large']= $this->__get('avatarFull');
				
				return $icons;
				}
			//Search in profile and optional info
			default:
				{
				//Optional info
				foreach($this->PMOptionalInfo as $POIName=>$POIValue)
					if(strtolower($Member)==strtolower($POIName))
						return $POIValue;
				
				//ProfileInfo data
				foreach($this->PMProfileInfo as $PIName=>$PIValue)
					if(strtolower($Member)==strtolower($PIName))
						return $PIValue;
				}
			}
		
		return false;
		}
	
	public function __call($Method, $Arguments)
		{
		if(!$this->IsValid())
			return false;
		
		//"Get*" methods will be redirected to "__get"
		if(strpos($Method, 'Get')===0)
			{
			$Value= $this->__get(substr($Method, 3));
			
			//Array index returning
			if(isset($Arguments[0]) && is_array($Value))
				{
				if(isset($Value[$Arguments[0]]))
					return $Value[$Arguments[0]];
				
				return false;
				}
			
			return $Value;
			}
		
		//"Is*" methods
		if(strpos($Method, 'Is')===0)
			{
			$Query= substr($Method, 2);
			
			switch(strtolower($Query))
				{
				case 'online':
					{
					if(!$OnLineState= $this->__get('onlineState'))
						return false;
					
					return ($OnLineState=='online' || $OnLineState=='in-game');
					}
				case 'ingame':
					{
					if(!$OnLineState= $this->__get('onlineState'))
						return false;
					
					return ($OnLineState=='in-game');
					}
				default:
					return false;
				}
			}
		
		return false;
		}
	
	/*---------------------+
	|    Public Methods    |
	+---------------------*/
	public function IsValid()
		{
		return $this->PMValid;
		}
	
	public function GetValueFromXML($path, &$value)
		{
		if(!$this->IsValid())
			return false;
		
		return $this->GetXMLDataValue($this->XMLData, $path, $value);
		}
	}

/*----------------+
|    CSteamGDS    |
+----------------*/
class CSteamGDS
	{
	/*----------------+
	|    Constants    |
	+----------------*/
	const FindProxyASClientAuthenticationIPAddrPort= "\x00";
	const FindMasterASAdminIPAddrPort= "\x01";
	const FindMasterConfigServerAdminIPAddrPort= "\x02";
	const FindAllConfigServerClientIPAddrPorts= "\x03";
	const FindAllConfigServerServerIPAddrPorts= "\x04";
	const FindAllCSDSContentServerNetIPAddrPorts= "\x05";
	const FindAllCSDSFindContentServersIPAddrPorts= "\x06";
	const FindAllValidateUserIDTicketServerIPAddrPorts= "\x07";
	const FindMasterGlobalTransactionManagerIPAddrPort= "\x08";
	const FindAllSystemStatusIPAddrPorts= "\x09";
	const FindAllRemoteFileHarvestIPAddrPorts= "\x0A";
	const FindAllVCDSValidateNewValveCDKeyNetIPAddrPorts= "\x0B";
	const FindAllMCSContentAdminIPAddrPorts= "\x0C";
	const FindAllMCSMasterPublicContentIPAddrPorts= "\x0D";
	const FindAllMCSMasterClientContentIPAddrPorts= "\x0E";
	const FindAllHLMasterServerIPAddrPorts= "\x0F";
	const FindAllFriendsServerIPAddrPorts= "\x10";
	const FindMasterBillingBridgeServerAdminIPAddrPort= "\x11";
	const FindAllMasterASClientAuthenticationIPAddrPorts= "\x12";
	const FindAllMasterASAdminIPAddrPorts= "\x13";
	const FindAllCSERServerIPAddrPorts= "\x14";
	const FindAllLogProcessingServerIPAddrPorts= "\x15";
	const FindMasterLogProcessingAdminIPAddrPort= "\x16";
	const FindAllCSERAdminIPAddrPorts= "\x17";
	const FindAllHL2MasterServerIPAddrPorts= "\x18";
	const FindMasterASClientAuthenticationIPAddrPort= "\x1A";
	const FindAllVTSAdminIPAddrPorts= "\x1B";
	const FindSlaveASClientAuthenticationIPAddrPort= "\x1C";
	const FindAllBRSIPAddrPort= "\x1D";
	const FindAllRDKFMasterServerIPAddrPorts= "\x1E";
	
	/*--------------+
	|    Members    |
	+--------------*/
	private $GDSServers= array
		(
		//Hardcoded IPs from Steam.exe/SteamUI.dll
		array('IP'=>'207.173.177.11', 'Port'=>27030),
		array('IP'=>'207.173.177.12', 'Port'=>27030),
		array('IP'=>'87.248.169.194', 'Port'=>27038),
		array('IP'=>'72.165.61.189', 'Port'=>27030),
		array('IP'=>'69.28.151.178', 'Port'=>27038),
		array('IP'=>'69.28.153.82', 'Port'=>27038),
		array('IP'=>'68.142.72.250', 'Port'=>27038)
		);
	public $ErrorNo;
	public $Error;
	
	private $SocketHandle;
	
	/*--------------+
	|    Methods    |
	+--------------*/
	public function GetServerCount()
		{
		return count($this->GDSServers);
		}
	
	public function Connect($Retries= 1)
		{		
		//Use Retries as index
		$GDS= $this->GDSServers[$Retries%count($this->GDSServers)];
		
		//Open a socket
		if(!$this->SocketHandle= @fsockopen($GDS['IP'], $GDS['Port'], $this->ErrorNo, $this->Error, 1))
			{
			if($Retries>0)
				return $this->Connect($Retries-1);
			
			return false;
			}
		
		//Check version
		if(!fwrite($this->SocketHandle, "\x00\x00\x00\x02", 4) || fread($this->SocketHandle, 1)!=0x00)
			{
			$this->Close();
			return false;
			}
		
		return true;
		}
	
	public function SendCommand($Command, $Parameters= null)
		{
		if(!$this->SocketHandle)
			return false;
			
		//Calculate packet length
		$PacketLength= strlen($Command)+strlen($Parameters);
		
		//Write packet length
		if(!fwrite($this->SocketHandle, @pack('N', $PacketLength), 4))
			return false;
		
		//Write the command
		if(!fwrite($this->SocketHandle, $Command, 1))
			return false;
			
		//Write parameters
		if(!empty($Parameters) && !fwrite($this->SocketHandle, $Parameters))
			return false;
		
		//Read data
		$PacketLength= @unpack('N', fread($this->SocketHandle, 4));
		if(!isset($PacketLength[1]))
			return false;
		$PacketLength= $PacketLength[1];
		
		$IPAddressCount= @unpack('n', fread($this->SocketHandle, 2));
		if(!isset($IPAddressCount[1]))
			return false;
		$IPAddressCount= $IPAddressCount[1];
		
		//Stack IPs
		$IPs= array();
		$read= 0;
		for($i= 0; $i<$IPAddressCount; $i++)
			{
			if($read>=$PacketLength)
				break;
				
			$IP= @unpack('N', fread($this->SocketHandle, 4));
			if(!isset($IP[1]))
				return false;
			$IP= @long2ip($IP[1]);
			
			$Port= @unpack('v', fread($this->SocketHandle, 2));
			if(!isset($Port[1]))
				return false;
			$Port= $Port[1];
			
			$IPs[]= array('IP'=>$IP, 'Port'=>$Port);
			$read+= 6;
			}
		
		return $IPs;
		}
	
	public function Close()
		{
		if($this->SocketHandle)
			fclose($this->SocketHandle);
			
		$this->SocketHandle= null;
		}
	}

/*---------------+
|    CSteamCS    |
+---------------*/
class CSteamCS
	{
	/*----------------+
	|    Constants    |
	+----------------*/
	const GetClientConfig= "\x01";
	const RequestLatestContentDescriptionDB= "\x02";
	const GetSteamInstanceRSAPublicKey= "\x04";
	const GetCurrentAuthFailSafeMode= "\x05";
	const GetCurrentBillingFailSafeMode= "\x06";
	const GetCurrentContentFailSafeMode= "\x07";
	const GetCurrentSteam3LogonPercent= "\x08";
	const GetCDDBAndFailSafeModes= "\x09";
	
	/*--------------+
	|    Members    |
	+--------------*/
	public $ErrorNo;
	public $Error;
	
	private $SocketHandle;
	
	/*--------------+
	|    Methods    |
	+--------------*/
	public function Connect($IPs, $Retries= 1)
		{
		//Use Retries as index
		$CS= $IPs[$Retries%count($IPs)];
		
		//Open a socket
		if(!$this->SocketHandle= fsockopen($CS['IP'], $CS['Port'], $this->ErrorNo, $this->Error, 1))
			{
			if($Retries>0)
				return $this->Connect($IPs, $Retries-1);
			
			return false;
			}
		
		//Check version
		if(!fwrite($this->SocketHandle, "\x00\x00\x00\x02", 4) || fread($this->SocketHandle, 1)!=0x00)
			{
			$this->Close();
			return false;
			}
		
		return true;
		}
		
	public function SendCommand($Command, $Parameters= '')
		{
		if(!$this->SocketHandle)
			return false;
			
		//Calculate packet length
		$PacketLength= strlen($Command)+strlen($Parameters);
		
		//Write packet length
		if(!fwrite($this->SocketHandle, @pack('N', $PacketLength), 4))
			return false;
		
		//Write the command
		if(!fwrite($this->SocketHandle, $Command, 1))
			return false;
			
		//Write parameters
		if(!empty($Parameters) && !fwrite($this->SocketHandle, $Parameters))
			return false;
		
		//Read
		$UnknownData= fread($this->SocketHandle, 11);
		$DataVariable= @unpack('V', fread($this->SocketHandle, 4));
		if(!isset($DataVariable[1]))
			return false;
		
		return $DataVariable[1]; //DataSize
		}
		
	public function GetContent($ReadSize= 1024)
		{
		if(!$this->SocketHandle || feof($this->SocketHandle))
			return false;
		
		return fread($this->SocketHandle, $ReadSize);
		}
		
	public function Close()
		{
		if($this->SocketHandle)
			fclose($this->SocketHandle);
			
		$this->SocketHandle= null;
		}
	}
?>