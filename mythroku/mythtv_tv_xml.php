<?php

// Get the local info from the settings file
require_once './settings.php';

// Put any command line arguments in $_GET
if ( $argv[1] )
{
    parse_str($argv[1], $_GET);
}

// Make a connection to the mySQL server
$db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
$db_found  = mysql_select_db($MythTVdb, $db_handle);

if ( $db_found )
{
    // Start building SQL query
    $SQL = "SELECT * FROM recorded";

    // Filter file extentions
    $SQL .= " WHERE basename LIKE '%.mp4'";
    $exts = array('mov', 'm4v'); reset($exts);
    foreach ( $exts as $ext ) { $SQL .= " OR basename LIKE '%.$ext'"; }

    // Add sorting
    if ( isset($_GET['sort']) )
    {
	$sort = $_GET['sort'];

	if     ($sort == 'title')    { $SQL .= " ORDER BY title ASC";     }
	elseif ($sort == 'date')     { $SQL .= " ORDER BY starttime ASC"; }
	elseif ($sort == 'channel')  { $SQL .= " ORDER BY chanid ASC";    }
	elseif ($sort == 'genre')    { $SQL .= " ORDER BY category ASC";  }
	elseif ($sort == 'recgroup') { $SQL .= " ORDER BY recgroup ASC";  }
    }

    // Grab the SQL data
    $result   = mysql_query($SQL);
    $num_rows = mysql_num_rows($result);

    // Print the XML header
    print <<<EOF
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<feed>
    <!-- resultLength indicates the total number of results for this feed -->
    <resultLength>$num_rows</resultLength>
    <!-- endIndix  indicates the number of results for this *paged* section of the feed -->
    <endIndex>$num_rows</endIndex>

EOF;

    $counter = 0;

    // Print out all the records in XML format for the Roku to read 
    while ( $db_field = mysql_fetch_assoc($result) )
    {
	$counter++;

	$filename  = $db_field['basename'];
	$programid = $db_field['programid'];

	$SQL = "SELECT * FROM recordedprogram WHERE programid='$programid'";
	$tmp_result   = mysql_query($SQL);
	$tmp_db_field = mysql_fetch_assoc($tmp_result);

	$contentType = "movie";
	if ( 'series' == $tmp_db_field['category_type'] )
	{
	    $contentType = "episode";
	    $episode	 = $tmp_db_field['syndicatedepisodenumber'];
	}

	$title	    = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['title'] ));
	$subtitle   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['subtitle'] ));
	$synopsis   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['description'] ));

	$img	= $db_field['hostname'] . "/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']);
	$hdimg	= "$img/100/56/-1/$filename.100x56x-1.png";
	$sdimg	= "$img/100/75/-1/$filename.100x75x-1.png";

	$isHD = 'false';
	$quality = 'SD';
#	if ( '0' !== $tmp_db_field['hdtv'] )
#	{
#	    $isHD = 'true';
#	    $quality = 'HD';
#	}

	$bitRate    = 0;
	$url	    = "$WebServer/pl/stream/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']);
	$contentId  = $filename;
	$format	    = pathinfo($filename, PATHINFO_EXTENSION);

	$delcmd = "$MythRokuDir/mythtv_tv_del.php?basename=$filename";

	$genre = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['category'] ));

	$runtime    = convert_datetime($db_field['endtime']) - convert_datetime($db_field['starttime']);
	$date	    = date("m/d/Y h:ia", convert_datetime($db_field['starttime']));
	$starrating = 0;
	$rating	    = "";

	print <<<EOF
    <item>
	<contentType>$contentType</contentType>
	<title>$title</title>
	<subtitle>$subtitle</subtitle>
	<synopsis>$synopsis</synopsis>
	<hdImg>$WebServer/tv/get_pixmap/$hdimg</hdImg>
	<sdImg>$WebServer/tv/get_pixmap/$sdimg</sdImg>
	<media>
	    <streamBitrate>$bitRate</streamBitrate>
	    <streamUrl>$url</streamUrl>
	    <streamQuality>$quality</streamQuality>
	    <streamContentId>$contentId</streamContentId>
	    <streamFormat>$format</streamFormat>
	</media>
	<isHD>$isHD</isHD>
	<episode>$episode</episode>
	<genres>$genre</genres>
	<runtime>$runtime</runtime>
	<date>$date</date>
	<starrating>$starrating</starrating>
	<rating>$rating</rating>
	<index>$counter</index>
	<recording>true</recording>
	<delcommand>$delcmd</delcommand>
    </item>

EOF;

    }

    print <<<EOF
</feed>
EOF;

}
else // Throw error if can not connect to database.
{
    print "Database NOT Found";
}

// Close mySQL pointer
mysql_close($db_handle);

// Convert mySQL timestamp to Unix time
function convert_datetime($str) 
{
    list($date, $time) = explode(' ', $str);
    list($year, $month, $day) = explode('-', $date);
    list($hour, $minute, $second) = explode(':', $time);

    $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

    return $timestamp;
}

?>
