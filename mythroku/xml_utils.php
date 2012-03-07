<?php

function xml_start_feed( $args )
{
    print <<<EOF
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<feed listType="{$args['list_type']}"
      resultTotal="{$args['total_rows']}"
      resultIndex="{$args['start_row']}"
      resultLength="{$args['result_rows']}">


EOF;

}

function xml_end_feed()
{
    print <<<EOF
</feed>

EOF;

}

function xml_file( $args )
{
    print <<<EOF
    <item>
        <itemType>file</itemType>
        <contentType>{$args['contentType']}</contentType>
        <title>{$args['title']}</title>
        <subtitle>{$args['subtitle']}</subtitle>
        <synopsis>{$args['synopsis']}</synopsis>
        <hdImg>{$args['hdImg']}</hdImg>
        <sdImg>{$args['sdImg']}</sdImg>
        <media>
            <streamBitrate>{$args['streamBitrate']}</streamBitrate>
            <streamUrl>{$args['streamUrl']}</streamUrl>
            <streamQuality>{$args['streamQuality']}</streamQuality>
            <streamContentId>{$args['streamContentId']}</streamContentId>
            <streamFormat>{$args['streamFormat']}</streamFormat>
        </media>
        <isHD>{$args['isHD']}</isHD>
        <episode>{$args['episode']}</episode>
        <genres>{$args['genres']}</genres>
        <runtime>{$args['runtime']}</runtime>
        <date>{$args['date']}</date>
        <starRating>{$args['starRating']}</starRating>
        <rating>{$args['rating']}</rating>
        <index>{$args['index']}</index>
        <isRecording>{$args['isRecording']}</isRecording>
        <delCmd>{$args['delCmd']}</delCmd>
    </item>


EOF;

}

function xml_dir( $args )
{
    print <<<EOF
    <item>
        <itemType>dir</itemType>
        <title>{$args['title']}</title>
        <hdImg>{$args['hdImg']}</hdImg>
        <sdImg>{$args['sdImg']}</sdImg>
        <feed>{$args['feed']}</feed>
    </item>


EOF;

}

function xml_start_dir( $args )
{
    require 'settings.php';

    if ( 0 < $args['start_row'] )
    {
        $startIndex = $args['start_row'] - $ResultLimit;
        if ( 0 > $startIndex )
        {
            $startIndex = 0;
        }

        $endIndex = $startIndex + $ResultLimit - 1;
        if ( $endIndex >= $args['start_row'] )
        {
            $endIndex = $args['start_row'] - 1;
        }

        $title = ( $startIndex == $endIndex )
                            ? "Index $startIndex"
                            : "Indexes $startIndex - $endIndex";

        $args['html_parms']['index'] = $startIndex;
        foreach( $args['html_parms'] as $key => $value )
        {
            $html_parms .= "$key=$value&";
        }

        $args = array(
                'title' => $title,
                'hdImg' => "$MythRokuDir/images/Mythtv_movie.png",
                'sdImg' => "$MythRokuDir/images/Mythtv_movie.png",
                'feed'  => htmlspecialchars("$MythRokuDir/{$args['script']}?$html_parms") );
        xml_dir( $args );
    }
}

function xml_end_dir( $args )
{
    require 'settings.php';

    if ( $args['total_rows'] > $args['start_row'] + $args['result_rows'] )
    {
        $startIndex = $args['start_row'] + $ResultLimit;
        if ( $args['total_rows'] < $startIndex )
        {
            $startIndex = $args['total_rows'];
        }

        $endIndex = $startIndex + $ResultLimit - 1;
        if ( $endIndex >= $args['total_rows'] )
        {
            $endIndex = $args['total_rows'] - 1;
        }

        $title = ( $startIndex == $endIndex )
                                ? "Index $startIndex"
                                : "Indexes $startIndex - $endIndex";

        $args['html_parms']['index'] = $startIndex;
        $html_parms = "";
        foreach( $args['html_parms'] as $key => $value )
        {
            $html_parms .= "$key=$value&";
        }

        $args = array(
                'title' => $title,
                'hdImg' => "$MythRokuDir/images/Mythtv_movie.png",
                'sdImg' => "$MythRokuDir/images/Mythtv_movie.png",
                'feed'  => htmlspecialchars("$MythRokuDir/{$args['script']}?$html_parms") );
        xml_dir( $args );
    }
}

?>
