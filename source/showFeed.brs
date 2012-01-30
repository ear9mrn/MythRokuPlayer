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

Function InitShowFeedConnection(category As Object) As Object

    if validateParam(category, "roAssociativeArray", "initShowFeedConnection") = false return invalid 

    conn = CreateObject("roAssociativeArray")
    conn.UrlShowFeed  = category.feed 

    conn.Timer = CreateObject("roTimespan")

    conn.LoadShowFeed    = load_show_feed
    conn.ParseShowFeed   = parse_show_feed
    conn.InitFeedItem    = init_show_feed_item

    print "created feed connection for " + conn.UrlShowFeed
    return conn

End Function


'******************************************************
'Initialize a new feed object
'******************************************************
Function newShowFeed() As Object

    o = CreateObject("roArray", 100, true)
    return o

End Function


'***********************************************************
' Initialize a ShowFeedItem. This sets the default values
' for everything.  The data in the actual feed is sometimes
' sparse, so these will be the default values unless they
' are overridden while parsing the actual game data
'***********************************************************
Function init_show_feed_item() As Object
    o = CreateObject("roAssociativeArray")

    o.ContentType = ""
    o.Title	  = ""
    o.TitleSeason = ""
    o.Description = ""
    o.HDPosterUrl = ""
    o.SDPosterUrl = ""

    o.StreamBitrates	= CreateObject("roArray", 1, true)
    o.StreamUrls	= CreateObject("roArray", 1, true)
    o.StreamQualities	= CreateObject("roArray", 1, true)
    o.StreamContentIDs	= CreateObject("roArray", 1, true)
    o.StreamFormat	= CreateObject("roArray", 1, true)

    o.Length		= 0
    o.BookmarkPosition	= 0
    o.ReleaseDate	= ""
    o.Rating		= ""
    o.StarRating	= 0

    o.ShortDescriptionLine1 = ""
    o.ShortDescriptionLine2 = ""

    o.EpisodeNumber = ""
    o.Actors	    = ""
    o.Director	    = ""
    o.Categories    = ""
    o.IsHD	    = false
    o.HDBranded	    = false

    o.ContentId	    = ""
    o.DelCommand    = ""
    o.Recording	    = false

    return o
End Function


'*************************************************************
'** Grab and load a show detail feed. The url we are fetching 
'** is specified as part of the category provided during 
'** initialization. This feed provides a list of all shows
'** with details for the given category feed.
'*********************************************************
Function load_show_feed(conn As Object) As Dynamic

    if validateParam(conn, "roAssociativeArray", "load_show_feed") = false return invalid 

    print "url: " + conn.UrlShowFeed 
    http = NewHttp(conn.UrlShowFeed)

    m.Timer.Mark()
    rsp = http.GetToStringWithRetry()
    print "Request Time: " + itostr(m.Timer.TotalMilliseconds())

    feed = newShowFeed()
    xml=CreateObject("roXMLElement")
    if not xml.Parse(rsp) then
        print "Can't parse feed"
        return feed
    endif

    if xml.GetName() <> "feed" then
        print "no feed tag found"
        return feed
    endif

    if islist(xml.GetBody()) = false then
        print "no feed body found"
        return feed
    endif

    m.Timer.Mark()
    m.ParseShowFeed(xml, feed)
    print "Show Feed Parse Took : " + itostr(m.Timer.TotalMilliseconds())

    return feed

End Function


'**************************************************************************
'**************************************************************************
Function parse_show_feed(xml As Object, feed As Object) As Void

    showCount = 0
    showList = xml.GetChildElements()

    for each curShow in showList

        'for now, don't process meta info about the feed size
        if curShow.GetName() = "resultLength" or curShow.GetName() = "endIndex" then
            goto skipitem
        endif

        item = init_show_feed_item()

	' Other attributes
	item.ContentId	= validstr(curShow.index.GetText())
	item.DelCommand	= validstr(curShow.delcommand.GetText())
	if curShow.recording.GetText() = "true"
	    item.Recording = true
	endif

	' Roku specific attributes
        item.ContentType = validstr(curShow.contentType.GetText())
	item.Title	 = validstr(curShow.title.GetText())
	item.Description = validstr(curShow.synopsis.GetText())
	item.HDPosterUrl = validstr(curShow.hdImg.GetText())
	item.SDPosterUrl = validstr(curShow.sdImg.GetText())

        e = curShow.media[0]
	item.StreamBitrates.Push(  strtoi(  e.streamBitrate.GetText()))
	item.StreamUrls.Push(	   validstr(e.streamUrl.GetText()))
	item.StreamQualities.Push( validstr(e.streamQuality.GetText()))
	item.StreamContentIDs.Push(validstr(e.streamContentId.GetText()))
	item.StreamFormat.Push(	   validstr(e.streamFormat.GetText()))

	item.Length		= strtoi(  curShow.runtime.GetText())
'	item.BookmarkPosition	= strtoi(  curShow..GetText())
	item.ReleaseDate	= validstr(curShow.date.GetText())
	item.Rating		= validstr(curShow.rating.GetText())
	item.StarRating		= strtoi(  curShow.starrating.GetText())

	item.Actors	    = validstr(curShow.subtitle.GetText())
'	item.Director	    = 
	item.Categories	    = validstr(curShow.genres.GetText())
	if curShow.isHD.GetText() = "true"
	    item.IsHD = true
	endif

	if item.ContentType = "episode"
	    item.EpisodeNumber = validstr(curShow.episode.GetText())
	    item.ShortDescriptionLine1 = item.Title + " - " + item.Actors
	    if item.Recording
		item.ShortDescriptionLine2 = "Episode: " + item.EpisodeNumber + " Recorded: " + item.ReleaseDate
	    endif
	    item.Actors = "[" + item.EpisodeNumber + "] " + item.Actors
	else ' movie
	    item.ShortDescriptionLine1 = item.Title
	    item.ShortDescriptionLine2 = item.Actors
	endif

        showCount = showCount + 1
        feed.Push(item)

        skipitem:

    next

End Function
