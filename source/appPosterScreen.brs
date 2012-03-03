'******************************************************
'**  Video Player Example Application -- Poster Screen
'**  November 2009
'**  Copyright (c) 2009 Roku Inc. All Rights Reserved.
'******************************************************

'******************************************************
'** Perform any startup/initialization stuff prior to
'** initially showing the screen.
'******************************************************
function preShowPosterScreen(breadA=invalid, breadB=invalid) as object

    if validateParam(breadA, "roString", "preShowPosterScreen", true) = false return -1
    if validateParam(breadB, "roString", "preShowPosterScreen", true) = false return -1

    port   = CreateObject("roMessagePort")
    screen = CreateObject("roPosterScreen")
    screen.SetMessagePort(port)

    if breadA<>invalid and breadB<>invalid then
        screen.SetBreadcrumbText(breadA, breadB)
    end if

    screen.SetListStyle("arced-portrait")

    return screen

end function

'******************************************************
'** Display the home screen and wait for events from
'** the screen. The screen will show retreiving while
'** we fetch and parse the feeds for the game posters
'******************************************************
function showPosterScreen(screen as object, category as object) as integer

    if validateParam(screen, "roPosterScreen", "showPosterScreen") = false return -1
    if validateParam(category, "roAssociativeArray", "showPosterScreen") = false return -1

    m.curCategory = 0
    m.curShow     = 0

    screen.SetListNames(getCategoryList(category)) 'comment out to not show categories
    feed = getShowsForCategoryItem(category.kids[m.curCategory])
    itemlist = feed.ItemList

    ' Change the List Style if this list is for recording
    if feed.ListType = "recording" then
        screen.SetListStyle("arced-landscape")
    end if

    screen.SetContentList(itemlist)
    screen.SetFocusToFilterBanner(false)
    screen.SetFocusedListItem(0)
    screen.Show()

    while true
        msg = wait(0, screen.GetMessagePort())
        if type(msg) = "roPosterScreenEvent" then
            'print "showPosterScreen | msg = "; msg.GetMessage() " | index = "; msg.GetIndex()
            if msg.isListFocused() then
                m.curCategory = msg.GetIndex()
                m.curShow = 0
                screen.SetFocusedListItem(m.curShow)
                screen.SetContentList(getShowsForCategoryItem(category.kids[m.curCategory]).ItemList)
                print "list focused | current category = "; m.curCategory
            else if msg.isListItemSelected() then
                m.curShow = msg.GetIndex()
                print "list item selected | current show = "; m.curShow

                if ( itemlist[m.curShow].ItemType = "dir" ) then
                    itemlist = getShowsForCategoryItem(itemlist[m.curShow]).ItemList
                    screen.SetContentList(itemlist)
                    screen.Show()
                else
                    m.curShow = displayShowDetailScreen(category, itemlist, m.curShow)
                end if

                screen.SetFocusedListItem(m.curShow)
                print "list item updated  | new show = "; m.curShow
            else if msg.isScreenClosed() then
                return -1
            end if
        end if
    end while

end function

'**********************************************************
'** When a poster on the home screen is selected, we call
'** this function passing an associative array with the
'** data for the selected show.  This data should be
'** sufficient for the show detail (springboard) to display
'**********************************************************
function displayShowDetailScreen(category as object, itemlist as object, itemIndex as integer) as integer

    if validateParam(category, "roAssociativeArray", "displayShowDetailScreen") = false return -1

    screen = preShowDetailScreen(category.Title, category.kids[m.curCategory].Title)
    itemIndex = showDetailScreen(screen, itemlist, itemIndex)

    return itemIndex

end function

'**************************************************************
'** Given an roAssociativeArray representing a category node
'** from the category feed tree, return an roArray containing
'** the names of all of the sub categories in the list.
'***************************************************************
Function getCategoryList(topCategory As Object) As Object

    if validateParam(topCategory, "roAssociativeArray", "getCategoryList") = false return -1

    if type(topCategory) <> "roAssociativeArray" then
        print "incorrect type passed to getCategoryList"
        return -1
    endif

    categoryList = CreateObject("roArray", 100, true)
    for each subCategory in topCategory.Kids
        categoryList.Push(subcategory.Title)
    next
    return categoryList

End Function

'********************************************************************
'** Return the list of shows corresponding the currently selected
'** category in the filter banner.  As the user highlights a
'** category on the top of the poster screen, the list of posters
'** displayed should be refreshed to corrrespond to the highlighted
'** item.  This function returns the list of shows for that category
'********************************************************************
function getShowsForCategoryItem(item as object) as object

    conn = InitShowFeedConnection(item)
    feed = conn.LoadShowFeed(conn)

    return feed

end function

