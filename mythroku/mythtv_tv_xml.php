<?php

// Get the local info from the settings file
require_once './settings.php';

include 'xml_utils.php';

// Put any command line arguments in $_GET
$size = count($argv);
if ( ($size >= 2) && $argv[1] )
{
    parse_str($argv[1], $_GET);
}

$start_row = 0;
if ( isset($_GET['index']) ) { $start_row = $_GET['index']; }

$script = "mythtv_movies_xml.php";
if ( isset($_GET['script']) ) { $script = $_GET['script']; }

// Make a connection to the mySQL server
$db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
$db_found  = mysql_select_db($MythTVdb, $db_handle);

if ( $db_found )
{
    // Start building SQL query
    $SQL = "SELECT * FROM recorded";

    // Filter file extentions
    $SQL .= " WHERE basename LIKE '%.mp4'";
    $SQL .=    " OR basename LIKE '%.m4v'";
    $SQL .=    " OR basename LIKE '%.mov'";

    // Add sorting
    if ( isset($_GET['sort']) )
    {
        switch ( $_GET['sort'] )
        {
            case 'title':    $SQL .= " ORDER BY title ASC";            break;
            case 'date':     $SQL .= " ORDER BY starttime, title ASC"; break;
            case 'channel':  $SQL .= " ORDER BY chanid, title ASC";    break;
            case 'genre':    $SQL .= " ORDER BY category, title ASC";  break;
            case 'recgroup': $SQL .= " ORDER BY recgroup, title ASC";  break;
        }
    }

    // Get the full result count
    $result     = mysql_query($SQL);
    $total_rows = mysql_num_rows($result);

    // Limit the number results
    if ( 0 !== $ResultLimit )
    {
        $SQL .= " LIMIT $start_row, $ResultLimit";

        // Get the subset results
        $result = mysql_query($SQL);
    }

    // Get the subset result count
    $result_rows = mysql_num_rows($result);

    // Print the XML header
    $args = array( 'start_row'   => $start_row,
                   'result_rows' => $result_rows,
                   'total_rows'  => $total_rows,
                   'list_type'   => 'recording' );
    xml_start_feed( $args );

    $args = array( 'script'     => $script,
                   'start_row'  => $start_row,
                   'html_parms' => $_GET );
    xml_start_dir( $args );

    $counter = $start_row;

    // Print out all the records in XML format for the Roku to read
    while ( $db_field = mysql_fetch_assoc($result) )
    {
        $filename  = $db_field['basename'];
        $programid = $db_field['programid'];

        $SQL = "SELECT * FROM recordedprogram WHERE programid='$programid'";
        $tmp_result   = mysql_query($SQL);
        $tmp_db_field = mysql_fetch_assoc($tmp_result);

        $contentType = "movie";
        $episode     = "";
        if ( 'series' == $tmp_db_field['category_type'] )
        {
            $contentType = "episode";
            $episode     = $tmp_db_field['syndicatedepisodenumber'];
        }

        $title      = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['title'] ));
        $subtitle   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['subtitle'] ));
        $synopsis   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['description'] ));

        $img    = $db_field['hostname'] . "/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']);
        $hdimg  = "$img/100/56/-1/$filename.100x56x-1.png";
        $sdimg  = "$img/100/75/-1/$filename.100x75x-1.png";

        $quality = $RokuDisplayType;
        $isHD    = 'false';
#       if ( '0' !== $tmp_db_field['hdtv'] ) { $quality = 'HD'; }
        if ( 'HD' == $quality ) { $isHD = 'true'; }

        $url = "$WebServer/pl/stream/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']);

        $genre = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['category'] ));

        $args = array(
                'contentType' => $contentType,
                'title'       => $title,
                'subtitle'    => $subtitle,
                'synopsis'    => $synopsis,
                'hdImg'       => "$WebServer/tv/get_pixmap/$hdimg",
                'sdImg'       => "$WebServer/tv/get_pixmap/$sdimg",
                'streamBitrate'   => 0,
                'streamUrl'       => $url,
                'streamQuality'   => $quality,
                'streamContentId' => $filename,
                'streamFormat'    => pathinfo($filename, PATHINFO_EXTENSION),
                'isHD'        => $isHD,
                'episode'     => $episode,
                'genres'      => $genre,
                'runtime'     => convert_datetime($db_field['endtime']) - convert_datetime($db_field['starttime']),
                'date'        => date("m/d/Y h:ia", convert_datetime($db_field['starttime'])),
                'starRating'  => 0,
                'rating'      => '',
                'index'       => $counter,
                'isRecording' => 'true',
                'delCmd'      => "$MythRokuDir/mythtv_tv_del.php?basename=$filename" );

        xml_file( $args );

        $counter++;
    }

    $args = array( 'script'      => $script,
                   'start_row'   => $start_row,
                   'result_rows' => $result_rows,
                   'total_rows'  => $total_rows,
                   'html_parms'  => $_GET );
    xml_end_dir( $args );

    xml_end_feed();

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
