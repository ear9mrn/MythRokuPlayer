<?php
    $cmd = "mythtv_tv_xml.php";
    $arg = "";
    foreach( $_GET as $key => $value )
    {
	$arg .= "$key=$value&";
    }
    if ( $arg !== "" ) { $arg .= '&'; }
    $arg .= "test=mythtv_tv_test.php";

    $xml_string = `php $cmd "$arg"`;
    $stylesheet = "xsl/mythtv_tv_xml.xsl";

    $xml = new DomDocument('1.0');
    $xml->loadXML($xml_string);

    $xp = new XsltProcessor();
    $xsl = new DomDocument('1.0');
    $xsl->load($stylesheet);
    $xp->importStylesheet($xsl);

    $html = $xp->transformToXML($xml);
    echo $html;
?>

