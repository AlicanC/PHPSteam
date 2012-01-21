<?php
$Files= array
	(
	'Copyright.php',
	'CPHPSteamCache.php',
	'CPHPSteam.php',
	'CSteamUserX.php',
	'CSteamUser.php',
	'CSteamGDS+CSteamCS.php'
	);

$CompiledFileName= '../PHPSteam.php';



//Check EOL from first file
$Check= file_get_contents($Files[0]);
if(strpos($Check, "\r\n")!==false)
	$EOL= "\r\n";
elseif(strpos($Check, "\r")!==false)
	$EOL= "\r";
elseif(strpos($Check, "\n")!==false)
	$EOL= "\n";

//Merge content of files
$Compilation= '';
foreach($Files as $File)
	$Compilation.= file_get_contents($File);

//Replace PHP tags
while(strpos($Compilation, $EOL.'?><?php'.$EOL)!==false)
	$Compilation= str_replace($EOL.'?><?php'.$EOL, $EOL.$EOL, $Compilation);

//Save compilation
file_put_contents($CompiledFileName, $Compilation);

echo 'Compiled file saved: "'.$CompiledFileName.'"';
?>