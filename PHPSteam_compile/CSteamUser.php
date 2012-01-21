<?php
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
?>