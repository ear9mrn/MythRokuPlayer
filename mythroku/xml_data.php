<?php

include 'sort_utils.php';

$g_isDbVer25 = false;
$g_vidGenres = array();

function get_xml_data()
{
    $xml = '';

    require 'settings.php';
    require 'xml_utils.php';

    // Get any parameters from $_GET
    parse_parms();

    // Make a connection to the mySQL server
    $db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
    $db_found  = mysql_select_db($MythTVdb, $db_handle);
    if ( !$db_found )
    {
        die( 'Database NOT found: ' . mysql_error() );
    }

    // Populate the global variables
    // The intent is to query the database for this data only once instead of
    // for each file.
    $GLOBALS['g_isDbVer25'] = isDbVer25();
    if ( 'vid' == $_GET['type'] )
    {
        $GLOBALS['g_vidGenres'] = getVidGenres();
    }

    // Query SQL database and get data array.
    $sql_query  = build_sql();
    $sql_result = mysql_query( $sql_query );
    $data_array = build_data_array( $sql_result );
    sort_data_array( $data_array, $_GET['sort'] );

    // Check boundry limits
    $total_rows = count( $data_array );
    if ( $total_rows < $_GET['index'] )
    {
        $_GET['index'] = $total_rows;
    }

    // Limit the number results
    if ( 0 != $ResultLimit )
    {
        $data_array = array_slice( $data_array, $_GET['index']-1,
                                   $ResultLimit );
    }

    // Get the subset result count
    $result_rows = count( $data_array );

    // Start XML feed
    $args = array( 'start_row'   => $_GET['index'],
                   'result_rows' => $result_rows,
                   'total_rows'  => $total_rows,
                   'list_type'   => $_GET['type'] );
    $xml .= xml_start_feed( $args );

    // Print 'previous' pueso-directory
    $args = array( 'start_row'  => $_GET['index'],
                   'html_parms' => $_GET );
    $xml .= xml_start_dir( $args );

    // Translate all items into xml
    $index = $_GET['index'];
    foreach ( $data_array as $key => $data )
    {
        $data['index'] = $index;
        switch ( $data['itemType'] )
        {
            case 'file': $xml .= xml_file( $data ); break;
            case 'dir':  $xml .= xml_dir(  $data ); break;
        }
        $index++;
    }

    // Print 'next' pueso-directory
    $args = array( 'start_row'   => $_GET['index'],
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
// Parse parameters
//------------------------------------------------------------------------------

function parse_parms()
{
    // type
    switch ( $_GET['type'] )
    {
        case 'rec': case 'vid': break;
        default: die( "Invalid parameter: [type]=[${_GET['type']}]" );
    }

    // sort
    $sort = array();
    if ( isset($_GET['sort']['type']) )
    {
        $sort['type'] = $_GET['sort']['type'];

        switch ( $sort['type'] )
        {
            case 'title':
            case 'genre':
                $sort['path']    = $_GET['sort']['path'];
                break;
            case 'series':
                $sort['path']    = $_GET['sort']['path'];
                $sort['season']  = $_GET['sort']['season'];
                $sort['legacy']  = $_GET['sort']['legacy'];
                break;
        }
    }
    else
    {
        $sort['type'] = 'title'; // Default
    }
    $_GET['sort'] = $sort;

    // index
    if ( !isset($_GET['index']) or (1 > $_GET['index']) )
    {
        $_GET['index'] = 1; // Default
    }

    // test
    // Nothing to do so far.
}

//------------------------------------------------------------------------------
// Build SQL queries
//------------------------------------------------------------------------------

function build_sql()
{
    switch ( $_GET['type'] )
    {
        case 'rec': return build_sql_rec();
        case 'vid': return build_sql_vid();
    }
}

//------------------------------------------------------------------------------

function build_sql_rec()
{
    // Start building SQL query
    $SQL  = "SELECT * FROM recorded ";
    $SQL .= "INNER JOIN recordedprogram USING (programid)";

    // Filter file extentions
    $SQL .= " WHERE ( basename LIKE '%.mp4'";
    $SQL .=      " OR basename LIKE '%.m4v'";
    $SQL .=      " OR basename LIKE '%.mov' )";

    // Filter for a single series, if needed.
    if ( 'series' == $_GET['sort']['type'] )
    {
        $SQL .= " AND recorded.title = '{$_GET['sort']['path']}'";
    }

    // Add sorting. Title and genre sorting done later.
    switch ( $_GET['sort']['type'] )
    {
        case 'date':     $SQL .= " ORDER BY recorded.starttime"; break;
        case 'channel':  $SQL .= " ORDER BY recorded.chanid";    break;
        case 'recgroup': $SQL .= " ORDER BY recorded.recgroup";  break;
    }

    return $SQL;
}

//------------------------------------------------------------------------------

function build_sql_vid()
{
    // Start building SQL query
    $SQL = "SELECT * FROM videometadata";

    // Filter file extentions
    $SQL .= " WHERE ( filename LIKE '%.mp4'";
    $SQL .=      " OR filename LIKE '%.m4v'";
    $SQL .=      " OR filename LIKE '%.mov' )";

    // Filter for a single series, if needed.
    if ( 'series' == $_GET['sort']['type'] )
    {
        $SQL .= " AND contenttype = 'TELEVISION'";
        $SQL .= " AND title       = '{$_GET['sort']['path']}'";
    }

    // Add sorting. Title and genre sorting done later.
    switch ( $_GET['sort']['type'] )
    {
        case 'date':  $SQL .= " ORDER BY releasedate"; break;
    }

    return $SQL;
}

//------------------------------------------------------------------------------
// Build data array
//------------------------------------------------------------------------------

function build_data_array( $sql_result )
{
    $data_array = array();

    while ( $db_field = mysql_fetch_assoc($sql_result) )
    {
        $data = array();

        switch ( $_GET['type'] )
        {
            case 'rec': $data = build_data_array_rec( $db_field ); break;
            case 'vid': $data = build_data_array_vid( $db_field ); break;
        }

        // If the sort type is 'series' then the list should only contain files
        // in that serires.
        if ( 'series' == $_GET['sort']['type'] )
        {
            array_push( $data_array, $data );
        }
        else
        {
            if ( 'episode' == $data['contentType'] )
            {
                // Add a directory for this series if it does not already exist.
                $title = $data['title'];
                if ( !isset($data_array[$title]) )
                {
                    $dir = array( 'itemType'   => 'dir',
                                  'title'      => $title,
                                  'html_parms' => $_GET,
                                  'hdImg'      => $data['hdImgs']['poster'],
                                  'sdImg'      => $data['sdImgs']['poster'], );

                    $dir['html_parms']['index'] = 1;

                    $sort = array( 'type' => 'series',
                                   'path' => $title );

                    $dir['html_parms']['sort'] = $sort;

                    $data_array[$title] = $dir;
                }
            }
            else
            {
                array_push( $data_array, $data );
            }
        }
    }

    return $data_array;
}

//------------------------------------------------------------------------------

function build_data_array_rec( $db_field )
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

        $episode['season']  = 0;
        $episode['episode'] = 0;
        $episode['legacy']  = $db_field['syndicatedepisodenumber'];

        if ( $GLOBALS['g_isDbVer25']  and
             0 < $db_field['season']  and
             0 < $db_field['episode'] )
        {
            $episode['season']  = $db_field['season'];
            $episode['episode'] = $db_field['episode'];
        }
    }

    $poster_path = "$MythRokuDir/images/";
    $img_script  = "$MythRokuDir/image.php?image=";
    $imgs = array();
    $imgs['poster'] = $poster_path . html_encode("Mythtv_movie.png");
    $imgs['screen'] = $img_script  . html_encode("$filename.png");

    $stream = array(
        'bitrate'   => 0,
        'url'       => "$WebServer/pl/stream/" . html_encode($chanid_strtime),
        'contentId' => html_cleanup($filename),
        'format'    => pathinfo($filename, PATHINFO_EXTENSION),
    );

    // TODO: You can find the TV rating in table 'recordedrating'

    $data = array(
        'itemType'    => 'file',
        'title'       => html_cleanup($db_field['title']),
        'subtitle'    => html_cleanup($db_field['subtitle']),
        'hdImgs'      => $imgs,
        'sdImgs'      => $imgs,
        'synopsis'    => html_cleanup($db_field['description']),
        'contentType' => $contentType,
        'episode'     => $episode,
        'genres'      => array( html_cleanup($db_field['category']) ),
        'runtime'     => $end_time - $str_time,
        'date'        => date("m/d/Y h:ia", $str_time),
        'starRating'  => 0,
        'rating'      => '',
        'isRecording' => 'true',
        'delCmd'      => "$MythRokuDir/mythtv_tv_del.php?basename=" . html_encode($filename),
        'hdStream'    => $stream,
        'sdStream'    => $stream,
    );

    return $data;
}

