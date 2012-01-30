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
    $SQL = "SELECT * FROM videometadata";

    // Filter file extentions
    $SQL .= " WHERE filename LIKE '%.mp4'";
    $exts = array('mov', 'm4v'); reset($exts);
    foreach ( $exts as $ext ) { $SQL .= " OR filename LIKE '%.$ext'"; }

    // Add sorting
    if ( isset($_GET['sort']) )
    {
	$sort = $_GET['sort'];

	if     ($sort == 'title') { $SQL .= " ORDER BY title ASC";       }
	elseif ($sort == 'date' ) { $SQL .= " ORDER BY releasedate ASC"; }
	elseif ($sort == 'genre') { $SQL .= " ORDER BY category ASC";    }
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

	$filename  = $db_field['filename'];

	$contentType = "movie";
	if ( 0 < $db_field['season'] )
	{
	    $contentType = "episode";
	    $episode	 = $db_field['season'] . "-" . $db_field['episode'];
	}

	$title	    = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['title'] ));
	$subtitle   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['subtitle'] ));
	$synopsis   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['plot'] ));

	$hdimg	= implode("/", array_map("rawurlencode", explode("/", $db_field['coverfile'])));
	$sdimg	= $hdimg;

	$isHD = 'false';
	$quality = 'SD';

	$bitRate    = 0;
	$url	    = "$mythtvdata/video/" . implode("/", array_map("rawurlencode", explode("/", $filename)));
	$contentId  = $filename;
	$format	    = pathinfo($filename, PATHINFO_EXTENSION);

	$genrenum = mysql_fetch_assoc(mysql_query("SELECT idgenre FROM videometadatagenre where idvideo='" . $db_field['intid'] . "' "));
	if ($genrenum['idgenre'] == 0) { $genrenum['idgenre'] = 22; }
	$genres = mysql_fetch_assoc(mysql_query("SELECT genre FROM videogenre where intid='" . $genrenum['idgenre'] . "' "));
	$genre = $genres['genre'];

	$runtime    = $db_field['length'] * 60;
	$date	    = date("m/d/Y", convert_datetime($db_field['releasedate']));
	$starrating = $db_field['userrating'] * 10;
	$rating	    = $db_field['rating'];

	print <<<EOF
    <item>
	<contentType>$contentType</contentType>
	<title>$title</title>
	<subtitle>$subtitle</subtitle>
	<synopsis>$synopsis</synopsis>
	<hdImg>$MythRokuDir/image.php?image=$hdimg</hdImg>
	<sdImg>$MythRokuDir/image.php?image=$sdimg</sdImg>
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
	<recording>false</recording>
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
    list($year, $month, $day) = explode('-', $str);

    $timestamp = mktime(0, 0, 0, $month, $day, $year);

    return $timestamp;
}

?>
