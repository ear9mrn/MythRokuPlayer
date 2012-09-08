<?php

require_once "settings.php";

//------------------------------------------------------------------------------
// Sorting main
//------------------------------------------------------------------------------

function sort_data_array( &$data_array, $sort )
{
    switch ( $sort['type'] )
    {
        case 'title':  sort_data_array_title(  $data_array, $sort ); break;
        case 'genre':  sort_data_array_genre(  $data_array, $sort ); break;
        case 'series': sort_data_array_series( $data_array, $sort ); break;
    }
}

//------------------------------------------------------------------------------
// Sorting by title
//------------------------------------------------------------------------------

# Removes 'The', 'An', or 'A' from the beginning of a title.
function title_substr( $title )
{
    $substr = $title;

    $pre_arr = array( 'The ', 'An ', 'A ' );

    for ( $i = 0; $i < count($pre_arr); ++$i )
    {
        preg_match( "/^${pre_arr[$i]}(.+)/i", $substr, $matches );
        if ( $matches )
        {
            $substr = $matches[1];
            break;
        }
    }

    return preg_replace( '/[^(a-zA-Z0-9)]*/', '', $substr );
}

//------------------------------------------------------------------------------

# Comparison functor for usort.
function title_compare( $a, $b )
{
    return strcasecmp( title_substr($a['title']), title_substr($b['title']) );
}

//------------------------------------------------------------------------------

# Sorts the given array by title. If the sort path is set, it will return a list
# of files where the title begins with 'path'. Otherwise, it will return a list
# of directories where 'path' is the first letter of each title.
function sort_data_array_title( &$data_array, $sort )
{
    if ( '' != $sort['path'] )
    {
        $tmpArray = array();
        foreach ( $data_array as $key => $data )
        {
            $substr = title_substr($data['title']);

            if ( preg_match("/^[{$sort['path']}]/i", $substr) )
            {
                array_push( $tmpArray, $data );
            }
        }
        $data_array = $tmpArray;
    }
    else
    {
        if ( $GLOBALS['ResultLimit'] < count($data_array) )
        {
            $subdirs = array();

            foreach ( $data_array as $key => $data )
            {
                $substr = title_substr($data['title']);

                $idx = '';
                if ( preg_match("/^[0-9]/", $substr) )
                {
                    $idx = '0-9';
                }
                else
                {
                    $idx = ucwords( substr(title_substr($substr),0,1) );
                }

                if ( !isset($subdirs[$idx]) )
                {
                    if ( 'dir' == $data['itemType'] )
                    {
                        $subdirs[$idx] = array( 'hdImg' => $data['hdImg'],
                                                'sdImg' => $data['sdImg'] );
                    }
                    else
                    {
                        $subdirs[$idx] = array(
                                        'hdImg' => $data['hdImgs']['poster'],
                                        'sdImg' => $data['sdImgs']['poster'] );
                    }
                }
            }

            $data_array = array();
            foreach ( $subdirs as $subdir => $val )
            {
                $dir = array( 'itemType'   => 'dir',
                              'title'      => $subdir,
                              'html_parms' => $_GET,
                              'hdImg'      => $val['hdImg'],
                              'sdImg'      => $val['sdImg'] );

                $dir['html_parms']['sort']['path'] = $subdir;

                array_push( $data_array, $dir );
            }
        }
    }

    usort( $data_array, "title_compare" );
}

//------------------------------------------------------------------------------
// Sorting by genre
//------------------------------------------------------------------------------

