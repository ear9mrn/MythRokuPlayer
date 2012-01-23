<?php

require_once './settings.php';

print <<<EOF
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<categories>

    <!-- banner_ad: optional element which displays an add at the top level category screen -->
    <banner_ad sd_img="$MythRokuDir/images/mythtv_logo_SD.png" hd_img="$MythRokuDir/images/mythtv_logo_SD.png" />

    <category title="TV" description="MythTV TV" sd_img="$MythRokuDir/images/Mythtv_tv.png" hd_img="$MythRokuDir/images/Mythtv_tv.png">
	<categoryLeaf title="Record Group" description="" feed="$MythRokuDir/mythtv_tv_xml.php?sort=recgroup" />
	<categoryLeaf title="Date" description="" feed="$MythRokuDir/mythtv_tv_xml.php?sort=date" /> 
	<categoryLeaf title="Title" description="" feed="$MythRokuDir/mythtv_tv_xml.php?sort=title" /> 
	<categoryLeaf title="Genre" description="" feed="$MythRokuDir/mythtv_tv_xml.php?sort=genre" /> 
	<categoryLeaf title="Channel" description="" feed="$MythRokuDir/mythtv_tv_xml.php?sort=channel" /> 
    </category>

    <category title="Movies" description="MythTV Movies" sd_img="$MythRokuDir/images/Mythtv_movie.png" hd_img="$MythRokuDir/images/Mythtv_movie.png">
	<categoryLeaf title="Title" description="" feed="$MythRokuDir/mythtv_movies_xml.php?sort=title" /> 
	<categoryLeaf title="Genre" description="" feed="$MythRokuDir/mythtv_movies_xml.php?sort=genre" /> 
	<categoryLeaf title="Year" description="" feed="$MythRokuDir/mythtv_movies_xml.php?sort=year" /> 
    </category>

    <category title="Settings" description="Roku MythTV Settings" sd_img="$MythRokuDir/images/Mythtv_settings.png" hd_img="$MythRokuDir/images/Mythtv_settings.png">
	<categoryLeaf title="Settings" description="" feed="$MythRokuDir/mythtv_tv.xml"/> 
    </category>

</categories>

EOF;

?>
