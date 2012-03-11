<?php

require_once './settings.php';

$script_vid = "$MythRokuDir/mythtv_xml.php?type=vid";
$script_rec = "$MythRokuDir/mythtv_xml.php?type=rec";

print <<<EOF
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<categories>

    <category title="Recordings" description="" sd_img="$MythRokuDir/images/Mythtv_tv.png" hd_img="$MythRokuDir/images/Mythtv_tv.png">
        <categoryLeaf title="Title"   description="" feed="$script_rec&amp;sort=title" />
        <categoryLeaf title="Date"    description="" feed="$script_rec&amp;sort=date" />
        <categoryLeaf title="Channel" description="" feed="$script_rec&amp;sort=channel" />
        <categoryLeaf title="Genre"   description="" feed="$script_rec&amp;sort=genre" />
        <categoryLeaf title="Group"   description="" feed="$script_rec&amp;sort=recgroup" />
    </category>

    <category title="Videos" description="" sd_img="$MythRokuDir/images/Mythtv_movie.png" hd_img="$MythRokuDir/images/Mythtv_movie.png">
        <categoryLeaf title="Title" description="" feed="$script_vid&amp;sort=title" />
        <categoryLeaf title="Genre" description="" feed="$script_vid&amp;sort=genre" />
        <categoryLeaf title="Date"  description="" feed="$script_vid&amp;sort=date" />
    </category>

</categories>

EOF;

?>
