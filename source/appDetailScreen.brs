'*******************************************************************************
'** Utility code to display show details
'**
'** NOTE: Modeled after "Video Player Example Application -- Detail Screen"
'*******************************************************************************

'*******************************************************************************
'** Perform any startup/initialization stuff prior to initially showing the
'** screen.
'*******************************************************************************

function preShowDetailScreen( breadA = invalid, breadB = invalid ) as object

    v1 = validateParam( breadA, "roString", "preShowDetailScreen", true )
    v2 = validateParam( breadB, "roString", "preShowDetailScreen", true )
    if not v1 or not v2 then return -1

    port   = CreateObject( "roMessagePort" )
    screen = CreateObject( "roSpringboardScreen" )
    screen.SetMessagePort( port )

    if breadA <> invalid and breadB <> invalid then
        screen.SetBreadcrumbText( breadA, breadB )
    end if

    screen.SetDescriptionStyle( "movie" )

    return screen

end function

'*******************************************************************************
'** The detail screen (springboard) is where the user sees the details for a
'** show and is allowed to select playback. This is the main event loop for that
'** screen  and where we spend our time waiting until the user presses a button
'** and then we decide how best to handle the event.
'*******************************************************************************

function showDetailScreen( screen as object, prevScreen as object, showList as object, showIndex as integer ) as integer

    v1 = validateParam( screen,     "roSpringboardScreen", "showDetailScreen" )
    v2 = validateParam( prevScreen, "roPosterScreen",      "showDetailScreen" )
    v3 = validateParam( showList,   "roArray",             "showDetailScreen" )
    if not v1 or not v2 or not v3 then return -1

    refreshDetailScreen( screen, showList[showIndex] )

    'remote key id's for left/right navigation
    remoteKeyLeft  = 4
    remoteKeyRight = 5

    while true

        msg = wait( 0, screen.GetMessagePort() )

        if type(msg) = "roSpringboardScreenEvent" then

            if msg.isScreenClosed() then

                print "[showDetailScreen] Screen closed"
                exit while

            else if msg.isRemoteKeyPressed() then

                if msg.GetIndex() = remoteKeyLeft then

                    showIndex = getPrevShow( showList, showIndex )
                    if showIndex <> -1 then
                        refreshDetailScreen( screen, showList[showIndex] )
                    end if

                else if msg.GetIndex() = remoteKeyRight then

                    showIndex = getNextShow( showList, showIndex )
                    if showIndex <> -1 then
                        refreshDetailScreen( screen, showList[showIndex] )
                    end if

                end if

            else if msg.isButtonPressed() then

                if msg.GetIndex() = 1 then

                    if not queryJobs( showList[showIndex] ) then

                        'TODO: Consider getting the PlayStart from SQL database.
                        '      This will allow for a universal bookmark that can be
                        '      any frontend or Roku. Will require sending PlayStart
                        '      information back to the MythBox.

                        PlayStart = RegRead( showList[showIndex].ContentId )
                        if PlayStart <> invalid then
                            showList[showIndex].PlayStart = PlayStart.ToInt()
                        end if
                        showVideoScreen( showList[showIndex] )

                    end if

                else if msg.GetIndex() = 2 then

                    if not queryJobs( showList[showIndex] ) then
                        showList[showIndex].PlayStart = 0
                        showVideoScreen( showList[showIndex] )
                    end if

                else if msg.GetIndex() = 7 then

                    print "[showDetailScreen] Delete button pressed: " + showList[showIndex].DelCommand

                    if not queryJobs( showList[showIndex] ) then

                        Dbg( "MythRoku: Confirm delete recording." )
                        title = "MythRoku: Confirm delete recording."
                        text  = "Are you sure you want to delete this recording?"

                        if ShowDialog2Buttons( title, text, "Yes", "No, return" ) = 0 then

                            'Send the HTTP request to delete the recording
                            http = NewHttp( showList[showIndex].DelCommand )
                            Dbg( "url: ", http.Http.GetUrl() )
                            rsp = http.GetToStringWithRetry()

                            'Remove the recording from the poster list and
                            'refresh it on the screen
                            showList.Delete( showIndex )
                            updatePosterList( prevScreen, showList )

                            'Close this details screen
                            screen.Close()

                        end if

                    end if

