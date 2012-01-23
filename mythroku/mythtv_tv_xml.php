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

	if     ($sort == 'date')     { $SQL .= " ORDER BY starttime DESC"; }
	elseif ($sort == 'title')    { $SQL .= " ORDER BY title ASC";      }
	elseif ($sort == 'recgroup') { $SQL .= " ORDER BY recgroup ASC";   }
	elseif ($sort == 'genre')    { $SQL .= " ORDER BY category ASC";   }
	elseif ($sort == 'channel')  { $SQL .= " ORDER BY chanid ASC";    }
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

    // Set the stream ID to some abitary number 
    $counter = 1000;

    // Print out all the records in XML format for the Roku to read 
    while ( $db_field = mysql_fetch_assoc($result) )
    {
	$counter++;

	$ext  = pathinfo($db_field['basename'], PATHINFO_EXTENSION);

	$url    = "$WebServer/pl/stream/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']) . ".mp4";
	$hdimg  = "$WebServer/tv/get_pixmap/" . $db_field['hostname'] . "/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']) . "/100/75/-1/" . $db_field['basename'] . ".100x75x-1.png";
	$sdimg  = "$WebServer/tv/get_pixmap/" . $db_field['hostname'] . "/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']) . "/100/75/-1/" . $db_field['basename'] . ".100x75x-1.png";
	$delcmd = "$MythRokuDir/mythtv_tv_del.php?basename=" . $db_field['basename'];

	$title	    = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['title'] ));
	$subtitle   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['subtitle'] ));
	$synopsis   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['description'] ));

	$genre      = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['category'] ));
	$ShowLength = convert_datetime($db_field['endtime']) - convert_datetime($db_field['starttime']);
	$date	    = date("F j, Y, g:i a", convert_datetime($db_field['starttime']));

	//print out all the records in xml format for roku to read 
	print <<<EOF
    <item sdImg="$sdimg" hdImg="$hdimg">
	<title>$title</title>
	<contentId>$counter</contentId>
	<contentType>TV</contentType>
	<contentQuality>$RokuDisplayType</contentQuality>
	<media>
	    <streamFormat>$ext</streamFormat>
	    <streamQuality>$RokuDisplayType</streamQuality>
	    <streamBitrate>$BitRate</streamBitrate>
	    <streamUrl>$url</streamUrl>
	</media>
	<synopsis>$synopsis</synopsis>
	<genres>$genre</genres>
	<subtitle>$subtitle</subtitle>
	<runtime>$ShowLength</runtime>
	<date>$date</date>
	<tvormov>tv</tvormov>
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
