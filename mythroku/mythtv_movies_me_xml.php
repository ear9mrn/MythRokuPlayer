<?php

require_once './settings.php';

$db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
$db_found = mysql_select_db($MythTVdb, $db_handle);
$counter = 1000;

if ($db_found) {

	if (isset($_GET['sort']) && $_GET['sort'] == 'year') {
		$SQL = "SELECT * FROM videometadata ORDER BY year DESC ";
	}elseif (isset($_GET['sort']) && $_GET['sort'] == 'title'){
		$SQL = "SELECT * FROM videometadata ORDER BY title ASC LIMIT 1";
	}elseif (isset($_GET['sort']) && $_GET['sort'] == 'genre'){
		$SQL = "SELECT * FROM videometadata ORDER BY category ASC";
	}
	else {
		$SQL = "SELECT * FROM videometadata";
	}

	$result = mysql_query($SQL);
	$num_rows = mysql_num_rows($result);

	

print "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?> 
	<feed>
	<!-- resultLength indicates the total number of results for this feed -->
	<resultLength>" . $num_rows . "</resultLength>
	<!-- endIndix  indicates the number of results for this *paged* section of the feed -->
	<endIndex>" . $num_rows . "</endIndex>";

	
 
	while ($db_field = mysql_fetch_assoc($result)) {

	$genrenum = mysql_fetch_assoc(mysql_query("SELECT idgenre FROM videometadatagenre where idvideo='" . $db_field['intid'] . "' "));
	if ($genrenum['idgenre'] == 0 ) { $genrenum['idgenre'] = 22; }
        $genre = mysql_fetch_assoc(mysql_query("SELECT genre FROM videogenre where intid='" . $genrenum['idgenre'] . "' "));

	print "	
	<item sdImg=\"" . $WebServer . "/pl/coverart/" . $db_field['coverfile'] . "\" hdImg=\"" . $WebServer . "/pl/coverart/" . $db_field['coverfile'] . "\">
		<title>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['title'] )) . "</title>
		<contentId>" . $counter++ . "</contentId>
		<contentType>Movies</contentType>
		<contentQuality>". $RokuDisplayType . "</contentQuality>
		<media>
			<streamFormat>mp4</streamFormat>
			<streamQuality>". $RokuDisplayType . "</streamQuality>
			<streamBitrate>". $BitRate . "</streamBitrate>
			<streamUrl>" . $WebServer . "/data/video/" . rawurlencode(substr($db_field['filename'], 0, strrpos($db_field['filename'], '.'))) .".mp4</streamUrl>		
		</media>
		<synopsis>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['plot'] )) . "</synopsis>	
		<genres>" . $genre['genre'] . "</genres>
		<runtime>" .$db_field['length'] . "</runtime>
		<date>Year: " . $db_field['year'] . "</date>
		<starrating>" . $db_field['userrating'] * 10 ."</starrating>
	</item>";	
	}
	print "</feed>";

	mysql_close($db_handle);

	}

	else {
	print "Database NOT Found ";
	mysql_close($db_handle);
}

function convert_datetime($str) 
{

list($date, $time) = explode(' ', $str);
list($year, $month, $day) = explode('-', $date);
list($hour, $minute, $second) = explode(':', $time);

$timestamp = mktime($hour, $minute, $second, $month, $day, $year);

return $timestamp;
}
?>
