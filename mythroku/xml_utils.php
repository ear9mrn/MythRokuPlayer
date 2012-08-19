<?php

function xml_start_feed( $args )
{
    return <<<EOF
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<feed listType     = "{$args['list_type']}"
      resultTotal  = "{$args['total_rows']}"
      resultIndex  = "{$args['start_row']}"
      resultLength = "{$args['result_rows']}">


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

    $episode = '';
    if ( 'episode' == $args['contentType'] )
    {
        if ( $args['episode']['legacy'] )
        {
            $episode = $args['episode']['legacy'];
        }
        else
        {
            $episode = $args['episode']['season'] . ' - ' .
                       $args['episode']['episode'];
        }
    }

    return <<<EOF
    <item itemType    = "file"
          index       = "{$args['index']}"
          title       = "{$args['title']}"
          subtitle    = "{$args['subtitle']}"
          hdImg       = "{$args['hdImg']}"
          sdImg       = "{$args['sdImg']}"
          synopsis    = "{$args['synopsis']}"
          contentType = "{$args['contentType']}"
          episode     = "$episode"
          genres      = "$genre_str"
          runtime     = "{$args['runtime']}"
          date        = "{$args['date']}"
          starRating  = "{$args['starRating']}"
          rating      = "{$args['rating']}"
          isRecording = "{$args['isRecording']}"
          delCmd      = "{$args['delCmd']}" >
        <stream bitrate   = "{$args['hdStream']['bitrate']}"
                url       = "{$args['hdStream']['url']}"
                quality   = "HD"
                contentId = "{$args['hdStream']['contentId']}"
                format    = "{$args['hdStream']['format']}" />
        <stream bitrate   = "{$args['sdStream']['bitrate']}"
                url       = "{$args['sdStream']['url']}"
                quality   = "SD"
                contentId = "{$args['sdStream']['contentId']}"
                format    = "{$args['sdStream']['format']}" />
    </item>


EOF;

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
    <item itemType = "dir"
          title    = "{$args['title']}"
          hdImg    = "$MythRokuDir/images/Mythtv_movie.png"
          sdImg    = "$MythRokuDir/images/Mythtv_movie.png"
          feed     = "$feed" />


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

    if ( (0 != $args['total_rows']) &&
         ($args['total_rows'] >= $args['start_row'] + $args['result_rows']) )
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
