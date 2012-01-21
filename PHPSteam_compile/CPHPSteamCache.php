<?php
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
?>