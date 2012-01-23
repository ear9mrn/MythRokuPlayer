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

	if     ($sort == 'year' ) { $SQL .= " ORDER BY year DESC";    }
	elseif ($sort == 'title') { $SQL .= " ORDER BY title ASC";    }
	elseif ($sort == 'genre') { $SQL .= " ORDER BY category ASC"; }
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

	$ext  = pathinfo($db_field['filename'], PATHINFO_EXTENSION);

	$genrenum = mysql_fetch_assoc(mysql_query("SELECT idgenre FROM videometadatagenre where idvideo='" . $db_field['intid'] . "' "));
	if ($genrenum['idgenre'] == 0) { $genrenum['idgenre'] = 22; }
	$genre = mysql_fetch_assoc(mysql_query("SELECT genre FROM videogenre where intid='" . $genrenum['idgenre'] . "' "));

	$filename   = implode("/", array_map("rawurlencode", explode("/", $db_field['filename'])));
	$hdimg	    = implode("/", array_map("rawurlencode", explode("/", $db_field['coverfile'])));
	$sdimg	    = $hdimg;

	$title	    = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['title'] ));
	$synopsis   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['plot'] ));

	$genre	    = $genre['genre'];
	$runtime    = $db_field['length'];
	$year	    = $db_field['year'];
	$starrating = $db_field['userrating'] * 10;

	print <<<EOF
    <item sdImg="$MythRokuDir/image.php?image=$sdimg" hdImg="$MythRokuDir/image.php?image=$hdimg">
	<title>$title</title>
	<contentId>$counter</contentId>
	<contentType>Movies</contentType>
	<contentQuality>$RokuDisplayType</contentQuality>
	<media>
	    <streamFormat>$ext</streamFormat>
	    <streamQuality>$RokuDisplayType</streamQuality>
	    <streamBitrate>$BitRate</streamBitrate>
	    <streamUrl>$mythtvdata/video/$filename</streamUrl>
	</media>
	<synopsis>$synopsis</synopsis>
	<genres>$genre</genres>
	<runtime>$runtime</runtime>
	<date>$year</date>
	<tvormov>movie</tvormov>
	<starrating>$starrating</starrating>
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

?>
