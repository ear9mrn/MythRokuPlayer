<?php

require 'settings.php';

$script = "$MythRokuDir/mythtv_xml.php";

print <<<EOF
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<categories>

EOF;

print_cat_begin( "Recordings", "Mythtv_tv.png" );
$sort = array( 'Title'       => 'title',
               'Date'        => 'date',
               'Genre'       => 'genre',
               'File System' => 'file',
               'Channel'     => 'channel',
               'Group'       => 'recgroup' );
print_cat_leaves( 'rec', $sort, $script );
print_cat_end();

print_cat_begin( "Videos", "Mythtv_movie.png" );
$sort = array( 'Title'       => 'title',
               'Date'        => 'date',
               'Genre'       => 'genre',
               'File System' => 'file', );
print_cat_leaves( 'vid', $sort, $script );
print_cat_end();

print <<<EOF
</categories>

EOF;

//------------------------------------------------------------------------------

function print_cat_begin( $type, $image )
{
    require 'settings.php';

    print <<<EOF
    <category title       = "$type"
              description = ""
              sd_img      = "$MythRokuDir/images/$image"
              hd_img      = "$MythRokuDir/images/$image" >

EOF;
}

//------------------------------------------------------------------------------

function print_cat_end()
{
    print <<<EOF
    </category>


EOF;
}

//------------------------------------------------------------------------------

function print_cat_leaves( $type, $sort, $script )
{
    foreach ( $sort as $key => $val )
    {
        $parms = http_build_query( array( 'type' => $type,
                                          'sort' => array( 'type' => $val ) ) );
        $parms = htmlspecialchars( $parms );

        print <<<EOF
        <categoryLeaf title = "$key" description = "" feed = "$script?$parms" />

EOF;
    }
}

?>
