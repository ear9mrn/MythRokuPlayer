<?php

function get_xml_data()
{
    $xml = '';

    require 'settings.php';
    require 'xml_utils.php';

    // Get any parameters from $_GET
    $type = '';
    if ( isset($_GET['type']) ) { $type = $_GET['type']; }
    switch ( $type )
    {
        case 'vid': case 'rec': break;
        default: die( "Invalid parameter: [type]=[$type]\n" );
    }

    $sort = 'title'; // default
    if ( isset($_GET['sort']) ) { $sort = $_GET['sort']; }

    $start_row = 1;
    if ( isset($_GET['index']) and (0 < $_GET['index']) )
    {
        $start_row = $_GET['index'];
    }

    $test = false;
    if ( isset($_GET['test']) ) { $test = true; }

    // Make a connection to the mySQL server
    $db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
    $db_found  = mysql_select_db($MythTVdb, $db_handle);
    if ( !$db_found )
    {
        die( 'Database NOT found: ' . mysql_error() );
    }

    // Query SQL database
    $sql_query  = build_sql( $type, $sort );
    $sql_result = mysql_query( $sql_query );

    // Check boundry limits
    $total_rows = mysql_num_rows( $sql_result );
    if ( $total_rows < $start_row )
    {
        $start_row = $total_rows;
        $_GET['index'] = $start_row;
    }

    // Limit the number results
    if ( 0 != $ResultLimit )
    {
        $sql_query .= " LIMIT " . ($start_row - 1) . ", $ResultLimit";

        // Get the subset results
        $sql_result = mysql_query($sql_query);
    }

    // Get the subset result count
    $result_rows = mysql_num_rows( $sql_result );

    // Start XML feed
    $args = array( 'start_row'   => $start_row,
                   'result_rows' => $result_rows,
                   'total_rows'  => $total_rows,
                   'list_type'   => $type );
    $xml .= xml_start_feed( $args );

    // Print 'previous' pueso-directory
    $args = array( 'start_row'  => $start_row,
                   'html_parms' => $_GET );
    $xml .= xml_start_dir( $args );

    // Get XML data for each file in this query.
    $xml .= build_xml( $sql_result, $type, $start_row );

    // Print 'next' pueso-directory
    $args = array( 'start_row'   => $start_row,
                   'result_rows' => $result_rows,
                   'total_rows'  => $total_rows,
                   'html_parms'  => $_GET );
    $xml .= xml_end_dir( $args );

    // End XML feed
    $xml .= xml_end_feed();

    // Close mySQL pointer
    mysql_close($db_handle);

    return $xml;
}

//------------------------------------------------------------------------------
// Build SQL queries
//------------------------------------------------------------------------------

function build_sql( $type, $sort )
{
    switch ( $type )
    {
        case 'rec': return build_sql_rec( $sort );
        case 'vid': return build_sql_vid( $sort );
    }
}

//------------------------------------------------------------------------------

function build_sql_rec( $sort )
{
print "rec";
    // Start building SQL query
    $SQL  = "SELECT * FROM recorded ";
    $SQL .= "INNER JOIN recordedprogram USING (programid)";

    // Filter file extentions
    $SQL .= " WHERE ( basename LIKE '%.mp4'";
    $SQL .=      " OR basename LIKE '%.m4v'";
    $SQL .=      " OR basename LIKE '%.mov' )";

    // Add sorting
    switch ( $sort )
    {
        case 'title':    $SQL .= " ORDER BY recorded.title ASC";                     break;
        case 'date':     $SQL .= " ORDER BY recorded.starttime, recorded.title ASC"; break;
        case 'channel':  $SQL .= " ORDER BY recorded.chanid, recorded.title ASC";    break;
        case 'genre':    $SQL .= " ORDER BY recorded.category, recorded.title ASC";  break;
        case 'recgroup': $SQL .= " ORDER BY recorded.recgroup, recorded.title ASC";  break;
    }

    return $SQL;
}

//------------------------------------------------------------------------------

function build_sql_vid( $sort )
{
    // Start building SQL query
    $SQL = "SELECT * FROM videometadata";

    // Filter file extentions
    $SQL .= " WHERE ( filename LIKE '%.mp4'";
    $SQL .=      " OR filename LIKE '%.m4v'";
    $SQL .=      " OR filename LIKE '%.mov' )";

    // Add sorting
    switch ( $sort )
    {
        case 'title': $SQL .= " ORDER BY title ASC";              break;
        case 'date':  $SQL .= " ORDER BY releasedate, title ASC"; break;
        case 'genre': $SQL .= " ORDER BY category, title ASC";    break;
    }

    return $SQL;
}

//------------------------------------------------------------------------------
// Build XML
//------------------------------------------------------------------------------

function build_xml( $sql_result, $type, $index )
{
    $xml = '';

    while ( $db_field = mysql_fetch_assoc($sql_result) )
    {
        switch ( $type )
        {
            case 'rec': $xml .= build_xml_rec( $db_field, $index ); break;
            case 'vid': $xml .= build_xml_vid( $db_field, $index ); break;
        }

        $index++;
    }

    return $xml;
}

//------------------------------------------------------------------------------

