<?php

//get the local info from the settings file
require_once './settings.php';

//make a connection to the mysql sever
$db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
$db_found = mysql_select_db($MythTVdb, $db_handle);


if ($db_found) { 

	if (isset($_GET['basename']) ) {

		mysql_query("DELETE FROM recorded WHERE basename = '$_GET['basename']'");

		$files = glob('../data/recordings/*' . RemoveExtension($db_field['basename'])  . '*');
		array_walk($files,'myunlink');

		
		}

} else {

	print "Database NOT Found ";
	
}

	mysql_close($db_handle);

//function to remove file extensions
function RemoveExtension($strName)
{
     $ext = strrchr($strName, '.');

     if($ext !== false)
     {
         $strName = substr($strName, 0, -strlen($ext));
     }
     return $strName;
}

function myunlink($t)
{
	unlink($t);
}



?>

