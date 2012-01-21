<?php
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
?>