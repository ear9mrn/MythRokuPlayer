'**********************************************************
'**  Video Player Example Application - Show Feed
'**  November 2009
'**  Copyright (c) 2009 Roku Inc. All Rights Reserved.
'**********************************************************

'******************************************************
'** Set up the show feed connection object
'** This feed provides the detailed list of shows for
'** each subcategory (categoryLeaf) in the category
'** category feed. Given a category leaf node for the
'** desired show list, we'll hit the url and get the
'** results.
'******************************************************
function InitShowFeedConnection(category as object) as object

    if validateParam(category, "roAssociativeArray", "initShowFeedConnection") = false return invalid

    conn = CreateObject("roAssociativeArray")
    conn.UrlShowFeed = category.feed

    conn.Timer = CreateObject("roTimespan")

    conn.LoadShowFeed    = load_show_feed
    conn.ParseShowFeed   = parse_show_feed

    print "[InitShowFeedConnection] Created feed connection:"
    print "    " + conn.UrlShowFeed

    return conn

end function

'******************************************************
'Initialize a new feed object
'******************************************************
function newShowFeed() as object

    o = CreateObject("roAssociativeArray")

    o.ListType     = ""
    o.ListTotal    = 0
    o.ResultIndex  = 0
    o.ResultLength = 0
    o.ItemList     = CreateObject("roArray", 100, true)

    return o

end function

'***********************************************************
' Initialize a ShowFeedItem. This sets the default values
' for everything.  The data in the actual feed is sometimes
' sparse, so these will be the default values unless they
' are overridden while parsing the actual game data
'***********************************************************

function init_show_feed_file() as object

    o = CreateObject("roAssociativeArray")

    o.ContentType = ""
    o.Title       = ""
    o.TitleSeason = ""
    o.Description = ""
    o.HDPosterUrl = ""
    o.SDPosterUrl = ""

    o.StreamBitrates    = CreateObject("roArray", 2, true)
    o.StreamUrls        = CreateObject("roArray", 2, true)
    o.StreamQualities   = CreateObject("roArray", 2, true)
    o.StreamContentIDs  = CreateObject("roArray", 2, true)
    o.StreamFormat      = CreateObject("roArray", 2, true)

    o.Length            = 0
    o.BookmarkPosition  = 0
    o.ReleaseDate       = ""
    o.Rating            = ""
    o.StarRating        = 0

    o.ShortDescriptionLine1 = ""
    o.ShortDescriptionLine2 = ""

    o.EpisodeNumber = ""
    o.Actors        = ""
    o.Director      = ""
    o.Categories    = ""
    o.IsHD          = false
    o.HDBranded     = false

    o.ContentId     = ""
    o.DelCommand    = ""
    o.Recording     = false

    return o

end function

'***********************************************************

function init_show_feed_dir() as object

    o = CreateObject("roAssociativeArray")

    o.Type  = ""
    o.Feed  = ""
    o.Title = ""

    o.HDPosterUrl = ""
    o.SDPosterUrl = ""

    o.ShortDescriptionLine1 = ""
    o.ShortDescriptionLine2 = ""

    return o

end function

'*************************************************************
'** Grab and load a show detail feed. The url we are fetching
'** is specified as part of the category provided during
'** initialization. This feed provides a list of all shows
'** with details for the given category feed.
'*********************************************************

function load_show_feed( conn as object ) as dynamic

    if validateParam(conn, "roAssociativeArray", "load_show_feed") = false return invalid

    print "url: " + conn.UrlShowFeed
    http = NewHttp(conn.UrlShowFeed)

    m.Timer.Mark()
    rsp = http.GetToStringWithRetry()
    print "Request Time: " + itostr(m.Timer.TotalMilliseconds())

    feed = newShowFeed()
    xml = CreateObject("roXMLElement")
    if not xml.Parse(rsp) then
        print "Can't parse feed"
        return feed
    end if

    if xml.GetName() <> "feed" then
        print "no feed tag found"
        return feed
    end if

    if islist(xml.GetBody()) = false then
        print "no feed body found"
        return feed
    end if

    m.Timer.Mark()
    m.ParseShowFeed(xml, feed)
    print "Show Feed Parse Took : " + itostr(m.Timer.TotalMilliseconds())

    return feed

end function

'**************************************************************************
'**************************************************************************

function parse_show_feed( xml as object, feed as object ) as void

    feed.ListType     = validstr(xml@listType)
    feed.ListTotal    = strtoi(  xml@resultTotal)
    feed.ResultIndex  = strtoi(  xml@resultIndex)
    feed.ResultLength = strtoi(  xml@resultLength)

    itemList = xml.GetChildElements()

    for each item in itemList

        if item.GetName() = "item" then

            tmptype = item@itemType

            if tmptype = "file" then

                feed.ItemList.Push( parse_file(item) )

            else if tmptype = "prev" or tmptype = "next" then

                feed.ItemList.Push( parse_dir(item) )

            else

                print "[parse_show_feed] unsupported item type: "; tmptype

            end if

        else

            print "[parse_show_feed] unsupported element: "; item.GetName()

        end if

    next

end function

'**************************************************************************
'**************************************************************************

function parse_file( e as object ) as object

    o = init_show_feed_file()

    ' Other attributes
    o.Type       = e@itemType
    o.ContentId  = e@index
    o.DelCommand = e@delCmd
    if e.isRecording.GetText() = "true" then
        o.Recording = true
    end if

    ' Roku specific attributes
    o.Title       = e@title
    o.Description = e@synopsis
    o.HDPosterUrl = e@hdImg
    o.SDPosterUrl = e@sdImg

    for i = 0 to 1
        s = e.stream[i]
        o.StreamBitrates.Push(  strtoi(s@bitrate) )
        o.StreamUrls.Push(      s@url             )
        o.StreamQualities.Push( s@quality         )
        o.StreamContentIDs.Push(s@contentId       )
        o.StreamFormat.Push(    s@format          )
    next i

    o.Length             = strtoi(e@runtime)
'   o.BookmarkPosition   = strtoi(e@)
    o.ReleaseDate        = e@date
    o.Rating             = e@rating
    o.StarRating         = strtoi(e@starRating)

    o.Actors     = e@subtitle
'   o.Director   = e@
    o.Categories = e@genres
    if e@isHD = "true" then
        o.IsHD = true
    end if

    if e@contentType = "episode" then

        'NOTE: We do not want to set o.EpisodeNumber otherwise the images will
        '      not show up in a roPosterScreen (flat-episodic) screen.
        'NOTE: We only want to set o.ContentType to "episode" if it is a
        '      recording. Otherwise, the poster gets stretched to 16x9.

        o.Actors = e@episode + " - " + o.Actors
        if o.Recording then
            o.ShortDescriptionLine2 = " Recorded: " + o.ReleaseDate
            o.ContentType = e@contentType
        end if

    end if

    o.ShortDescriptionLine1 = o.Title
    if o.Actors <> "" then
        o.ShortDescriptionLine1 = o.ShortDescriptionLine1 + " - " + o.Actors
    end if

    return o

end function

'**************************************************************************
'**************************************************************************

function parse_dir( e as object ) as object

    o = init_show_feed_dir()

    o.Type  = e@itemType
    o.Feed  = e@feed
    o.Title = e@title

    o.HDPosterUrl = e@hdImg
    o.SDPosterUrl = e@sdImg

    o.ShortDescriptionLine1 = o.Title

    return o

end function