//------------------------------------------------------------------------------

function build_data_array_vid( $db_field )
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

    $img_script = "$MythRokuDir/image.php?image=";
    $imgs = array();
    $imgs['poster'] = $img_script . html_encode($db_field['coverfile']);
    $imgs['screen'] = ( 'movie' == $contentType )
                          ? $imgs['poster']
                          : $img_script . html_encode($db_field['screenshot']);

    $stream = array(
        'bitrate'   => 0,
        'url'       => "$mythtvdata/video/" . html_encode($filename),
        'contentId' => html_cleanup($filename),
        'format'    => pathinfo($filename, PATHINFO_EXTENSION),
    );

    $data = array(
        'itemType'    => 'file',
        'title'       => html_cleanup($db_field['title']),
        'subtitle'    => html_cleanup($db_field['subtitle']),
        'hdImgs'      => $imgs,
        'sdImgs'      => $imgs,
        'synopsis'    => html_cleanup($db_field['plot']),
        'contentType' => $contentType,
        'episode'     => $episode,
        'genres'      => $GLOBALS['g_vidGenres'][$db_field['intid']],
        'runtime'     => $db_field['length'] * 60,
        'date'        => date("m/d/Y", convert_date($db_field['releasedate'])),
        'starRating'  => $db_field['userrating'] * 10,
        'rating'      => $db_field['rating'],
        'isRecording' => 'false',
        'delCmd'      => '',
        'hdStream'    => $stream,
        'sdStream'    => $stream,
    );

    return $data;
}

