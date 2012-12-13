<?php

require_once 'settings.php';

//------------------------------------------------------------------------------

// Make a connection to the mySQL server
$db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
$db_found  = mysql_select_db($MythTVdb, $db_handle);
if ( !$db_found )
{
    die( 'Database NOT found: ' . mysql_error() );
}

// Remove recording, if possible.
if ( isset($_GET['basename']) )
{
    $path_parts = pathinfo( $_GET['basename'] );

    $path = $path_parts['dirname'] . '/';
    if ( 0 == strcmp('./', $path) ) { $path = ''; }

    $path_no_ext = "$path{$path_parts['filename']}";

    // Find file's storage group.
    $SQL = <<<EOF
SELECT A.chanid,
       A.starttime AS act_start,
       B.starttime AS sch_start,
       D.dirname
FROM recorded A
    INNER JOIN recordedprogram B
        ON A.programid = B.programid
    LEFT OUTER JOIN recordedrating C
        ON B.starttime = C.starttime AND B.chanid = C.chanid
    LEFT OUTER JOIN storagegroup D
        ON A.storagegroup = D.groupname
WHERE A.basename LIKE '$path_no_ext.%'
EOF;

    $sql_result    = mysql_query( $SQL );
    $db_field      = mysql_fetch_assoc( $sql_result );
    $storage_group = $db_field['dirname'];

    // Delete associated files.
    $files = glob( "$storage_group/$path_no_ext.*" );
    array_walk( $files, 'myunlink' );

    // Delete entry from database.
    $tmp =        "chanid    = '{$db_field['chanid']}' AND ";
    $act = $tmp . "starttime = '{$db_field['act_start']}'";
    $sch = $tmp . "starttime = '{$db_field['sch_start']}'";
    mysql_query( "DELETE FROM recorded        WHERE $act" );
    mysql_query( "DELETE FROM recordedcredits WHERE $sch" );
    mysql_query( "DELETE FROM recordedmarkup  WHERE $act" );
    mysql_query( "DELETE FROM recordedprogram WHERE $sch" );
    mysql_query( "DELETE FROM recordedrating  WHERE $sch" );
    mysql_query( "DELETE FROM recordedseek    WHERE $act" );
}

// Close mySQL pointer
mysql_close($db_handle);

//------------------------------------------------------------------------------

function myunlink($t)
{
    print "Deleting file: $t <br/>";
    if ( !unlink($t) )
    {
        $err = error_get_last();
        die( "{$err['message']}" );
    }
}

?>

