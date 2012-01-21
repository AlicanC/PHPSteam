<?php
/*----------------+
|    CSteamGCF    |
+----------------*/
class CSteamGCFHeader extends CSteamMagicComponent{}
class CSteamGCFBlockEntryHeader extends CSteamMagicComponent{}
class CSteamGCFBlockEntry extends CSteamMagicComponent{}
class CSteamGCFFragMapHeader extends CSteamMagicComponent{}
class CSteamGCFFragMap extends CSteamMagicComponent{}
	
class CSteamGCF
	{
	/*--------------+
	|    Members    |
	+--------------*/
	protected $PMValid;
	protected $PMHeader;
	
	/*-------------------+
	|    Construction    |
	+-------------------*/
	public function __construct($Path)
		{
		//Check if file exists
		if(!file_exist($Path))
			return;
		
		//Open the file
		if(!$File= fopen($Path))
			return;
		
		//Read the Header 
		if(!$this->ReadData($File, 44, array('Dummy0', 'Dummy1', 'Dummy2', 'GCFVersion', 'AppID', 'AppVersion', 'Dummy3', 'Dummy4', 'FileSize', 'BlockSize', 'BlockCount', 'Dummy5'), $Data))
			return;
		
		//Create Header instance
		$Header= new CSteamGCFHeader($Data);
		
		//Check if the version is supported
		if($Header->GCFVersion!=1 && $Header->GCFVersion1=6)
			return;
		
		//Version 6 stuff
		if($Header->GCFVersion==6)
			{
			//Read the Header
			if(!$this->ReadData($File, 32, array('BlockCount', 'BlocksUsed', 'Dummy0', 'Dummy1', 'Dummy2', 'Dummy3', 'Dummy4', 'Dummy5', 'Checksum'), $Data))
				return;
			
			//Create BlockEntryHeader instance
			$BlockEntryHeader= new CSteamGCFBlockEntryHeader($Data);
			
			//Validate block count
			if($BlockEntryHeader->BlockCount!=$Header->BlockCount)
				return;
			
			//Get BlockEntries
			$BlockEntries= array();
			for($i= 0; $i<$Header->BlockCount; $i++)
				{
				//Read the Entry
				if(!$this->ReadData($File, 28, array('EntryType', 'FileDataOffset', 'FileDataSize', 'FirstDataBlockIndex', 'NextBlockEntryIndex', 'PreviousBlockEntryIndex', 'DirectoryIndex'), $Data))
					return;
				
				$BlockEntries[]= new CSteamGCFBlockEntry($Data);
				}
			
			//Get Fragmentation Map Header
			if(!$this->ReadData($File, 16, array('BlockCount', 'Dummy0', 'Dummy1', 'Checksum'), $Data))
				return;
			
			//Create FragMapHeader instance
			$FragMapHeader= new CSteamGCFFragMapHeader($Data);
			
			//Validate block count
			if($FragMapHeader->BlockCount!=$Header->BlockCount)
				return;
			
			//Get FragMaps
			$FragMaps= array();
			for($i= 0; $i<$Header->BlockCount; $i++)
				{
				//Read the Entry
				if(!$this->ReadData($File, 4, array('NextDataBlockIndex'), $Data))
					return;
				
				$FragMaps[]= new CSteamGCFFragMap($Data);
				}
			
			}
		
		//Validated!
		$this->Valid= true;
		}
	
	/*----------------------+
	|    Private Methods    |
	+----------------------*/
	private function ReadData($File, $Length, $Type, array $Names, &$Data)
		{
		//Read the header
		if(!$Data= fread($File, $Length))
			return false;
	
		//Unpack data and check count
		if(!$DataArray= unpack('L'.count($Names), $Data) || count($DataArray)!=count($Names)+1)
			return false;
		
		//Build header data
		$Data= array();
		foreach($DataArray as $Index=>$Value)
			{
			if($Index==0 || $Names[$Index-1]===false)
				continue;
			
			$Data[$Names[$Index]]= $Value;
			}
		
		return true;
		}
	
	/*---------------------+
	|    Public Methods    |
	+---------------------*/
	}
?>