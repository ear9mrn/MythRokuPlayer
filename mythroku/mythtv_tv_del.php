<?php

//get the local info from the settings file
require_once './settings.php';

//make a connection to the mysql sever
$db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
$db_found = mysql_select_db($MythTVdb, $db_handle);


if ($db_found) { 

	if (isset($_GET['recordid']) ) {

		$mysqltime = unixToMySQL($_GET['recordid']);
		$unixt = convert_datetime($mysqltime); 
		mysql_query("DELETE FROM recorded WHERE starttime = '$mysqltime' ");

		$files = glob('../data/recordings/*' . $unixt . '*');
		array_walk($files,'myunlink');

		
		}

} else {

	print "Database NOT Found ";
	
}

	mysql_close($db_handle);

function unixToMySQL($timestamp)
{
    return date('Y-m-d H:i:s', $timestamp);
}


function myunlink($t)
{
	unlink($t);
}


//function to convert mysql timestamp to unix time
function convert_datetime($str) 
{

	list($date, $time) = explode(' ', $str);
	list($year, $month, $day) = explode('-', $date);
	list($hour, $minute, $second) = explode(':', $time);

	$timestamp = mktime($hour, $minute, $second, $month, $day, $year);

	return $timestamp;
}

?>

