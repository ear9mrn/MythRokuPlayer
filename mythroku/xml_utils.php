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
        if ( 0 < $args['episode']['season'] && 0 < $args['episode']['episode'] )
        {
            $episode = "S{$args['episode']['season']}:" .
                       "E{$args['episode']['episode']}";
        }
        else
        {
            $episode = $args['episode']['legacy'];
        }
    }

    return <<<EOF
    <item itemType    = "{$args['itemType']}"
          index       = "{$args['index']}"
          title       = "{$args['title']}"
          subtitle    = "{$args['subtitle']}"
          hdImg       = "{$args['hdImgs']['screen']}"
          sdImg       = "{$args['sdImgs']['screen']}"
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
#   Required Args: html_parms, itemType, title, hdImg, and sdImg
function xml_dir( $args )
{
    require 'settings.php';

    $script = isset($args['html_parms']['test']) ? 'mythtv_test.php'
                                                 : 'mythtv_xml.php';

    $parms = http_build_query($args['html_parms']);

    $feed = htmlspecialchars("$MythRokuDir/$script?$parms");

    return <<<EOF
    <item itemType = "{$args['itemType']}"
          index    = "{$args['index']}"
          title    = "{$args['title']}"
          hdImg    = "{$args['hdImg']}"
          sdImg    = "{$args['sdImg']}"
          feed     = "$feed" />


EOF;

}

//------------------------------------------------------------------------------

# Creates a puedo directory that advances to the previous set of items in the
# list.
#   Required Args:
#       start_row   => starting index of current subset
#       html_parms  => $_GET
function xml_start_dir( $args )
{
    $xml_output = '';

    require 'settings.php';

    if ( 1 < $args['start_row'] )
    {
        $args['itemType'] = 'prev';

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

        $args['hdImg'] = "$MythRokuDir/images/Mythtv_movie.png";
        $args['sdImg'] = "$MythRokuDir/images/Mythtv_movie.png";

        $xml_output = xml_dir( $args );
    }

    return $xml_output;
}

# Creates a puedo directory that advances to the next set of items in the list.
#   Required Args:
#       start_row   => starting index of current subset
#       result_rows => number of items in current subset
#       total_rows  => total number of entries in the list
#       html_parms  => $_GET
function xml_end_dir( $args )
{
    $xml_output = '';

    require 'settings.php';

    if ( (0 != $args['total_rows']) &&
         ($args['total_rows'] >= $args['start_row'] + $args['result_rows']) )
    {
        $args['itemType'] = 'next';

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

        $args['hdImg'] = "$MythRokuDir/images/Mythtv_movie.png";
        $args['sdImg'] = "$MythRokuDir/images/Mythtv_movie.png";

        $xml_output = xml_dir( $args );
    }

    return $xml_output;
}

?>
