<?php
    if ( isset($_GET['type']) and
         ( 'vid' == $_GET['type'] or 'rec' == $_GET['type'] ) )
    {
        $_GET['test'] = '';
        include 'xml_data.php';

        $xml_string = get_xml_data();
        $stylesheet = "xsl/mythtv_xml_test.xsl";

        $xml = new DomDocument('1.0');
        $xml->loadXML($xml_string);

        $xp = new XsltProcessor();
        $xsl = new DomDocument('1.0');
        $xsl->load($stylesheet);
        $xp->importStylesheet($xsl);

        $html = $xp->transformToXML($xml);
        echo $html;
    }
    else
    {
        echo <<<EOF
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>MythRoku Test Page</title>
</head>

<style type="text/css">
#showlegacy, #showseason
{
    display: none;
}
</style>

<script type="text/javascript">

function formReset()
{
    document.getElementById("frm1").reset();
    displaySort('title');
}

function displaySort(objID)
{
    switch(objID)
    {
        case 'series':
            document.getElementById('showlegacy').style.display = 'block';
            document.getElementById('showseason').style.display = 'block';
            break;
        default:
            document.getElementById('showlegacy').style.display = 'none';
            document.getElementById('showseason').style.display = 'none';
    }

    switch(objID)
    {
        case 'title':
            document.getElementById('showPathHelp_title').style.display  = 'block';
            document.getElementById('showPathHelp_genre').style.display  = 'none';
            document.getElementById('showPathHelp_file').style.display   = 'none';
            document.getElementById('showPathHelp_series').style.display = 'none';
            break;
        case 'genre':
            document.getElementById('showPathHelp_title').style.display  = 'none';
            document.getElementById('showPathHelp_genre').style.display  = 'block';
            document.getElementById('showPathHelp_file').style.display   = 'none';
            document.getElementById('showPathHelp_series').style.display = 'none';
            break;
        case 'file':
            document.getElementById('showPathHelp_title').style.display  = 'none';
            document.getElementById('showPathHelp_genre').style.display  = 'none';
            document.getElementById('showPathHelp_file').style.display   = 'block';
            document.getElementById('showPathHelp_series').style.display = 'none';
            break;
        case 'series':
            document.getElementById('showPathHelp_title').style.display  = 'none';
            document.getElementById('showPathHelp_genre').style.display  = 'none';
            document.getElementById('showPathHelp_file').style.display   = 'none';
            document.getElementById('showPathHelp_series').style.display = 'block';
            break;
        default:
            document.getElementById('showPathHelp_title').style.display  = 'none';
            document.getElementById('showPathHelp_genre').style.display  = 'none';
            document.getElementById('showPathHelp_file').style.display   = 'none';
            document.getElementById('showPathHelp_series').style.display = 'none';
    }
}

function displaySeason(checked)
{
    if ( checked )
    {
        document.getElementById('showseason').style.display = 'none';
    }
    else
    {
        document.getElementById('showseason').style.display = 'block';
    }
}

</script>

<body bgcolor="white" text="blue" onload="formReset()" onreset="formReset()">
    <h1>Select type:</h1>

    <form name="input" id="frm1" action="mythtv_test.php" method="get">

        <table>
            <tr>
                <td valign="top">List Type:</td>
                <td>
                    <input type="radio" name="type" value="vid" checked />Videos<br />
                    <input type="radio" name="type" value="rec"         />Recordings
                </td>
            </tr>
            <tr>
                <td valign="top">Sort:</td>
                <td>
                    <input type="radio" name="sort[type]" value="title"  id="title"  onclick='displaySort(this.id);' checked />Title<br />
                    <input type="radio" name="sort[type]" value="genre"  id="genre"  onclick='displaySort(this.id);'         />Genre<br />
                    <input type="radio" name="sort[type]" value="file"   id="file"   onclick='displaySort(this.id);'         />File System<br />
                    <input type="radio" name="sort[type]" value="series" id="series" onclick='displaySort(this.id);'         />Series
                </td>
                <td valign="top">
                    Path: <input type="text" name="sort[path]" size="40" /><br />
                    <div id="showlegacy">
                        <input type="checkbox" name="sort[legacy]" onclick='displaySeason(this.checked);' />
                        Show legacy episodes (for MythTV .24 and older)
                        <br />
                    </div>
                    <div id="showseason">Season: <input type="number" name="sort[season]" size="1" /></div>
                </td>
                <td valign="top">
                    <div id="showPathHelp_title"> Valid options: '0-9', 'A', 'B', 'C', ..., 'Z' (case-insensitive)</div>
                    <div id="showPathHelp_genre"> Examples: 'Action', 'Science Fiction', 'Romance', etc. (case-insensitive)</div>
                    <div id="showPathHelp_file"> Exact file path i.e. 'Series/Family Guy/' (case-sensitive)</div>
                    <div id="showPathHelp_series"> Examples: 'Family Guy', 'Heroes', 'The Simpsons', etc. (case-insensitive)</div>
                </td>
            </tr>
            <tr>
                <td valign="top">Start Index:</td><td><input type="number" name="index" size="3" value="1" /></td>
            </tr>
        </table>

        <input type="submit" value="Submit" /> <input type="reset" value="Reset" onclick="formReset()" />

    </form>
</body>

</html>

EOF;

    }
?>
