'******************************************************
'**  Video Player Example Application -- Category Feed
'**  November 2009
'**  Copyright (c) 2009 Roku Inc. All Rights Reserved.
'******************************************************

'******************************************************
' Set up the category feed connection object
' This feed provides details about top level categories
'******************************************************
function initCategoryFeedConnection() as object

    conn = CreateObject("roAssociativeArray")

    conn.UrlPrefix = RegRead("MythRokuServerURL")
    conn.UrlCategoryFeed = conn.UrlPrefix + "/mythtv.php"

    conn.Timer = CreateObject("roTimespan")

    conn.LoadCategoryFeed = load_category_feed
    conn.GetCategoryNames = get_category_names

    print "created feed connection for " + conn.UrlCategoryFeed

    return conn

end function

'*********************************************************
'** Create an array of names representing the children
'** for the current list of categories. This is useful
'** for filling in the filter banner with the names of
'** all the categories at the next level in the hierarchy
'*********************************************************
function get_category_names(categories as object) as dynamic

    categoryNames = CreateObject("roArray", 100, true)

    for each category in categories.kids
        'print category.Title
        categoryNames.Push(category.Title)
    next

    return categoryNames

end function

'******************************************************************
'** Given a connection object for a category feed, fetch,
'** parse and build the tree for the feed.  the results are
'** stored hierarchically with parent/child relationships
'** with a single default node named Root at the root of the tree
'******************************************************************
function load_category_feed(conn as object) as dynamic

    print "BEGIN load_category_feed -------------------------------------------"

    http = NewHttp(conn.UrlCategoryFeed)

    Dbg("url: ", http.Http.GetUrl())

    m.Timer.Mark()
    rsp = http.GetToStringWithRetry()
    Dbg("Took: ", m.Timer)

    m.Timer.Mark()
    xml = CreateObject("roXMLElement")
    if not xml.Parse(rsp) then
        print "Can't parse feed"
        return invalid
    end if
    Dbg("Parse Took: ", m.Timer)

    m.Timer.Mark()
    if xml.category = invalid then
        print "no categories tag"
        return invalid
    end if

    if islist(xml.category) = false then
        print "invalid feed body"
        return invalid
    end if

    if xml.category[0].GetName() <> "category" then
        print "no initial category tag"
        return invalid
    end if

    topNode = makeEmptyCatNode()
    topNode.Title = "root"
    topNode.isapphome = true

    print "begin category node parsing"

    categories = xml.GetChildElements()
    print "number of categories: " + itostr(categories.Count())
    for each e in categories
        o = parseCategoryNode(e)
        if o <> invalid then
            topNode.AddKid(o)
            print "added new child node"
        else
            print "parse returned no child node"
        end if
    next
    Dbg("Traversing: ", m.Timer)

    'Add the Settings node
    o = init_category_item()
    o.Type = "settings"
    o.ShortDescriptionLine1 = "Settings"
    o.SDPosterURL = "pkg:/images/Mythtv_settings.png"
    o.HDPosterURL = "pkg:/images/Mythtv_settings.png"
    topNode.AddKid(o)

    print "END load_category_feed ---------------------------------------------"

    return topNode

end function

'******************************************************
'MakeEmptyCatNode - use to create top node in the tree
'******************************************************
function makeEmptyCatNode() as object

    return init_category_item()

end function

'***********************************************************
'Given the xml element to an <Category> tag in the category
'feed, walk it and return the top level node to its tree
'***********************************************************
function parseCategoryNode(xml as object) as dynamic

    o = init_category_item()

    'print "ParseCategoryNode: " + xml.GetName()
    'PrintXML(xml, 5)

    'parse the curent node to determine the type. everything except
    'special categories are considered normal, others have unique types
    if xml.GetName() = "category" then
        print "category: " + xml@title + " | " + xml@description
        o.Type = "normal"
        o.Title = xml@title
        o.Description = xml@Description
        o.ShortDescriptionLine1 = xml@Title
        o.ShortDescriptionLine2 = xml@Description
        o.SDPosterURL = xml@sd_img
        o.HDPosterURL = xml@hd_img
    else if xml.GetName() = "categoryLeaf" then
        o.Type = "normal"
    else
        print "ParseCategoryNode skip: " + xml.GetName()
        return invalid
    end if

    'only continue processing if we are dealing with a known type
    'if new types are supported, make sure to add them to the list
    'and parse them correctly further downstream in the parser
    while true
        if o.Type = "normal" exit while
        print "parseCategoryNode unrecognized feed type"
        return invalid
    end while

    'get the list of child nodes and recursed
    'through everything under the current node
    for each e in xml.GetBody()
        name = e.GetName()
        if name = "category" then
            print "category: " + e@title + " [" + e@description + "]"
            kid = parseCategoryNode(e)
            kid.Title = e@title
            kid.Description = e@Description
            kid.ShortDescriptionLine1 = xml@Description
            kid.SDPosterURL = xml@sd_img
            kid.HDPosterURL = xml@hd_img
            o.AddKid(kid)
        else if name = "categoryLeaf" then
            print "categoryLeaf: " + e@title + " [" + e@description + "]"
            kid = parseCategoryNode(e)
            kid.Title = e@title
            kid.Description = e@Description
            kid.Feed = e@feed
            o.AddKid(kid)
        end if
    next

    return o

end function

'******************************************************
'Initialize a Category Item
'******************************************************
function init_category_item() as object

    o = CreateObject("roAssociativeArray")

    o.Title       = ""
    o.Type        = "normal"
    o.Description = ""
    o.Kids        = CreateObject("roArray", 100, true)
    o.Parent      = invalid
    o.Feed        = ""
    o.IsLeaf      = cn_is_leaf
    o.AddKid      = cn_add_kid

    return o

end function

'********************************************************
'** Helper function for each node, returns true/false
'** indicating that this node is a leaf node in the tree
'********************************************************
function cn_is_leaf() as boolean

    if m.Kids.Count() > 0 return true
    if m.Feed <> "" return false

    return true

end function

'*********************************************************
'** Helper function for each node in the tree to add a
'** new node as a child to this node.
'*********************************************************
function cn_add_kid(kid As Object) as void

    if kid = invalid then
        print "skipping: attempt to add invalid kid failed"
        return
     endif

    kid.Parent = m
    m.Kids.Push(kid)

end function
