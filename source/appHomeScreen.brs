'*****************************************************************
'**  Video Player Example Application -- Home Screen
'**  November 2009
'**  Copyright (c) 2009 Roku Inc. All Rights Reserved.
'*****************************************************************

'******************************************************
'** Perform any startup/initialization stuff prior to
'** initially showing the screen.
'******************************************************
function preShowHomeScreen(breadA=invalid, breadB=invalid) as object

    if validateParam(breadA, "roString", "preShowHomeScreen", true) = false return -1
    if validateParam(breadB, "roString", "preShowHomeScreen", true) = false return -1

    port   = CreateObject("roMessagePort")
    screen = CreateObject("roPosterScreen")
    screen.SetMessagePort(port)

    if breadA<>invalid and breadB<>invalid then
        screen.SetBreadcrumbText(breadA, breadB)
    end if

    screen.SetListStyle("flat-category")
    screen.setAdDisplayMode("scale-to-fit")

    return screen

end function

'******************************************************
'** Display the home screen and wait for events from
'** the screen. The screen will show retreiving while
'** we fetch and parse the feeds for the game posters
'******************************************************
function showHomeScreen(screen) as integer

    if validateParam(screen, "roPosterScreen", "showHomeScreen") = false return -1

    ' Verify that server URL is set and valid
    checkServerUrl()

    initCategoryList()
    screen.SetContentList(m.Categories.Kids)
    screen.SetFocusedListItem(0)
    screen.Show()

    while true
        msg = wait(0, screen.GetMessagePort())
        if type(msg) = "roPosterScreenEvent" then
            print "showHomeScreen | msg = "; msg.GetMessage() " | index = "; msg.GetIndex()
            if msg.isListFocused() then
                print "list focused | index = "; msg.GetIndex(); " | category = "; m.curCategory
            else if msg.isListItemSelected() then
                print "list item selected | index = "; msg.GetIndex()
                kid = m.Categories.Kids[msg.GetIndex()]
                if kid.type = "settings" then
                    displaySettingsScreen()
                else
                    displayCategoryPosterScreen(kid)
                end if
            else if msg.isScreenClosed() then
                return -1
            end if
        end if
    end while

    return 0

end function

'**********************************************************
'** When a poster on the home screen is selected, we call
'** this function passing an associative array with the
'** data for the selected show.  This data should be
'** sufficient for the show detail (springboard) to display
'**********************************************************
function displayCategoryPosterScreen(category as object) as dynamic

    if validateParam(category, "roAssociativeArray", "displayCategoryPosterScreen") = false return -1

    screen = preShowPosterScreen(category.Title, " ")
    showCategoryPosterScreen(screen, category)

    return 0

end function

'************************************************************
'** initialize the category tree.  We fetch a category list
'** from the server, parse it into a hierarchy of nodes and
'** then use this to build the home screen and pass to child
'** screen in the heirarchy. Each node terminates at a list
'** of content for the sub-category describing individual videos
'************************************************************
function initCategoryList() as void

    conn = initCategoryFeedConnection()

    m.Categories    = conn.LoadCategoryFeed(conn)
    m.CategoryNames = conn.GetCategoryNames(m.Categories)

end function

'*******************************************************************************
' ** Check the registry for the server URL
' ** Prompt the user to enter the URL or IP if it is not
' ** found and write it to the registry.
'*******************************************************************************
function checkServerUrl() as void

    serverURL = RegRead("MythRokuServerURL")

    'set to a default value if reg is empty
    if (serverURL = invalid) then
        print "MythRokuServerURL not found in the registry"
        serverURL = "http://192.168.1.8/mythweb/mythroku"
        RegWrite("MythRokuServerURL", serverURL)
    end if

    http = NewHttp(serverURL + "/mythtv.php")

    Dbg("url: ", http.Http.GetUrl())

    rsp = http.GetToStringWithRetry()
    xml = CreateObject("roXMLElement")

    if not xml.Parse(rsp) then

        if (serverURL = invalid) then
            serverURL = "http://192.168.1.8/mythweb/mythroku"
        else 'something has been entered but does not point to the mythroku
            print "MythRokuServerURL is invalid"
        end if

        displaySettingsScreen()

    end if

end function

'*******************************************************************************
'** Display the Settings screen
'*******************************************************************************
function displaySettingsScreen() as void

    print "Displaying Settings screen"
    serverURL = RegRead("MythRokuServerURL")

    screen = CreateObject("roKeyboardScreen")
    port   = CreateObject("roMessagePort")
    screen.SetMessagePort(port)

    screen.SetTitle("Video Server URL")
    screen.SetText(serverURL)
    screen.SetDisplayText("Enter URL for mythroku, e.g. http://myip/mythweb/mythroku")
    screen.SetMaxLength(100)
    screen.AddButton(1, "finished")
    screen.Show()

    while true
        msg = wait(0, screen.GetMessagePort())
        print "message received"
        if (type(msg) = "roKeyboardScreenEvent") then
            if msg.isScreenClosed() then
                exit while
            else if msg.isButtonPressed() then
                print "Evt: "; msg.GetMessage(); " idx:"; msg.GetIndex()
                if (msg.GetIndex() = 1) then
                    searchText = screen.GetText()
                    print "search text: "; searchText
                    RegWrite("MythRokuServerURL", searchText)
                    exit while
                end if
            end if
        end if
    end while

end function

