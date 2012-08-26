<?php

// Get the local info from the settings file
require_once('./settings.php');
include('resizeimage.php');

if ( isset($_GET['image']) )
{
    $file = rawurlencode( $_GET['image'] );

    $image = new SimpleImage();

    do
    {
        // Look for video cover files
        $rc = $image->load( $mythtvdata . "/video_covers/" . $file );
        if ( $rc ) break;

        // Look for video screen shots
        $rc = $image->load( $mythtvdata . "/video_screenshots/" . $file );
        if ( $rc ) break;

        // Look for recording screen shots
        $rc = $image->load( $mythtvdata . "/recordings/" . $file );
        if ( $rc ) break;

    } while (0);

    $image->resizeToWidth(250);
    $image->output();
}

?>
