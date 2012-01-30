<?php

require_once './settings.php';

print <<<EOF
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<categories>

    <!-- banner_ad: optional element which displays an add at the top level category screen -->
    <banner_ad sd_img="$MythRokuDir/images/mythtv_logo_SD.png" hd_img="$MythRokuDir/images/mythtv_logo_SD.png" />

    <category title="Recordings" description="" sd_img="$MythRokuDir/images/Mythtv_tv.png" hd_img="$MythRokuDir/images/Mythtv_tv.png">
	<categoryLeaf title="Title"   description="" feed="$MythRokuDir/mythtv_tv_xml.php?sort=title" /> 
	<categoryLeaf title="Date"    description="" feed="$MythRokuDir/mythtv_tv_xml.php?sort=date" /> 
	<categoryLeaf title="Channel" description="" feed="$MythRokuDir/mythtv_tv_xml.php?sort=channel" /> 
	<categoryLeaf title="Genre"   description="" feed="$MythRokuDir/mythtv_tv_xml.php?sort=genre" /> 
	<categoryLeaf title="Group"   description="" feed="$MythRokuDir/mythtv_tv_xml.php?sort=recgroup" />
    </category>

    <category title="Videos" description="" sd_img="$MythRokuDir/images/Mythtv_movie.png" hd_img="$MythRokuDir/images/Mythtv_movie.png">
	<categoryLeaf title="Title" description="" feed="$MythRokuDir/mythtv_movies_xml.php?sort=title" /> 
	<categoryLeaf title="Genre" description="" feed="$MythRokuDir/mythtv_movies_xml.php?sort=genre" /> 
	<categoryLeaf title="Date"  description="" feed="$MythRokuDir/mythtv_movies_xml.php?sort=date" /> 
    </category>

    <category title="Settings" description="" sd_img="$MythRokuDir/images/Mythtv_settings.png" hd_img="$MythRokuDir/images/Mythtv_settings.png">
	<categoryLeaf title="Settings" description="" feed="$MythRokuDir/mythtv_tv.xml"/> 
    </category>

</categories>

EOF;

?>
