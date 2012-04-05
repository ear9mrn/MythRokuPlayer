<?php
    if ( isset($_GET['type']) and
         ($_GET['type'] == 'vid' or $_GET['type'] == 'rec') )
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
        require 'settings.php';

        $_GET['type'] = 'vid';
        $url_vid = "$MythRokuDir/mythtv_test.php?" . http_build_query($_GET);

        $_GET['type'] = 'rec';
        $url_rec = "$MythRokuDir/mythtv_test.php?" . http_build_query($_GET);

        echo <<<EOF
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>MythRoku Test Page</title>
        </head>
        <body bgcolor="white" text="blue">
            <h1>Select type:</h1>
            <table>
                <tr>
                    <td>Videos:</td>
                    <td><a href="{$url_vid}">$url_vid</a></td>
                </tr>
                <tr>
                    <td>Recordings:</td>
                    <td><a href="{$url_rec}">$url_rec</a></td>
                </tr>
            </table>
        </body>
    </head>
</html>
EOF;

    }
?>
