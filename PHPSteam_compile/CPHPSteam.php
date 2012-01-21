<?php
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
?>