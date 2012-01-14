<?php

require_once './settings.php';

$db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
$db_found = mysql_select_db($MythTVdb, $db_handle);

$counter = 1000;

if ($db_found) {

        if (isset($_GET['sort']) && $_GET['sort'] == 'year') {
		$SQL = "SELECT * FROM videometadata WHERE filename LIKE '%.mp4' ORDER BY year DESC ";
	}elseif (isset($_GET['sort']) && $_GET['sort'] == 'title'){
		$SQL = "SELECT * FROM videometadata WHERE filename LIKE '%.mp4' ORDER BY title ASC";
	}elseif (isset($_GET['sort']) && $_GET['sort'] == 'genre'){
		$SQL = "SELECT * FROM videometadata WHERE filename LIKE '%.mp4' ORDER BY category ASC";
	}
	else {
		$SQL = "SELECT * FROM videometadata WHERE filename LIKE '%.mp4'";
	}

        $result = mysql_query($SQL);
        $num_rows = mysql_num_rows($result);

print "
<html> 

<head> 
<title> MythRoku Test Page</title>
</head>

<body bgcolor=\"white\" text=\"blue\">
<img src=\"" . $WebServer . "/" . $MythRokuDir . "/images/mythtv_logo_SD.png\">
<br>
<h1>MythRoku Test Page: Movies</h1>
<br>
Use this page to help diagnose any problems there may be with settings and configuration. If all data displays ok here then it should work with Roku!<br><br>";

while ($db_field = mysql_fetch_assoc($result)) {

        $genrenum = mysql_fetch_assoc(mysql_query("SELECT idgenre FROM videometadatagenre where idvideo='" . $db_field['intid'] . "' "));
	
	if ($genrenum['idgenre'] == 0 ) { $genrenum['idgenre'] = 22; }
        
	$genre = mysql_fetch_assoc(mysql_query("SELECT genre FROM videogenre where intid='" . $genrenum['idgenre'] . "' "));
	
        print 
        "<br><b>" . $db_field['title'] . "</b><br>

        <a href=\"" . $WebServer . "/data/video/" . implode("/", array_map("rawurlencode", explode("/", $db_field['filename']))) . "\"> <img src=\"" . $WebServer . "/pl/coverart/" . $db_field['coverfile'] . " \" width=\"200\" height=\"250\" ></a><br>
	Stream ID: " . $counter++ . "<br>
	Type: Movie<br>
        Quality: . $RokuDisplayType . <br>
        Stream Format: mp4<br>
        Stream Bitrate: ". $BitRate . "<br>
        Stream Url: <a href=\"" . $WebServer . "/data/video/" . implode("/", array_map("rawurlencode", explode("/", $db_field['filename']))) ."\">" . $WebServer . "/data/video/" . implode("/", array_map("rawurlencode", explode("/", $db_field['filename']))) ."</a><br>
        Synopsis: " . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['plot'] )) . "<br>
        Genre: " . $genre['genre'] . "<br>
        Runtime: " .$db_field['length'] . " min<br>
        Year: " . $db_field['year'] . "<br>
	Star Rating: " . round($db_field['userrating'] / 2, 1) ."<br>
        <br>";
        }

print "</body></html>";

       mysql_close($db_handle);

        }

        else {
        print "Database NOT Found ";
        mysql_close($db_handle);
}


?>
