<?php

// Get the local info from the settings file
require_once './settings.php';

include 'xml_utils.php';

// Put any command line arguments in $_GET
if ( $argv[1] )
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
    $SQL = "SELECT * FROM videometadata";

    // Filter file extentions
    $SQL .= " WHERE filename LIKE '%.mp4'";
    $SQL .=    " OR filename LIKE '%.m4v'";
    $SQL .=    " OR filename LIKE '%.mov'";

    // Add sorting
    if ( isset($_GET['sort']) )
    {
        switch ( $_GET['sort'] )
        {
            case 'title': $SQL .= " ORDER BY title ASC";              break;
            case 'date':  $SQL .= " ORDER BY releasedate, title ASC"; break;
            case 'genre': $SQL .= " ORDER BY category, title ASC";    break;
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
                   'list_type'   => 'video' );
    xml_start_feed( $args );

    $args = array( 'script'     => $script,
                   'start_row'  => $start_row,
                   'html_parms' => $_GET );
    xml_start_dir( $args );

    $counter = $start_row;

    // Print out all the records in XML format for the Roku to read
    while ( $db_field = mysql_fetch_assoc($result) )
    {
        $filename  = $db_field['filename'];

        $contentType = "movie";
        if ( 0 < $db_field['season'] )
        {
            $contentType = "episode";
            $episode     = $db_field['season'] . "-" . $db_field['episode'];
        }

        $title      = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['title'] ));
        $subtitle   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['subtitle'] ));
        $synopsis   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['plot'] ));

        $hdimg  = implode("/", array_map("rawurlencode", explode("/", $db_field['coverfile'])));
        $sdimg  = $hdimg;

        $quality = $RokuDisplayType;
        $isHD    = 'false';
        if ( 'HD' == $quality ) { $isHD = 'true'; }

        $url = "$mythtvdata/video/" . implode("/", array_map("rawurlencode", explode("/", $filename)));

        $genrenum = mysql_fetch_assoc(mysql_query("SELECT idgenre FROM videometadatagenre where idvideo='" . $db_field['intid'] . "' "));
        if ($genrenum['idgenre'] == 0) { $genrenum['idgenre'] = 22; }
        $genres = mysql_fetch_assoc(mysql_query("SELECT genre FROM videogenre where intid='" . $genrenum['idgenre'] . "' "));
        $genre = $genres['genre'];

        $args = array(
                'contentType' => $contentType,
                'title'       => $title,
                'subtitle'    => $subtitle,
                'synopsis'    => $synopsis,
                'hdImg'       => "$MythRokuDir/image.php?image=$hdimg",
                'sdImg'       => "$MythRokuDir/image.php?image=$sdimg",
                'streamBitrate'   => 0,
                'streamUrl'       => $url,
                'streamQuality'   => $quality,
                'streamContentId' => $filename,
                'streamFormat'    => pathinfo($filename, PATHINFO_EXTENSION),
                'isHD'        => $isHD,
                'episode'     => $episode,
                'genres'      => $genre,
                'runtime'     => $db_field['length'] * 60,
                'date'        => date("m/d/Y", convert_datetime($db_field['releasedate'])),
                'starRating'  => $db_field['userrating'] * 10,
                'rating'      => $db_field['rating'],
                'index'       => $counter,
                'isRecording' => 'false',
                'delCmd'      => '' );

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
    list($year, $month, $day) = explode('-', $str);

    $timestamp = mktime(0, 0, 0, $month, $day, $year);

    return $timestamp;
}

?>
