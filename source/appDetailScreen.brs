'**********************************************************
'**  Video Player Example Application - Detail Screen
'**  November 2009
'**  Copyright (c) 2009 Roku Inc. All Rights Reserved.
'**********************************************************
function preShowDetailScreen(breadA=invalid, breadB=invalid) as object

    if validateParam(breadA, "roString", "preShowDetailScreen", true) = false return -1
    if validateParam(breadB, "roString", "preShowDetailScreen", true) = false return -1

    port   = CreateObject("roMessagePort")
    screen = CreateObject("roSpringboardScreen")
    screen.SetMessagePort(port)

    if breadA<>invalid and breadB<>invalid then
        screen.SetBreadcrumbText(breadA, breadB)
    end if

    screen.SetDescriptionStyle("movie")

    return screen

end function

'***************************************************************
'** The show detail screen (springboard) is where the user sees
'** the details for a show and is allowed to select a show to
'** begin playback.  This is the main event loop for that screen
'** and where we spend our time waiting until the user presses a
'** button and then we decide how best to handle the event.
'***************************************************************
function showDetailScreen(screen as object, showList as object, showIndex as integer) as integer

    if validateParam(screen, "roSpringboardScreen", "showDetailScreen") = false return -1
    if validateParam(showList, "roArray", "showDetailScreen") = false return -1

    refreshShowDetail(screen, showList, showIndex)

    'remote key id's for left/right navigation
    remoteKeyLeft  = 4
    remoteKeyRight = 5

    while true
        msg = wait(0, screen.GetMessagePort())

        if type(msg) = "roSpringboardScreenEvent" then

            if msg.isScreenClosed() then
                print "Screen closed"
                exit while
            else if msg.isRemoteKeyPressed() then
                print "Remote key pressed"
                if msg.GetIndex() = remoteKeyLeft then
                    showIndex = getPrevShow(showList, showIndex)
                    if showIndex <> -1 then
                        refreshShowDetail(screen, showList, showIndex)
                    end if
                else if msg.GetIndex() = remoteKeyRight then
                    showIndex = getNextShow(showList, showIndex)
                    if showIndex <> -1 then
                        refreshShowDetail(screen, showList, showIndex)
                    end if
                end if
            else if msg.isButtonPressed() then
                print "Button pressed: "; msg.GetIndex(); " " msg.GetData()

                if msg.GetIndex() = 1 then
                    PlayStart = RegRead(showList[showIndex].ContentId)
                    if PlayStart <> invalid then
                        showList[showIndex].PlayStart = PlayStart.ToInt()
                    end if
                    showVideoScreen(showList[showIndex])
                end if

                if msg.GetIndex() = 2 then
                    showList[showIndex].PlayStart = 0
                    showVideoScreen(showList[showIndex])
                end if

                if msg.GetIndex() = 3 then
                end if

                if msg.GetIndex() = 7 then
                    print "delete button pressed " + showList[showIndex].DelCommand

                    Dbg("MythRoku: Confirm delete recording.")
                    title = "MythRoku: Confirm delete recording."
                    text  = "Are you sure you want to delete this recording?"

                    if ShowDialog2Buttons(title, text, "Yes", "no, return") = 0 then
                        http = NewHttp(showList[showIndex].DelCommand)
                        Dbg("url: ", http.Http.GetUrl())
                        rsp = http.GetToStringWithRetry()
                        showList.Delete(showIndex)
                        kid = m.Categories.Kids[0]
                        displayCategoryPosterScreen(kid)
                    else
                        refreshShowDetail(screen, showList, showIndex)
                    end if

                end if

                'set captions
                if msg.GetIndex() = 9 then
                    srtOnOff = RegRead("MythRokuSrtOnOff")

                    'set to a default value if reg is empty
                    if (srtOnOff = invalid) then
                        print "MythRokuSrtOnOff not found in the registry"
                        srtOnOff = "srtOff"
                        'RegWrite("MythRokuSrtOnOff", srtOnOff)
                    end if

                    print "subtitles button pressed. current value: " + srtOnOff

                    Dbg("MythRoku: change srtOnOff. current value: " + srtOnOff)

                    'toggle values
                    if (srtOnOff = "srtOn")
                        srtOnOff = "srtOff"
                    else
                        srtOnOff = "srtOn"
                    end if

                    RegWrite("MythRokuSrtOnOff", srtOnOff)

                    Dbg("MythRoku: change srtOnOff. new value: " + srtOnOff)

                    refreshShowDetail(screen, showList, showIndex)
                end if

            end if
        else
            print "Unexpected message class: "; type(msg)
        end if

    end while

    return showIndex

end function

'**************************************************************
'** Refresh the contents of the show detail screen. This may be
'** required on initial entry to the screen or as the user moves
'** left/right on the springboard.  When the user is on the
'** springboard, we generally let them press left/right arrow keys
'** to navigate to the previous/next show in a circular manner.
'** When leaving the screen, the should be positioned on the
'** corresponding item in the poster screen matching the current show
'**************************************************************
Function refreshShowDetail(screen As Object, showList As Object, showIndex as Integer) As Integer

    if validateParam(screen, "roSpringboardScreen", "refreshShowDetail") = false return -1
    if validateParam(showList, "roArray", "refreshShowDetail") = false return -1

    show = showList[showIndex]

    'Uncomment this statement to dump the details for each show
    'PrintAA(show)

    screen.ClearButtons()
    screen.AddButton(1, "Resume Playing")
    screen.AddButton(2, "Play from Beginning")

    if show.Recording then
        screen.AddButton(7, "Delete")
    end if

    'set captions
    srtOnOff = RegRead("MythRokuSrtOnOff")

    'set to a default value if reg is empty
    if (srtOnOff = invalid) then
        print "MythRokuSrtOnOff not found in the registry"
        srtOnOff = "srtOff"
        RegWrite("MythRokuSrtOnOff", srtOnOff)
    end if

    if (srtOnOff = "srtOn")
        screen.AddButton(9, "Subtiles are on")
    else
        screen.AddButton(9, "Subtiles are off")
    end if

    screen.SetContent(show)
    screen.Show()

End Function

'********************************************************
'** Get the next item in the list and handle the wrap
'** around case to implement a circular list for left/right
'** navigation on the springboard screen
'********************************************************
Function getNextShow(showList As Object, showIndex As Integer) As Integer
    if validateParam(showList, "roArray", "getNextShow") = false return -1

    nextIndex = showIndex + 1
    if nextIndex >= showList.Count() or nextIndex < 0 then
       nextIndex = 0
    end if

    show = showList[nextIndex]
    if validateParam(show, "roAssociativeArray", "getNextShow") = false return -1

    return nextIndex
End Function


'********************************************************
'** Get the previous item in the list and handle the wrap
'** around case to implement a circular list for left/right
'** navigation on the springboard screen
'********************************************************
Function getPrevShow(showList As Object, showIndex As Integer) As Integer
    if validateParam(showList, "roArray", "getPrevShow") = false return -1

    prevIndex = showIndex - 1
    if prevIndex < 0 or prevIndex >= showList.Count() then
        if showList.Count() > 0 then
            prevIndex = showList.Count() - 1
        else
            return -1
        end if
    end if

    show = showList[prevIndex]
    if validateParam(show, "roAssociativeArray", "getPrevShow") = false return -1

    return prevIndex
End Function
