<?php

//------------------------------------------------------------------------------
// NOTE: When including this file, you should use 'require_once' so the code
//       in this file does not get multiple times.
//------------------------------------------------------------------------------

require_once 'settings.php';

//------------------------------------------------------------------------------
// Global variables
//------------------------------------------------------------------------------

// This is an array of storage groups, where the key is the storage group name
// and the value is the storage group directory.
$g_storageGroups = getStorageGroups();

//------------------------------------------------------------------------------
// Supporting functions
//------------------------------------------------------------------------------

function getStorageGroups()
{
    $o_storageGroups = array();

    // Open SQL connection
    $db_handle = mysql_connect( $GLOBALS['MysqlServer'],
                                $GLOBALS['MythTVdbuser'],
                                $GLOBALS['MythTVdbpass'] );
    $db_found  = mysql_select_db( $GLOBALS['MythTVdb'], $db_handle );
    if ( !$db_found )
    {
        die( 'Database NOT found: ' . mysql_error() );
    }

    // Get the list of storage groups
    $g_storageGroups = array();
    $sql_query = "SELECT groupname, dirname FROM storagegroup";
    $sql_result = mysql_query( $sql_query );
    while ( $db_field = mysql_fetch_assoc($sql_result) )
    {
        $o_storageGroups[$db_field['groupname']] = $db_field['dirname'];
    }

    // Close SQL connection
    mysql_close( $db_handle );

    return $o_storageGroups;
}

?>
