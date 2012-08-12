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

    o.StreamBitrates    = CreateObject("roArray", 1, true)
    o.StreamUrls        = CreateObject("roArray", 1, true)
    o.StreamQualities   = CreateObject("roArray", 1, true)
    o.StreamContentIDs  = CreateObject("roArray", 1, true)
    o.StreamFormat      = CreateObject("roArray", 1, true)

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

            tmptype = item.itemType.GetText()

            if tmptype = "file" then
                item = parse_file(item)
            else if tmptype = "dir"  then
                item = parse_dir(item)
            else
                print "[parse_show_feed] unsupported item type: "; tmptype
            end if

            feed.ItemList.Push(item)

        else

            print "[parse_show_feed] unsupported item: "; item.GetName()

        end if

    next

end function

'**************************************************************************
'**************************************************************************

function parse_file( e as object ) as object

    o = init_show_feed_file()

    ' Other attributes
    o.Type       = validstr(e.itemType.GetText())
    o.ContentId  = validstr(e.index.GetText()   )
    o.DelCommand = validstr(e.delCmd.GetText()  )
    if e.isRecording.GetText() = "true" then
        o.Recording = true
    end if

    ' Roku specific attributes
    o.ContentType = validstr(e.contentType.GetText())
    o.Title       = validstr(e.title.GetText()      )
    o.Description = validstr(e.synopsis.GetText()   )
    o.HDPosterUrl = validstr(e.hdImg.GetText()      )
    o.SDPosterUrl = validstr(e.sdImg.GetText()      )

    s = e.media[0]
    o.StreamBitrates.Push(  strtoi(  s.streamBitrate.GetText())  )
    o.StreamUrls.Push(      validstr(s.streamUrl.GetText())      )
    o.StreamQualities.Push( validstr(s.streamQuality.GetText())  )
    o.StreamContentIDs.Push(validstr(s.streamContentId.GetText()))
    o.StreamFormat.Push(    validstr(s.streamFormat.GetText())   )

    o.Length             = strtoi(  e.runtime.GetText()   )
'   o.BookmarkPosition   = strtoi(  e..GetText()          )
    o.ReleaseDate        = validstr(e.date.GetText()      )
    o.Rating             = validstr(e.rating.GetText()    )
    o.StarRating         = strtoi(  e.starRating.GetText())

    o.Actors     = validstr(e.subtitle.GetText())
'   o.Director   = validstr(e..GetText()        )
    o.Categories = validstr(e.genres.GetText()  )
    if e.isHD.GetText() = "true" then
        o.IsHD = true
    end if

    if o.ContentType = "episode" then
        o.EpisodeNumber = validstr(e.episode.GetText())
        o.ShortDescriptionLine1 = o.Title + " - " + o.Actors
        if o.Recording then
            o.ShortDescriptionLine2 = "Episode: " + o.EpisodeNumber + " Recorded: " + o.ReleaseDate
        end if
        o.Actors = "[" + o.EpisodeNumber + "] " + o.Actors
    else ' movie
        o.ShortDescriptionLine1 = o.Title
        o.ShortDescriptionLine2 = o.Actors
    end if

    return o

end function

'**************************************************************************
'**************************************************************************

function parse_dir( e as object ) as object

    o = init_show_feed_dir()

    o.Type  = validstr(e.itemType.GetText())
    o.Feed  = validstr(e.feed.GetText()    )
    o.Title = validstr(e.title.GetText()   )

    o.HDPosterUrl = validstr(e.hdImg.GetText())
    o.SDPosterUrl = validstr(e.sdImg.GetText())

    o.ShortDescriptionLine1 = o.Title

    return o

end function

