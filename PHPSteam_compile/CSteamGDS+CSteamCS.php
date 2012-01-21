<?php
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