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
?>