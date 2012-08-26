<?php

//------------------------------------------------------------------------------
// Sorting main
//------------------------------------------------------------------------------

function sort_data_array( &$data_array, $sort )
{
    switch ( $sort['type'] )
    {
        case 'series': sort_data_array_series( $data_array, $sort ); break;
    }
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

            $subdirs[$idx] = array( 'hdImg' => $data['hdImgs']['poster'],
                                    'sdImg' => $data['sdImgs']['poster'] );
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
