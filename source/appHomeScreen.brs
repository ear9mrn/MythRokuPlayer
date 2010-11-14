'*****************************************************************
'**  Video Player Example Application -- Home Screen
'**  November 2009
'**  Copyright (c) 2009 Roku Inc. All Rights Reserved.
'*****************************************************************

'******************************************************
'** Perform any startup/initialization stuff prior to 
'** initially showing the screen.  
'******************************************************
Function preShowHomeScreen(breadA=invalid, breadB=invalid) As Object

    if validateParam(breadA, "roString", "preShowHomeScreen", true) = false return -1
    if validateParam(breadA, "roString", "preShowHomeScreen", true) = false return -1

    port=CreateObject("roMessagePort")
    screen = CreateObject("roPosterScreen")
    screen.SetMessagePort(port)
    if breadA<>invalid and breadB<>invalid then
        screen.SetBreadcrumbText(breadA, breadB)
    end if

    screen.SetListStyle("flat-category")
    screen.setAdDisplayMode("scale-to-fit")
    return screen

End Function

'************************************************************
' ** Check the registry for the server URL
' ** Prompt the user to enter the URL or IP if it is not
' ** found and write it to the registry.
'************************************************************
Function checkServerUrl() as Void

	serverURL = RegRead("ServerURL")
	
	'set to a default value if reg is empty
	if (serverURL = invalid)
		print "ServerURL not found in the registry"
		serverURL = "http://192.168.1.8/mythweb/mythroku"
		RegWrite("ServerURL", serverURL)
        endif

	http = NewHttp(serverURL + "/mythtv.php")

    	Dbg("url: ", http.Http.GetUrl())

    	rsp = http.GetToStringWithRetry()
	xml=CreateObject("roXMLElement")

	if not xml.Parse(rsp) then
           if (serverURL = invalid) then
		serverURL = "http://192.168.1.8/mythweb/mythroku"
	   else 'something has been entered but does not point to the mythroku
		print "ServerURL invalid"
	   endif
           
	screen = CreateObject("roKeyboardScreen")
	port = CreateObject("roMessagePort")
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
		if type(msg) = "roKeyboardScreenEvent"
			if msg.isScreenClosed()
				return
			else if msg.isButtonPressed() then
				print "Evt: ";msg.GetMessage();" idx:"; msg.GetIndex()
				if msg.GetIndex() = 1
					searchText = screen.GetText()
					print "search text: "; searchText
					RegWrite("ServerURL", searchText)
					return
				endif
			endif
		endif
	end while

	endif
End Function

'******************************************************
'** Display the home screen and wait for events from 
'** the screen. The screen will show retreiving while
'** we fetch and parse the feeds for the game posters
'******************************************************
Function showHomeScreen(screen) As Integer

    if validateParam(screen, "roPosterScreen", "showHomeScreen") = false return -1

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
		if msg.GetIndex() = 2 then
			print "Display settings screen"
			serverURL = RegRead("ServerURL")
			screen = CreateObject("roKeyboardScreen")
			port = CreateObject("roMessagePort")
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
				if type(msg) = "roKeyboardScreenEvent"
					if msg.isScreenClosed() then
						return 0
					else if msg.isButtonPressed() then
						print "Evt: ";msg.GetMessage();" idx:"; msg.GetIndex()
						if msg.GetIndex() = 1
							searchText = screen.GetText()
							print "search text: "; searchText
							RegWrite("ServerURL", searchText)
							return 0
						endif
					endif
				endif
			end while
    
		elseif kid.type = "special_category" then
                    displaySpecialCategoryScreen()
                else
		    print "cat"
                    displayCategoryPosterScreen(kid)
                end if
            else if msg.isScreenClosed() then
                return -1
            end if
        end If
    end while

    return 0

End Function


'**********************************************************
'** When a poster on the home screen is selected, we call
'** this function passing an associative array with the 
'** data for the selected show.  This data should be 
'** sufficient for the show detail (springboard) to display
'**********************************************************
Function displayCategoryPosterScreen(category As Object) As Dynamic

    if validateParam(category, "roAssociativeArray", "displayCategoryPosterScreen") = false return -1
    screen = preShowPosterScreen(category.Title, "")
    showPosterScreen(screen, category)

    return 0
End Function

'**********************************************************
'** Special categories can be used to have categories that
'** don't correspond to the content hierarchy, but are
'** managed from the server by data from the feed.  In these
'** cases we might show a different type of screen other
'** than a poster screen of content. For example, a special
'** category could be search, music, options or similar.
'**********************************************************
Function displaySpecialCategoryScreen() As Dynamic

    ' do nothing, this is intended to just show how
    ' you might add a special category ionto the feed

    return 0
End Function

'************************************************************
'** initialize the category tree.  We fetch a category list
'** from the server, parse it into a hierarchy of nodes and
'** then use this to build the home screen and pass to child
'** screen in the heirarchy. Each node terminates at a list
'** of content for the sub-category describing individual videos
'************************************************************
Function initCategoryList() As Void

    conn = InitCategoryFeedConnection()

    m.Categories = conn.LoadCategoryFeed(conn)
    m.CategoryNames = conn.GetCategoryNames(m.Categories)

End Function