//------------------------------------------------------------------------------
// Utility functions
//------------------------------------------------------------------------------

function convert_date( $date )
{
    list($year, $month, $day) = explode('-', $date);

    if ( 0 == $year  ) { $year  = 1900; }
    if ( 0 == $month ) { $month = 1;    }
    if ( 0 == $day   ) { $day   = 1;    }

    $timestamp = mktime(0, 0, 0, $month, $day, $year);

    return $timestamp;
}

function convert_datetime( $datetime )
{
    list($date, $time)            = explode(' ', $datetime);
    list($year, $month,  $day)    = explode('-', $date);
    list($hour, $minute, $second) = explode(':', $time);

    if ( 0 == $year  ) { $year  = 1900; }
    if ( 0 == $month ) { $month = 1;    }
    if ( 0 == $day   ) { $day   = 1;    }

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

function getVidGenres()
{
    $genre_arr = array();

    $SQL  = "SELECT idvideo, genre FROM videometadatagenre, videogenre ";
    $SQL .= "WHERE videometadatagenre.idgenre = videogenre.intid ";
    $genre_result = mysql_query($SQL);

    while ( $db_field = mysql_fetch_assoc($genre_result) )
    {
        $idvideo = $db_field['idvideo'];
        $genre   = $db_field['genre'];

        if ( !$genre_arr[$idvideo] ) { $genre_arr[$idvideo] = array(); }
        array_push( $genre_arr[$idvideo], $genre );
    }

    return $genre_arr;
}

?>
