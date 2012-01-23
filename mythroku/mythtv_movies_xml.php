<?php

//get the local info from the settings file
require_once './settings.php';

// Put any command line arguments in $_GET
if ( $argv[1] )
{
    parse_str($argv[1], $_GET);
}

//make a connection to the mysql sever
$db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
$db_found = mysql_select_db($MythTVdb, $db_handle);

//set the stream id to some abitary number 
$counter = 1000;

//define quiery for sorting the records, only get files that are .mp4
if ( $db_found )
{
	if (isset($_GET['sort']) && $_GET['sort'] == 'year') {
		$SQL = "SELECT * FROM videometadata WHERE filename LIKE '%.mp4' ORDER BY year DESC ";
	}elseif (isset($_GET['sort']) && $_GET['sort'] == 'title'){
		$SQL = "SELECT * FROM videometadata WHERE filename LIKE '%.mp4' ORDER BY title ASC ";
	}elseif (isset($_GET['sort']) && $_GET['sort'] == 'genre'){
		$SQL = "SELECT * FROM videometadata WHERE filename LIKE '%.mp4' ORDER BY category ASC";
	}
	else {
		$SQL = "SELECT * FROM videometadata WHERE filename LIKE '%.mp4' ";
	}

    //grab the data
    $result = mysql_query($SQL);
    $num_rows = mysql_num_rows($result);

    // print the xml header
    print <<<EOF
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<feed>
    <!-- resultLength indicates the total number of results for this feed -->
    <resultLength>$num_rows</resultLength>
    <!-- endIndix  indicates the number of results for this *paged* section of the feed -->
    <endIndex>$num_rows</endIndex>

EOF;

    //print out all the records in xml format for roku to read 
    while ( $db_field = mysql_fetch_assoc($result) )
    {
	$counter++;

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
	    <streamFormat>mp4</streamFormat>
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
//throw error if can not connect to database.
else
{
    print "Database NOT Found";
}

//close mysql pointer
mysql_close($db_handle);

?>