function build_xml_rec( $db_field, $index )
{
    require 'settings.php';

    $filename = $db_field['basename'];

    $str_time = convert_datetime($db_field['starttime']);
    $end_time = convert_datetime($db_field['endtime']);

    $chanid_strtime = "{$db_field['chanid']}/$str_time";

    $contentType = 'movie';
    $episode     = array();
    if ( 'series' == $db_field['category_type'] )
    {
        $contentType = 'episode';

        if ( isDbVer25() )
        {
            $episode['season']  = $db_field['season'];
            $episode['episode'] = $db_field['episode'];
        }
        else
        {
            $episode['legacy'] = $db_field['syndicatedepisodenumber'];
        }
    }

    $img  = "{$db_field['hostname']}/$chanid_strtime";
    $hdimg = "$img/100/56/-1/$filename.100x56x-1.png";
    $sdimg = "$img/100/75/-1/$filename.100x75x-1.png";

    $stream = array(
        'bitrate'   => 0,
        'url'       => "$WebServer/pl/stream/" . html_encode($chanid_strtime),
        'contentId' => html_cleanup($filename),
        'format'    => pathinfo($filename, PATHINFO_EXTENSION),
    );

    // TODO: You can find the TV rating in table 'recordedrating'

    $args = array(
        'title'       => html_cleanup($db_field['title']),
        'subtitle'    => html_cleanup($db_field['subtitle']),
        'hdImg'       => "$WebServer/tv/get_pixmap/" . html_encode($hdimg),
        'sdImg'       => "$WebServer/tv/get_pixmap/" . html_encode($sdimg),
        'synopsis'    => html_cleanup($db_field['description']),
        'contentType' => $contentType,
        'episode'     => $episode,
        'genres'      => array( html_cleanup($db_field['category']) ),
        'runtime'     => $end_time - $str_time,
        'date'        => date("m/d/Y h:ia", $str_time),
        'starRating'  => 0,
        'rating'      => '',
        'index'       => $index,
        'isRecording' => 'true',
        'delCmd'      => "$MythRokuDir/mythtv_tv_del.php?basename=" . html_encode($filename),
        'hdStream'    => $stream,
        'sdStream'    => $stream,
    );

    return xml_file( $args );
}

//------------------------------------------------------------------------------

function build_xml_vid( $db_field, $index )
{
    require 'settings.php';

    $filename = $db_field['filename'];

    $contentType = 'movie';
    $episode     = array();
    if ( 'TELEVISION' == $db_field['contenttype'] )
    {
        $contentType = 'episode';
        $episode['season']  = $db_field['season'];
        $episode['episode'] = $db_field['episode'];
    }

    $hdimg = html_encode($db_field['coverfile']);
    $sdimg = $hdimg;

    $SQL  = "SELECT * FROM videometadatagenre, videogenre ";
    $SQL .= "WHERE videometadatagenre.idgenre = videogenre.intid ";
    $SQL .= "AND idvideo='{$db_field['intid']}'";
    $genre_result = mysql_query($SQL);
    $genre_arr = array();
    while ( $genres = mysql_fetch_assoc($genre_result) )
    {
        array_push( $genre_arr, html_cleanup($genres['genre']) );
    }

    $stream = array(
        'bitrate'   => 0,
        'url'       => "$mythtvdata/video/" . html_encode($filename),
        'contentId' => html_cleanup($filename),
        'format'    => pathinfo($filename, PATINFO_EXTENSION),
    );

    $args = array(
        'title'       => html_cleanup($db_field['title']),
        'subtitle'    => html_cleanup($db_field['subtitle']),
        'hdImg'       => "$MythRokuDir/image.php?image=" . html_encode($hdimg),
        'sdImg'       => "$MythRokuDir/image.php?image=" . html_encode($sdimg),
        'synopsis'    => html_cleanup($db_field['plot']),
        'contentType' => $contentType,
        'episode'     => $episode,
        'genres'      => $genre_arr,
        'runtime'     => $db_field['length'] * 60,
        'date'        => date("m/d/Y", convert_date($db_field['releasedate'])),
        'starRating'  => $db_field['userrating'] * 10,
        'rating'      => $db_field['rating'],
        'index'       => $index,
        'isRecording' => 'false',
        'delCmd'      => '',
        'hdStream'    => $stream,
        'sdStream'    => $stream,
    );

    return xml_file( $args );
}

//------------------------------------------------------------------------------
// Utility functions
//------------------------------------------------------------------------------

function convert_date( $date )
{
    list($year, $month, $day) = explode('-', $date);

    $timestamp = mktime(0, 0, 0, $month, $day, $year);

    return $timestamp;
}

function convert_datetime( $datetime )
{
    list($date, $time) = explode(' ', $datetime);
    list($year, $month, $day) = explode('-', $date);
    list($hour, $minute, $second) = explode(':', $time);

    $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

    return $timestamp;
}

//------------------------------------------------------------------------------

function html_cleanup($str)
{
    return htmlspecialchars( preg_replace('/[^(\x20-\x7F)]*/', '', $str) );
}

function html_encode($str)
{
    return implode("/", array_map("rawurlencode", explode("/", $str)));
}

//------------------------------------------------------------------------------

// Returns true if the current database is at least verion .25
function isDbVer25()
{
    $query  = "SELECT * FROM settings WHERE value = 'DBSchemaVer'";
    $result = mysql_query( $query );
    if ( !$result )
    {
        $message  = 'Query failed: ' . mysql_error() . "\n";
        $message .= "Query: $query";
        die( $message );
    }

    $num = mysql_num_rows( $result );
    if ( 1 != $num )
    {
        $message  = "Invalid results found\n";
        $message .= "Query: $query";
        die( $message );
    }

    $row = mysql_fetch_assoc( $result );
    if ( 1299 <= $row['data'] ) // Version .25
        return true;

    return false;
}

//------------------------------------------------------------------------------

?>
