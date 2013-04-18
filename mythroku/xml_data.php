<?php

require_once 'globals.php';
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

        $sort['path'] = '';
        if ( isset($_GET['sort']['path']) )
        {
            $sort['path'] = $_GET['sort']['path'];
        }

        if ( 'series' == $sort['type'] )
        {
            $sort['season'] = '';
            if ( isset($_GET['sort']['season']) )
            {
                $sort['season'] = $_GET['sort']['season'];
            }

            $sort['legacy'] = '';
            if ( isset($_GET['sort']['legacy']) )
            {
                $sort['legacy'] = $_GET['sort']['legacy'];
            }
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
    $SQL = <<<EOF
SELECT A.chanid,
       A.starttime AS actualStartTime,
       A.endtime   AS actualEndTime,
       A.title,
       A.subtitle,
       A.description,
       A.season,
       A.episode,
       A.category,
       A.programid,
       A.recgroup,
       A.stars,
       A.basename,
       A.watched,
       A.storagegroup,
       A.bookmarkupdate,
       B.starttime AS scheduledStartTime,
       B.endtime   AS scheduledEndTime,
       B.category_type,
       B.hdtv,
       B.syndicatedepisodenumber,
       C.rating,
       D.dirname
FROM recorded A
    INNER JOIN recordedprogram B
        ON A.programid = B.programid
    LEFT OUTER JOIN recordedrating C
        ON B.starttime = C.starttime AND B.chanid = C.chanid
    LEFT OUTER JOIN storagegroup D
        ON A.storagegroup = D.groupname

EOF;

    // Filter for a single series, if needed.
    if ( 'series' == $_GET['sort']['type'] )
    {
        $SQL .= " WHERE B.category_type = 'series'";
        $SQL .= " AND A.title = \"{$_GET['sort']['path']}\"";
    }
    else if ( 'file' == $_GET['sort']['type'] )
    {
        $SQL .= " WHERE A.basename LIKE \"{$_GET['sort']['path']}%\"";
    }

    // Add sorting. Title and genre sorting done later.
    switch ( $_GET['sort']['type'] )
    {
        case 'date':     $SQL .= " ORDER BY A.starttime"; break;
        case 'channel':  $SQL .= " ORDER BY A.chanid";    break;
        case 'recgroup': $SQL .= " ORDER BY A.recgroup";  break;
        case 'file':     $SQL .= " ORDER BY A.basename";  break;
    }

    return $SQL;
}

//------------------------------------------------------------------------------

