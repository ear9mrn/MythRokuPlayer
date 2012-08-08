<?php

function xml_start_feed( $args )
{
    return <<<EOF
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<feed listType="{$args['list_type']}"
      resultTotal="{$args['total_rows']}"
      resultIndex="{$args['start_row']}"
      resultLength="{$args['result_rows']}">


EOF;

}

function xml_end_feed()
{
    return <<<EOF
</feed>

EOF;

}

//------------------------------------------------------------------------------

# Builds XML for a file.
function xml_file( $args )
{
    $genre_str = implode( ", ", $args['genres'] );

    $xml = <<<EOF
    <item>
        <itemType>file</itemType>
        <index>{$args['index']}</index>
        <title>{$args['title']}</title>
        <subtitle>{$args['subtitle']}</subtitle>
        <hdImg>{$args['hdImg']}</hdImg>
        <sdImg>{$args['sdImg']}</sdImg>
        <synopsis>{$args['synopsis']}</synopsis>
        <contentType>{$args['contentType']}</contentType>

EOF;

    if ( 'episode' == $args['contentType'] )
    {
        $episode = '';
        if ( $args['episode']['legacy'] )
        {
            $episode = $args['episode']['legacy'];
        }
        else
        {
            $episode = $args['episode']['season'] . ' - ' .
                       $args['episode']['episode'];
        }

            $xml .= <<<EOF
        <episode>$episode</episode>

EOF;

    }

    $xml .= <<<EOF
        <genres>$genre_str</genres>
        <runtime>{$args['runtime']}</runtime>
        <date>{$args['date']}</date>
        <starRating>{$args['starRating']}</starRating>
        <rating>{$args['rating']}</rating>
        <isRecording>{$args['isRecording']}</isRecording>
        <delCmd>{$args['delCmd']}</delCmd>
        <stream>
            <bitrate>{$args['hdStream']['bitrate']}</bitrate>
            <url>{$args['hdStream']['url']}</url>
            <quality>HD</quality>
            <contentId>{$args['hdStream']['contentId']}</contentId>
            <format>{$args['hdStream']['format']}</format>
        </stream>
        <stream>
            <bitrate>{$args['sdStream']['bitrate']}</bitrate>
            <url>{$args['sdStream']['url']}</url>
            <quality>SD</quality>
            <contentId>{$args['sdStream']['contentId']}</contentId>
            <format>{$args['sdStream']['format']}</format>
        </stream>
    </item>


EOF;

    return $xml;
}

# Builds XML for a directory.
#   Required Args: html_parms and title
function xml_dir( $args )
{
    require 'settings.php';

    $script = isset($args['html_parms']['test']) ? 'mythtv_test.php'
                                                 : 'mythtv_xml.php';

    $parms = http_build_query($args['html_parms']);

    $feed = htmlspecialchars("$MythRokuDir/$script?$parms");

    return <<<EOF
    <item>
        <itemType>dir</itemType>
        <title>{$args['title']}</title>
        <hdImg>$MythRokuDir/images/Mythtv_movie.png</hdImg>
        <sdImg>$MythRokuDir/images/Mythtv_movie.png</sdImg>
        <feed>$feed</feed>
    </item>


EOF;

}

//------------------------------------------------------------------------------

function xml_start_dir( $args )
{
    $xml_output = '';

    require 'settings.php';

    if ( 1 < $args['start_row'] )
    {
        $startIndex = $args['start_row'] - $ResultLimit;
        if ( 1 > $startIndex )
        {
            $startIndex = 1;
        }
        $args['html_parms']['index'] = $startIndex;

        $endIndex = $args['start_row'] - 1;

        $args['title'] =  ( $startIndex == $endIndex )
                                    ? "Index $startIndex"
                                    : "Indexes $startIndex - $endIndex";

        $xml_output = xml_dir( $args );
    }

    return $xml_output;
}

function xml_end_dir( $args )
{
    $xml_output = '';

    require 'settings.php';

    if ( $args['total_rows'] >= $args['start_row'] + $args['result_rows'] )
    {
        $startIndex = $args['start_row'] + $ResultLimit;
        if ( $args['total_rows'] < $startIndex )
        {
            $startIndex = $args['total_rows'];
        }
        $args['html_parms']['index'] = $startIndex;

        $endIndex = $startIndex + $ResultLimit - 1;
        if ( $endIndex > $args['total_rows'] )
        {
            $endIndex = $args['total_rows'];
        }

        $args['title'] =  ( $startIndex == $endIndex )
                                    ? "Index $startIndex"
                                    : "Indexes $startIndex - $endIndex";

        $xml_output = xml_dir( $args );
    }

    return $xml_output;
}

?>
