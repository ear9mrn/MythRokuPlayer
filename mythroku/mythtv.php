<?php

require_once './settings.php';

$script_vid = htmlspecialchars("$MythRokuDir/mythtv_xml.php?type=vid&sort");
$script_rec = htmlspecialchars("$MythRokuDir/mythtv_xml.php?type=rec&sort");

print <<<EOF
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<categories>

    <category title="Recordings" description="" sd_img="$MythRokuDir/images/Mythtv_tv.png" hd_img="$MythRokuDir/images/Mythtv_tv.png">
        <categoryLeaf title="Title"   description="" feed="$script_rec=title" />
        <categoryLeaf title="Date"    description="" feed="$script_rec=date" />
        <categoryLeaf title="Channel" description="" feed="$script_rec=channel" />
        <categoryLeaf title="Genre"   description="" feed="$script_rec=genre" />
        <categoryLeaf title="Group"   description="" feed="$script_rec=recgroup" />
    </category>

    <category title="Videos" description="" sd_img="$MythRokuDir/images/Mythtv_movie.png" hd_img="$MythRokuDir/images/Mythtv_movie.png">
        <categoryLeaf title="Title" description="" feed="$script_vid=title" />
        <categoryLeaf title="Genre" description="" feed="$script_vid=genre" />
        <categoryLeaf title="Date"  description="" feed="$script_vid=date" />
    </category>

    <category title="Settings" description="Roku MythTV Settings" sd_img="$MythRokuDir/images/Mythtv_settings.png" hd_img="$MythRokuDir/images/Mythtv_settings.png">
		<categoryLeaf title="Settings" description="" feed=""/> 
    </category>
    		
</categories>

EOF;

?>
