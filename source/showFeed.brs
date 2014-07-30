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
    conn.InitFeedItem    = init_show_feed_item

    print "created feed connection for " + conn.UrlShowFeed

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
function init_show_feed_item() as object

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

    o.HDBifUrl       = ""
    o.SDBifUrl       = ""
    o.SubtitleUrl    = ""
    o.SubtitleConfig = ""

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

'*************************************************************
'** Grab and load a show detail feed. The url we are fetching
'** is specified as part of the category provided during
'** initialization. This feed provides a list of all shows
'** with details for the given category feed.
'*********************************************************
function load_show_feed(conn as object) as dynamic

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
function parse_show_feed(xml as object, feed as object) as void

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
                print "[parse_show_feed] unsupported type: "; tmptype
            end if

            feed.ItemList.Push(item)

        else

            print "[parse_show_feed] unsupported item: "; item.GetName()

        end if

    next

end function

'**************************************************************************
'**************************************************************************
function parse_file(element as object) as object

    item = init_show_feed_item()

    ' Other attributes
    item.ItemType   = validstr(element.itemType.GetText())
    item.ContentId  = validstr(element.index.GetText())
    item.DelCommand = validstr(element.delcommand.GetText())
    if element.isRecording.GetText() = "true" then
        item.Recording = true
    end if

    ' Roku specific attributes
    item.ContentType = validstr(element.contentType.GetText())
    item.Title       = validstr(element.title.GetText())
    item.Description = validstr(element.synopsis.GetText())
    item.HDPosterUrl = validstr(element.hdImg.GetText())
    item.SDPosterUrl = validstr(element.sdImg.GetText())

    e = element.media[0]
    item.StreamBitrates.Push(  strtoi(  e.streamBitrate.GetText()))
    item.StreamUrls.Push(      validstr(e.streamUrl.GetText()))
    item.StreamQualities.Push( validstr(e.streamQuality.GetText()))
    item.StreamContentIDs.Push(validstr(e.streamContentId.GetText()))
    item.StreamFormat.Push(    validstr(e.streamFormat.GetText()))

    item.HDBifUrl       = validstr(e.hdbifUrl.GetText())
    item.SDBifUrl       = validstr(e.sdbifsrtUrl.GetText())
    item.SubtitleUrl    = validstr(e.srtUrl.GetText())
    item.SubtitleConfig = {
        ShowSubtitle: 1 ' alternatively use screen.ShowSubtitle(true) to toggle Srt
        TrackName: item.SubtitleUrl
    }

    item.Length             = strtoi(  element.runtime.GetText())
'   item.BookmarkPosition   = strtoi(  element..GetText())
    item.ReleaseDate        = validstr(element.date.GetText())
    item.Rating             = validstr(element.rating.GetText())
    item.StarRating         = strtoi(  element.starRating.GetText())

    item.Actors     = validstr(element.subtitle.GetText())
'   item.Director   = validstr(element..GetText())
    item.Categories = validstr(element.genres.GetText())
    if element.isHD.GetText() = "true" then
        item.IsHD = true
    endif

    if item.ContentType = "episode"
        item.EpisodeNumber = validstr(element.episode.GetText())
        item.ShortDescriptionLine1 = item.Title + " - " + item.Actors
        if item.Recording
            item.ShortDescriptionLine2 = "Episode: " + item.EpisodeNumber + " Recorded: " + item.ReleaseDate
        end if
        item.Actors = "[" + item.EpisodeNumber + "] " + item.Actors
    else ' movie
        item.ShortDescriptionLine1 = item.Title
        item.ShortDescriptionLine2 = item.Actors
    end if

    return item

end function

'**************************************************************************
'**************************************************************************
function parse_dir(element as object) as object

    item = init_show_feed_item()

    item.ItemType = validstr(element.itemType.GetText())

    item.ShortDescriptionLine1 = validstr(element.title.GetText())

    item.HDPosterUrl = validstr(element.hdImg.GetText())
    item.SDPosterUrl = validstr(element.sdImg.GetText())

    item.Feed = validstr(element.feed.GetText())

    return item

end function
