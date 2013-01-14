<?php

require_once 'globals.php';
require_once 'settings.php';
include      'resizeimage.php';

// Required inputs:
//      group -> The storage group name in which the image exists.
//      file  -> File name of the image.

if ( isset($_GET['group']) and isset($_GET['file']) )
{
    $path = $GLOBALS['g_storageGroups'][$_GET['group']];
    $file  = rawurldecode( $_GET['file'] );

    $image = new SimpleImage();

    $rc = $image->load( $path . '/' . $file );
    if ( $rc )
    {
        $image->resizeToWidth(250);
        $image->output();
    }
}

?>
