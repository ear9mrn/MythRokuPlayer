<?php

$WebServer = "http://192.168.1.8/mythweb";    // include path to mythweb eg, http://yourip/mythweb

$MythRokuDir = $WebServer . "/mythroku";    // name of your mythroku directory in the mythweb folder
$mythtvdata  = $WebServer . "/data";        // relative path to mythtv data

$RokuDisplayType = "HD";    // set to the same as your Roku player under display type, HD or SD
                            // use SD if playing to myltiple devices if any are SD

$MysqlServer  = "192.168.1.8";  // mysql server ip/name
$MythTVdb     = "mythconverg";  // mythtv database name
$MythTVdbuser = "mythtv";       // mythtv database user
$MythTVdbpass = "mythtv";       // mythtv database password

$ResultLimit = 50;  // Maximum number of elements to be displayed at one time. Use 0 for no limit.

$useUTC = true;  //true if MythTV version is 0.26 or newer
?>