' TODO: The breadcrumbs are not getting updated when iterating through files.
                else if msg.GetIndex() = 8 then

                    conn      = InitShowFeedConnection( showList[showIndex] )
                    showList  = conn.LoadShowFeed(conn).ItemList
                    showIndex = showList.Count() - 2
                    prevScreen.SetContentList( showList )
                    refreshDetailScreen( screen, showList[showIndex] )

                else if msg.GetIndex() = 9 then

                    conn      = InitShowFeedConnection( showList[showIndex] )
                    showList  = conn.LoadShowFeed(conn).ItemList
                    showIndex = 1
                    prevScreen.SetContentList( showList )
                    refreshDetailScreen( screen, showList[showIndex] )

' TODO: Need a way to copy the breadcrumbs from this details screen to the poster screen.
                else if msg.GetIndex() = 10 then

                    newScreen = preShowPosterScreen( showList[showIndex].Title, "" )
                    showDirectoryPosterScreen( newScreen, showList[showIndex] )

                end if

            end if

        else

            print "[showDetailScreen] Unexpected message class: "; type(msg)

        end if

    end while

    return showIndex

end function

'*******************************************************************************
'** Refresh the contents of the details screen. This may be required on initial
'** entry to the screen or as the user moves left/right on the springboard. When
'** the user is on the springboard, we generally let them press left/right arrow
'** keys to navigate to the previous/next show in a circular manner. When
'** leaving the screen, they should be positioned on the corresponding item in
'** the poster screen matching the current show.
'*******************************************************************************

function refreshDetailScreen( screen as object, item as object ) as integer

    v1 = validateParam( screen, "roSpringboardScreen", "refreshDetailScreen" )
    v2 = validateParam( item,   "roAssociativeArray",  "refreshDetailScreen" )
    if not v1 or not v2 then return -1

    screen.ClearButtons()

    if item.Type = "prev" then

        screen.SetStaticRatingEnabled(false)
        screen.AddButton( 8, "Show these files" )

    else if item.Type = "next" then

        screen.SetStaticRatingEnabled(false)
        screen.AddButton( 9, "Show these files" )

    else if "dir" = item.Type then

        screen.SetStaticRatingEnabled(false)
        screen.AddButton( 10, "Show these files" )

    else

        screen.SetStaticRatingEnabled(true)

' TODO: Only add resume button if there is a timestamp that is at least 30 seconds into the show.
        screen.AddButton( 1, "Resume Playing" )
        screen.AddButton( 2, "Play from Beginning" )

        if item.Recording then
            screen.AddButton( 7, "Delete" )
        end if

    end if

    screen.SetContent( item )
    screen.Show()

end function

'*******************************************************************************
'** Get the next item in the list and handle the wrap around case to implement a
'** circular list for left/right navigation on the springboard screen.
'*******************************************************************************

function getNextShow( showList as object, showIndex as integer ) as integer

    v1 = validateParam( showList, "roArray", "getNextShow" )
    if not v1 then return -1

    nextIndex = showIndex + 1
    if nextIndex >= showList.Count() or nextIndex < 0 then
        nextIndex = 0
    end if

    show = showList[nextIndex]
    v1 = validateParam( show, "roAssociativeArray", "getNextShow" )
    if not v1 then return -1

    return nextIndex

end function

'*******************************************************************************
'** Get the previous item in the list and handle the wrap around case to
'** implement a circular list for left/right navigation on the springboard
'** screen.
'*******************************************************************************

function getPrevShow(showList as object, showIndex as integer) as integer

    v1 = validateParam( showList, "roArray", "getPrevShow" )
    if not v1 then return -1

    prevIndex = showIndex - 1
    if prevIndex < 0 or prevIndex >= showList.Count() then
        if showList.Count() > 0 then
            prevIndex = showList.Count() - 1
        else
            return -1
        end if
    end if

    show = showList[prevIndex]
    v1 = validateParam( show, "roAssociativeArray", "getPrevShow" )
    if not v1 then return -1

    return prevIndex

end function

'*******************************************************************************

function queryJobs( i_video as object ) as boolean

    if not i_video.Recording then return false 'nothing to do

    o_jobsRunning = false

    url = RegRead("MythRokuServerURL") + "/queryJobs.php"
    url = url + "?chanid=" + i_video.chanid
    url = url + "&starttime=" + HttpEncode( i_video.starttime )

    http = NewHttp( url )

    if "true" = http.GetToStringWithRetry() then

        o_jobsRunning = true

        title = "MythRoku: Request failed."
        text  = "Unable process action because there are jobs pending."
        ShowDialog1Button( title, text, "Done" )

    end if

    return o_jobsRunning

end function