function build_sql_vid()
{
    // Start building SQL query
    $SQL = <<<EOF
SELECT A.*,
       B.dirname
FROM videometadata A
    LEFT OUTER JOIN storagegroup B
        ON B.groupname = 'Videos'

EOF;

    // Filter file extentions
    $SQL .= <<<EOF
WHERE ( A.filename LIKE '%.mp4' OR
        A.filename LIKE '%.m4v' OR
        A.filename LIKE '%.mov' )

EOF;

    // Filter for a single series, if needed.
    if ( 'series' == $_GET['sort']['type'] )
    {
        if ( $GLOBALS['g_isDbVer25'] )
        {
            $SQL .= " AND A.contenttype = 'TELEVISION'";
        }
        else
        {
            $SQL .= " AND A.season > 0";
        }

        $SQL .= " AND A.title = \"{$_GET['sort']['path']}\"";
    }
    else if ( 'file' == $_GET['sort']['type'] )
    {
        $SQL .= " AND A.filename LIKE \"{$_GET['sort']['path']}%\"";
    }

    // Add sorting. Title and genre sorting done later.
    switch ( $_GET['sort']['type'] )
    {
        case 'date':  $SQL .= " ORDER BY A.releasedate"; break;
        case 'file':  $SQL .= " ORDER BY A.filename"; break;
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

        if ( 'rec' == $_GET['type'] )
        {
            $file = findTransRecFile( $db_field['basename'],
                                      $db_field['dirname'] );
            if ( !$file['trans'] )
            {
                continue; // not playable because not transcoded.
            }

            if ( checkPendingJobs($db_field['chanid'],
                                  $db_field['actualStartTime']) )
            {
                continue; // not playable because of pending jobs.
            }

            $data = build_data_array_rec( $db_field, $file );
        }
        else if ( 'vid' == $_GET['type'] )
        {
            $data = build_data_array_vid( $db_field );
        }

        // If the sort type is 'series' then the list should only contain files
        // in that series.
        // If the sort type is 'file' then the list should only contain files
        // in the subdirectory specified in the sort path.
        if ( 'series' == $_GET['sort']['type'] or
             'file'   == $_GET['sort']['type'] )
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
                                  'sdImg'      => $data['sdImgs']['poster'],
                                  'genres'     => $data['genres'], );

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

function build_data_array_rec( $db_field, $file )
{
    require 'settings.php';

    $str_time = convert_datetime($db_field['actualStartTime']);
    $end_time = convert_datetime($db_field['actualEndTime']  );

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

    $img_script = "$MythRokuDir/image.php?";
    $imgs = array();
    $imgs['poster'] = "$MythRokuDir/images/Mythtv_movie.png";
    $tmp = array('group' => $db_field['storagegroup'], 'file' => $file['pic']);
    $imgs['screen'] = $img_script . http_build_query($tmp);

    $stream = array(
        'bitrate'   => 0,
        'url'       => "$mythtvdata/recordings/" . html_encode($file['file']),
        'contentId' => $file['base'],
        'format'    => $file['ext'],
    );

    $data = array(
        'itemType'     => 'file',
        'itemId'       => $db_field['programid'],
        'title'        => $db_field['title'],
        'subtitle'     => $db_field['subtitle'],
        'hdImgs'       => $imgs,
        'sdImgs'       => $imgs,
        'synopsis'     => $db_field['description'],
        'contentType'  => $contentType,
        'episode'      => $episode,
        'genres'       => array( $db_field['category'] ),
        'runtime'      => $end_time - $str_time,
        'date'         => date("m/d/Y h:ia", $str_time),
        'year'         => date("Y",          $str_time),
        'starRating'   => 0,
        'rating'       => $db_field['rating'],
        'isRecording'  => true,
        'isTranscoded' => $file['trans'],
        'delCmd'       => "$MythRokuDir/mythtv_tv_del.php?basename=" . html_encode($file['file']),
        'hdStream'     => $stream,
        'sdStream'     => $stream,
        'path'         => $file['path'],
    );

    return $data;
}

//------------------------------------------------------------------------------

function build_data_array_vid( $db_field )
{
    require 'settings.php';

    $filename = $db_field['filename'];
    $path_parts = pathinfo($filename);

    $contentType = 'movie';
    $episode     = array();
    if ( (!$GLOBALS['g_isDbVer25'] && 0 < $db_field['season']) ||
         ( $GLOBALS['g_isDbVer25'] && 'TELEVISION' == $db_field['contenttype']))
    {
        $contentType = 'episode';
        $episode['season']  = $db_field['season'];
        $episode['episode'] = $db_field['episode'];
    }

    $img_script = "$MythRokuDir/image.php?";
    $imgs = array();
    $tmp = array('group' => 'Coverart', 'file' => $db_field['coverfile']);
    $imgs['poster'] = $img_script . http_build_query($tmp);
    $tmp = array('group' => 'Screenshots', 'file' => $db_field['screenshot']);
    $imgs['screen'] = ( 'movie' == $contentType )
                          ? $imgs['poster']
                          : $img_script . http_build_query($tmp);

    $releasedate = convert_date($db_field['releasedate']);

    $stream = array(
        'bitrate'   => 0,
        'url'       => "$mythtvdata/video/" . html_encode($filename),
        'contentId' => $path_parts['basename'],
        'format'    => $path_parts['extension'],
    );

    $path = $path_parts['dirname'] . '/';
    if ( 0 == strcmp('./', $path) ) { $path = ''; }

    $data = array(
        'itemType'     => 'file',
        'itemId'       => $db_field['intid'],
        'title'        => $db_field['title'],
        'subtitle'     => $db_field['subtitle'],
        'hdImgs'       => $imgs,
        'sdImgs'       => $imgs,
        'synopsis'     => $db_field['plot'],
        'contentType'  => $contentType,
        'episode'      => $episode,
        'genres'       => $GLOBALS['g_vidGenres'][$db_field['intid']],
        'runtime'      => $db_field['length'] * 60,
        'date'         => date("m/d/Y", $releasedate),
        'year'         => date("Y",     $releasedate),
        'starRating'   => $db_field['userrating'] * 10,
        'rating'       => $db_field['rating'],
        'isRecording'  => false,
        'isTranscoded' => true,
        'delCmd'       => '',
        'hdStream'     => $stream,
        'sdStream'     => $stream,
        'path'         => $path,
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

//------------------------------------------------------------------------------

function findTransRecFile( $filename, $storagegroup )
{
    $path_parts = pathinfo($filename);

    $ext = $path_parts['extension'];

    $path = $path_parts['dirname'] . '/';
    if ( 0 == strcmp('./', $path) ) { $path = ''; }

    $fileNoExt = "$path{$path_parts['filename']}";
    $pathNoExt = "$storagegroup/$fileNoExt";

    // Try to find a transcoded version of the recording.
    $newExt = '';
    $isTranscoded = true;
    if      ( file_exists("$pathNoExt.m4v") ) $newExt = 'm4v';
    else if ( file_exists("$pathNoExt.mp4") ) $newExt = 'mp4';
    else if ( file_exists("$pathNoExt.mov") ) $newExt = 'mov';

    if ( !$newExt )
    {
        $newExt = $ext;
        $isTranscoded = false;
    }

    // Try to find the screen shot.
    $pic = '';
    if      ( file_exists("$pathNoExt.$ext.png") ) $pic = "$fileNoExt.$ext.png";
    else if ( file_exists("$pathNoExt.m4v.png")  ) $pic = "$fileNoExt.m4v.png";
    else if ( file_exists("$pathNoExt.mp4.png")  ) $pic = "$fileNoExt.mp4.png";
    else if ( file_exists("$pathNoExt.mov.png")  ) $pic = "$fileNoExt.mov.png";

    return array( 'file'  => "$fileNoExt.$newExt",
                  'path'  => $path,
                  'base'  => $fileNoExt,
                  'ext'   => $newExt,
                  'pic'   => $pic,
                  'trans' => $isTranscoded, );
}

//------------------------------------------------------------------------------

function checkPendingJobs( $chanid, $actualStartTime )
{
    $SQL = <<<EOF
SELECT status FROM jobqueue
WHERE chanid = '$chanid' AND starttime = '$actualStartTime'
EOF;

    $result = mysql_query($SQL);

    while ( $db_field = mysql_fetch_assoc($result) )
    {
        // A status of 0x100 or greater indicates that the job is done whether
        // is finished successfully or not (from jobqueue.h).
        if ( 0x100 > $db_field['status'] )
        {
            return true;
        }
    }

    return false;
}

?>
