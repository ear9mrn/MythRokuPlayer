'*******************************************************************************
'** Utility code to display poster screens
'**
'** NOTE: Modeled after "Video Player Example Application -- Poster Screen"
'*******************************************************************************

'*******************************************************************************
'** Perform any startup/initialization stuff prior to initially showing the
'** screen.
'*******************************************************************************

function preShowPosterScreen( breadA = invalid, breadB = invalid ) as object

    v1 = validateParam( breadA, "roString", "preShowPosterScreen", true )
    v2 = validateParam( breadB, "roString", "preShowPosterScreen", true )
    if not v1 or not v2 then return -1

    port   = CreateObject( "roMessagePort" )
    screen = CreateObject( "roPosterScreen" )
    screen.SetMessagePort( port )

    if breadA <> invalid and breadB <> invalid then
        screen.SetBreadcrumbText( breadA, breadB )
    end if

    screen.SetListStyle( "arced-portrait" )

    return screen

end function

'*******************************************************************************
'** Display the home screen and wait for events from the screen. The screen will
'** show "retrieving" while we fetch and parse the feeds for the show posters.
'*******************************************************************************

function showCategoryPosterScreen( screen as object, category as object ) as integer

    v1 = validateParam( screen,   "roPosterScreen",     "showCategoryPosterScreen" )
    v2 = validateParam( category, "roAssociativeArray", "showCategoryPosterScreen" )
    if not v1 or not v2 then return -1

    m.curCategory = 0
    m.curShow     = 0

    ' Add the category list to the filter banner (i.e. the sort list).
    categoryList = CreateObject( "roArray", 20, true )
    for each subCategory in category.Kids
        categoryList.Push( subcategory.Title )
    next
    screen.SetListNames( categoryList )

    ' Display the current list.
    refreshPosterScreen( screen, category.kids[m.curCategory] )

    ' Set focus to first item in poster list.
    screen.SetFocusToFilterBanner( false )
    screen.SetFocusedListItem( 0 )

    while true

        msg = wait( 0, screen.GetMessagePort() )

        if type(msg) = "roPosterScreenEvent" then

            if msg.isScreenClosed() then

                print "[showPosterScreen] Screen closed"
                return -1

            else if msg.isListFocused() then

                ' Display a new list based on the filter in this category
                m.curCategory = msg.GetIndex()
                refreshPosterScreen( screen, category.kids[m.curCategory] )

                m.curShow = 0
                screen.SetFocusedListItem( m.curShow )

            else if msg.isListItemSelected() then

                m.curShow = msg.GetIndex()
                itemlist  = screen.GetContentList()
                curItem = itemlist[m.curShow]

                if curItem.Type = "prev" then

                    refreshPosterScreen( screen, curItem )
                    screen.SetFocusedListItem( 0 )

                else if curItem.Type = "next" then

                    refreshPosterScreen( screen, curItem )
                    screen.SetFocusedListItem( -1 )

                else if ( curItem.Type = "dir" ) then

                    newScreen = preShowPosterScreen( curItem.Title, category.Title )
                    showDirectoryPosterScreen( newScreen, curItem )

                else

                    newScreen = preShowDetailScreen( category.Title, category.kids[m.curCategory].Title )
                    m.curShow = showDetailScreen( newScreen, screen, itemlist, m.curShow )
                    screen.SetFocusedListItem( m.curShow )

                end if

            end if

        end if

    end while

end function

'******************************************************
'** Display a directory screen and wait for events from
'** the screen. The screen will show retreiving while
'** we fetch and parse the feeds for the video posters
'******************************************************
function showDirectoryPosterScreen( screen as object, directory as object ) as integer

    if validateParam( screen,    "roPosterScreen",     "showPosterScreen" ) = false return -1
    if validateParam( directory, "roAssociativeArray", "showPosterScreen" ) = false return -1

    m.curShow = 0

    itemlist = refreshPosterScreen( screen, directory )

    screen.SetFocusToFilterBanner( false )
    screen.SetFocusedListItem( 0 )

    while true

        msg = wait( 0, screen.GetMessagePort() )

        if "roPosterScreenEvent" = type(msg) then

            if msg.isListItemSelected() then

                m.curShow = msg.GetIndex()
                itemlist  = screen.GetContentList()
                curItem = itemlist[m.curShow]

                if curItem.Type = "prev" then

                    refreshPosterScreen( screen, curItem )
                    screen.SetFocusedListItem( 0 )

                else if curItem.Type = "next" then

                    refreshPosterScreen( screen, curItem )
                    screen.SetFocusedListItem( -1 )

                else if ( curItem.Type = "dir" ) then

                    newScreen = preShowPosterScreen( curItem.Title, directory.Title )
                    showDirectoryPosterScreen( newScreen, curItem )

                else

                    newScreen = preShowDetailScreen( curItem.Title, directory.Title )
                    m.curShow = showDetailScreen( newScreen, screen, itemlist, m.curShow )
                    screen.SetFocusedListItem( m.curShow )

                end if

            else if msg.isScreenClosed() then

                return -1

            end if

        end if

    end while

end function

'*******************************************************************************
'** Will display all items in the list associated with the given item's feed.
'*******************************************************************************

function refreshPosterScreen( screen as object, item as object ) as object

    v1 = validateParam( screen, "roPosterScreen",     "refreshPosterScreen" )
    v2 = validateParam( item,   "roAssociativeArray", "refreshPosterScreen" )
    if not v1 or not v2 then return -1

    conn = InitShowFeedConnection( item )
    feed = conn.LoadShowFeed( conn )
    list = feed.ItemList

    if list.Count() <> 0 then

        ' Change the list style if this list is for recording
        if "rec" = feed.ListType or "episode" = list[0].ContentType then
            screen.SetListStyle("arced-landscape")
        end if

    end if

    updatePosterList( screen, list )

    return list

end function

function updatePosterList( screen as object, list as object ) as void

    v1 = validateParam( screen, "roPosterScreen", "updatePosterList" )
    v2 = validateParam( list,   "roArray",        "updatePosterList" )
    if not v1 or not v2 then return

    screen.ClearMessage()
    screen.SetContentList( CreateObject("roArray") )

    if 0 = list.Count() then
        screen.ShowMessage( "No results found." )
    else
        screen.SetContentList( list )
    end if

    screen.Show()

end function