# Sorts the given array by genre. If the sort path is set, it will return a list
# of files in the given genre. Otherwise, it will return a list of directories
# for each supported genre.
function sort_data_array_genre( &$data_array, $sort )
{
    if ( '' != $sort['path'] )
    {
        $tmpArray = array();
        foreach ( $data_array as $i => $data )
        {
            foreach ( $data['genres'] as $j => $genre )
            {
                if ( ucwords($genre) == ucwords($sort['path']) )
                {
                    array_push( $tmpArray, $data );
                    break;
                }
            }
        }
        $data_array = $tmpArray;
    }
    else
    {
        $subdirs = array();

        foreach ( $data_array as $i => $data )
        {
            foreach ( $data['genres'] as $j => $genre )
            {
                $idx = ucwords($genre);
                if ( !isset($subdirs[$idx]) )
                {
                    $subdirs[$idx] = array(
                                    'hdImg' => $data['hdImgs']['poster'],
                                    'sdImg' => $data['sdImgs']['poster'] );
                }
            }
        }

        $data_array = array();
        foreach ( $subdirs as $subdir => $val )
        {
            $dir = array( 'itemType'   => 'dir',
                          'title'      => $subdir,
                          'html_parms' => $_GET,
                          'hdImg'      => $val['hdImg'],
                          'sdImg'      => $val['sdImg'] );

            $dir['html_parms']['sort']['path'] = $subdir;

            array_push( $data_array, $dir );
        }
    }

    usort( $data_array, "title_compare" );
}

//------------------------------------------------------------------------------
// Sorting by series
//------------------------------------------------------------------------------

# Comparison functors for usort.

function series_legacy_compare( $a, $b )
{
    // Each item in the array should be files.
    return strcasecmp( $a['episode']['legacy'], $b['episode']['legacy'] );
}

function series_season_compare( $a, $b )
{
    // Each item in the array should be directories.
    return intval($a['title']) > intval($b['title']);
}

function series_episode_compare( $a, $b )
{
    // Each item in the array should be files.
    return intval($a['episode']['episode']) > intval($b['episode']['episode']);
}

//------------------------------------------------------------------------------

function sort_data_array_series( &$data_array, $sort )
{
    // NOTE: The given $data_array should be a list of files.
    // NOTE: All items in this list should from the sort path (see SQL query).

    if ( $sort['legacy'] )
    {
        $tmpArray = array();
        foreach ( $data_array as $i => $data )
        {
            if ( 0 == $data['episode']['season'] )
            {
                array_push( $tmpArray, $data );
            }
        }
        $data_array = $tmpArray;

        usort( $data_array, "series_legacy_compare" );
    }
    else if ( 0 < $sort['season'] )
    {
        $tmpArray = array();
        foreach ( $data_array as $i => $data )
        {
            if ( $sort['season'] == $data['episode']['season'] )
            {
                array_push( $tmpArray, $data );
            }
        }
        $data_array = $tmpArray;

        usort( $data_array, "series_episode_compare" );
    }
    else
    {
        $subdirs = array();

        foreach ( $data_array as $i => $data )
        {
            $idx = 'Legacy';
            if ( 0 < $data['episode']['season'] )
            {
                $idx = $data['episode']['season'];
            }

            if ( !isset($subdirs[$idx]) )
            {
                $subdirs[$idx] = array( 'hdImg' => $data['hdImgs']['poster'],
                                        'sdImg' => $data['sdImgs']['poster'] );
            }
        }

        if ( isset($subdirs['Legacy']) and 1 == count($subdirs) )
        {
            // The list should only contain files with legacy season/episode
            // information.
            usort( $data_array, "series_legacy_compare" );
        }
        else
        {
            $data_array = array();
            foreach ( $subdirs as $subdir => $val )
            {
                $dir = array( 'itemType'   => 'dir',
                              'title'      => $subdir,
                              'html_parms' => $_GET,
                              'hdImg'      => $val['hdImg'],
                              'sdImg'      => $val['sdImg'] );

                if ( 'Legacy' == $subdir )
                {
                    $dir['html_parms']['sort']['legacy'] = 1;
                }
                else
                {
                    $dir['html_parms']['sort']['season'] = $subdir;
                }

                $dir['html_parms']['index'] = 1;

                array_push( $data_array, $dir );
            }

            usort( $data_array, "series_season_compare" );
        }
    }
}

?>
