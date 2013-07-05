<?php

function opendb()
{
    require 'settings.php';

    // Make a connection to the mySQL server
    $db_handle = mysql_connect( $MysqlServer, $MythTVdbuser, $MythTVdbpass );
    $db_found  = mysql_select_db( $MythTVdb, $db_handle );
    if ( !$db_found )
    {
        die( 'Database NOT found: ' . mysql_error() );
    }

    return $db_handle;
}

//------------------------------------------------------------------------------

function closedb( $db_handle )
{
    // Close mySQL pointer
    mysql_close( $db_handle );
}

?>
