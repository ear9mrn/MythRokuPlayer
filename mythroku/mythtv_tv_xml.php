<?php

//get the local info from the settings file
require_once './settings.php';

//make a connection to the mysql sever
$db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
$db_found = mysql_select_db($MythTVdb, $db_handle);

//set the stream id to some abitary number 
$counter = 1000;

//define quiery for sorting the records
if ($db_found) {

	if (isset($_GET['sort']) && $_GET['sort'] == 'date') {
		$SQL = "SELECT * FROM recorded ORDER BY starttime DESC";
	}elseif (isset($_GET['sort']) && $_GET['sort'] == 'title'){
		$SQL = "SELECT * FROM recorded ORDER BY title ASC";
	}elseif (isset($_GET['sort']) && $_GET['sort'] == 'recgroup'){
		$SQL = "SELECT * FROM recorded ORDER BY recgroup ASC";
	}elseif (isset($_GET['sort']) && $_GET['sort'] == 'genre'){
		$SQL = "SELECT * FROM recorded ORDER BY category ASC";
	}elseif (isset($_GET['sort']) && $_GET['sort'] == 'channel'){
		$SQL = "SELECT * FROM recorded ORDER BY channel ASC";
	}else {
		$SQL = "SELECT * FROM recorded";
	}

//grab the data
$result = mysql_query($SQL);
$num_rows = mysql_num_rows($result);

//check if appropriate mp4 files exists for each recording
while ($db_field = mysql_fetch_assoc($result)) {

	if ( !file_exists("../data/recordings/" . RemoveExtension($db_field['basename'] ) . ".mp4" ) ){
		$num_rows = $num_rows - 1;
		} 

}

//print the xml header
print "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?> 
	<feed>
	<!-- resultLength indicates the total number of results for this feed -->
	<resultLength>" . $num_rows . "</resultLength>
	<!-- endIndix  indicates the number of results for this *paged* section of the feed -->
	<endIndex>" . $num_rows . "</endIndex>";


//reset pointer
mysql_data_seek ( $result , 0 );

while ($db_field = mysql_fetch_assoc($result)) {

	//only show mp4 recordings
	if ( file_exists("../data/recordings/" . RemoveExtension($db_field['basename'] ) . ".mp4" ) ){

		//compute the length of the show
		$ShowLength = convert_datetime($db_field['endtime']) - convert_datetime($db_field['starttime']);

		//print out all the records in xml format for roku to read 
		print "	
		<item sdImg=\"" . $WebServer . "/tv/get_pixmap/" . $db_field['hostname'] . "/" . $db_field['chanid'] ."/" . convert_datetime($db_field['starttime']) . "/100/75/-1/" . $db_field['basename'] .".100x75x-1.png\" hdImg=\"" . $WebServer . "/tv/get_pixmap/" . $db_field['hostname'] . "/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']) . "/100/75/-1/" . $db_field['basename'] . ".100x75x-1.png\">
			 <title>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['title'] )) . "</title>
			<contentId>" . $counter++ . "</contentId>
			<contentType>TV</contentType>
			<contentQuality>". $RokuDisplayType . "</contentQuality>
			<media>
				<streamFormat>mp4</streamFormat>
				<streamQuality>". $RokuDisplayType . "</streamQuality>
				<streamBitrate>" . $BitRate . "</streamBitrate>
				<streamUrl>" . $WebServer . "/pl/stream/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']) . ".mp4</streamUrl>
			</media>
			<synopsis>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['description'] )) . "</synopsis>
		 	<genres>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['category'] )) . "</genres>
			<subtitle>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['subtitle'] )) . "</subtitle>
			<runtime>" . $ShowLength . "</runtime>
			<date>" . date("F j, Y, g:i a", convert_datetime($db_field['starttime'])) . "</date>
			<tvormov>tv</tvormov>
			<delcommand>" . $WebServer . "/mythroku/mythtv_tv_del.php?recordid=" . convert_datetime($db_field['starttime']) . "&amp;basename=" . RemoveExtension($db_field['basename']) . "</delcommand>
		</item>";	
	}
}
print "</feed>";

}

//throw error if can not connect to database
else {
	print "Database NOT Found ";

}

//close mysql pointer
mysql_close($db_handle);


//function to convert mysql timestamp to unix time
function convert_datetime($str) 
{

	list($date, $time) = explode(' ', $str);
	list($year, $month, $day) = explode('-', $date);
	list($hour, $minute, $second) = explode(':', $time);

	$timestamp = mktime($hour, $minute, $second, $month, $day, $year);

	return $timestamp;
}

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

?>
