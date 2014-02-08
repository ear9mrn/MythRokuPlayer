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

    $sort = '';
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
        die( 'Database NOT found' . mysql_error() . '\n' );
    }

    // Build SQL query
    $SQL = '';
    switch ( $type )
    {
        case 'vid': $SQL = build_query_vid( $sort ); break;
        case 'rec': $SQL = build_query_rec( $sort ); break;
    }

    // Get the full result count
    $sql_result = mysql_query($SQL);
    $total_rows = mysql_num_rows($sql_result);

    // Check boundry limits
    if ( $total_rows < $start_row )
    {
        $start_row = $total_rows;
        $_GET['index'] = $start_row;
    }

    // Limit the number results
    if ( 0 !== $ResultLimit )
    {
        $SQL .= " LIMIT " . ($start_row - 1) . ", $ResultLimit";

        // Get the subset results
        $sql_result = mysql_query($SQL);
    }

    // Get the subset result count
    $result_rows = mysql_num_rows($sql_result);

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
    switch ( $type )
    {
        case 'vid': $xml .= build_xml_vid( $sql_result, $start_row ); break;
        case 'rec': $xml .= build_xml_rec( $sql_result, $start_row ); break;
    }

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

function build_query_vid( $sort )
{
    // Start building SQL query
    $SQL = "SELECT * FROM videometadata";

    // Filter file extentions
    $SQL .= " WHERE filename LIKE '%.mp4'";
    $SQL .=    " OR filename LIKE '%.m4v'";
    $SQL .=    " OR filename LIKE '%.mov'";

    // Add sorting
    switch ( $sort )
    {
        case 'title': $SQL .= " ORDER BY title ASC";              break;
        case 'date':  $SQL .= " ORDER BY releasedate, title ASC"; break;
        case 'genre': $SQL .= " ORDER BY category, title ASC";    break;
    }

    return $SQL;
}

function build_query_rec( $sort )
{
    // Start building SQL query
	$SQL  = "SELECT * ,recorded.starttime as start_time, recorded.endtime as end_time";
	$SQL .= " FROM recorded";
	$SQL .= " INNER JOIN recordedprogram USING (programid)";

    // Filter file extentions
    $SQL .= " WHERE basename LIKE '%.mp4'";
    $SQL .=    " OR basename LIKE '%.m4v'";
    $SQL .=    " OR basename LIKE '%.mov'";

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

function build_xml_vid( $sql_result, $index )
{
    $xml = '';

    require 'settings.php';

    while ( $db_field = mysql_fetch_assoc($sql_result) )
    {
        $filename = $db_field['filename'];

        $contentType = "movie";
        $episode     = "";
        if ( 0 < $db_field['season'] )
        {
            $contentType = "episode";
            $episode     = $db_field['season'] . "-" . $db_field['episode'];
        }

        $hdimg = html_encode($db_field['coverfile']);
        $sdimg = $hdimg;

        $quality = $RokuDisplayType;
        $isHD    = 'false';
        if ( 'HD' == $quality ) { $isHD = 'true'; }

        $SQL  = "SELECT * FROM videometadatagenre, videogenre ";
        $SQL .= "WHERE videometadatagenre.idgenre = videogenre.intid ";
        $SQL .= "AND idvideo='{$db_field['intid']}'";
        $genre_result = mysql_query($SQL);
        $genre_arr = array();
        while ( $genres = mysql_fetch_assoc($genre_result) )
        {
            array_push($genre_arr, $genres['genre']);
        }
        $genre = implode( ", ", $genre_arr );

        $args = array(
            'contentType' => $contentType,
            'title'       => html_cleanup($db_field['title']),
            'subtitle'    => html_cleanup($db_field['subtitle']),
            'synopsis'    => html_cleanup($db_field['plot']),
            'hdImg'       => "$MythRokuDir/image.php?image=" . html_cleanup($hdimg),
            'sdImg'       => "$MythRokuDir/image.php?image=" . html_cleanup($sdimg),
            'streamBitrate'   => 0,
            'streamUrl'       => "$mythtvdata/video/" . html_encode($filename),
            'streamQuality'   => $quality,
            'streamContentId' => html_cleanup($filename),
            'streamFormat'    => pathinfo($filename, PATHINFO_EXTENSION),
            'isHD'        => $isHD,
            'episode'     => html_cleanup($episode),
            'genres'      => html_cleanup($genre),
            'runtime'     => $db_field['length'] * 60,
            'date'        => date("m/d/Y", convert_date($db_field['releasedate'])),
            'starRating'  => $db_field['userrating'] * 10,
            'rating'      => $db_field['rating'],
            'index'       => $index,
            'isRecording' => 'false',
            'delCmd'      => ''
        );

        $xml .= xml_file( $args );

        $index++;
    }

    return $xml;
}

function build_xml_rec( $sql_result, $index )
{
    $xml = '';

    require 'settings.php';

    while ( $db_field = mysql_fetch_assoc($sql_result) )
    {
        $filename = $db_field['basename'];

        $str_time = convert_datetime($db_field['start_time']);
        $end_time = convert_datetime($db_field['end_time']);

        $chanid_strtime = "{$db_field['chanid']}/$str_time";

        $contentType = "movie";
        $episode     = "";
        if ( 'series' == $db_field['category_type'] )
        {
            $contentType = "episode";
            $episode     = $db_field['syndicatedepisodenumber'];
        }

        $img  = "{$db_field['hostname']}/$chanid_strtime";
        $hdimg = "$img/100/56/-1/$filename.100x56x-1.png";
        $sdimg = "$img/100/75/-1/$filename.100x75x-1.png";

        $quality = $RokuDisplayType;
        $isHD    = 'false';
#       if ( '0' !== $db_field['hdtv'] ) { $quality = 'HD'; }
        if ( 'HD' == $quality ) { $isHD = 'true'; }

        $args = array(
            'contentType' => $contentType,
            'title'       => html_cleanup($db_field['title']),
            'subtitle'    => html_cleanup($db_field['subtitle']),
            'synopsis'    => html_cleanup($db_field['description']),
            'hdImg'       => "$WebServer/tv/get_pixmap/" . html_cleanup($hdimg),
            'sdImg'       => "$WebServer/tv/get_pixmap/" . html_cleanup($sdimg),
            'streamBitrate'   => 0,
            'streamUrl'       => "$WebServer/pl/stream/" . html_encode($chanid_strtime),
            'streamQuality'   => $quality,
            'streamContentId' => html_cleanup($filename),
            'streamFormat'    => pathinfo($filename, PATHINFO_EXTENSION),
            'isHD'        => $isHD,
            'episode'     => html_cleanup($episode),
            'genres'      => html_cleanup($db_field['category']),
            'runtime'     => $end_time - $str_time,
            'date'        => date("m/d/Y h:ia", $str_time),
            'starRating'  => 0,
            'rating'      => '',
            'index'       => $index,
            'isRecording' => 'true',
            'delCmd'      => "$MythRokuDir/mythtv_tv_del.php?basename=" . html_encode($filename)
        );

        $xml .= xml_file( $args );

        $index++;
    }

    return $xml;
}

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

?>
