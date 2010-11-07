<?php



require_once './settings.php';


$db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
$db_found = mysql_select_db($MythTVdb, $db_handle);
$counter = 1000;

if ($db_found) {

        if (isset($_GET['sort']) && $_GET['sort'] == 'date') {
                $SQL = "SELECT * FROM recorded ORDER BY starttime DESC";
        }elseif (isset($_GET['sort']) && $_GET['sort'] == 'title'){
                $SQL = "SELECT * FROM recorded ORDER BY title ASC";
        }elseif (isset($_GET['sort']) && $_GET['sort'] == 'genre'){
                $SQL = "SELECT * FROM recorded ORDER BY category ASC";
        }elseif (isset($_GET['sort']) && $_GET['sort'] == 'channel'){
                $SQL = "SELECT * FROM recorded ORDER BY channel ASC";
        }else {
                $SQL = "SELECT * FROM recorded";
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
<h1>MythRoku Test Page: Recordings</h1>
<br>
Use this page to help diagnose any problems there may be with settings and configuration. If all data displays ok here then it should work with Roku!<br><br>";

while ($db_field = mysql_fetch_assoc($result)) {

        $ShowLength = convert_datetime($db_field['endtime']) - convert_datetime($db_field['starttime']);

        print 
        "<br><b>" . $db_field['title'] . "</b><br>

        <a href=\"" . $WebServer . "/pl/stream/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']) .".mp4\"> <img src=\"" . $WebServer . "/tv/get_pixmap/" . $db_field['hostname'] . "/" . $db_field['chanid'] ."/" . convert_datetime($db_field['starttime']) . "/100/75/-1/" . $db_field['basename'] .".100x75x-1.png\"></a><br>
	Stream ID: " . $counter++ . "<br>
	Type: TV<br>
        Quality: " . $RokuDisplayType . "<br>
        Stream Format: mp4<br>
        Stream Bitrate: ". $BitRate . "<br>
        Stream Url: <a href=\"" . $WebServer . "/pl/stream/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']) .".mp4\">" . $WebServer . "/pl/stream/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']) .".mp4</a><br>
        Synopsis: " . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['description'] )) . "<br>
        Genre: " . $db_field['category'] . "<br>
        Runtime: " . $ShowLength . " min<br>
        Date: " . date("F j, Y, g:i a", convert_datetime($db_field['starttime'])) . "<br>
        <br>";
        }

print "</body></html>";

       mysql_close($db_handle);

        }

        else {
        print "Database NOT Found ";
        mysql_close($db_handle);
}

function convert_datetime($str)
{

list($date, $time) = explode(' ', $str);
list($year, $month, $day) = explode('-', $date);
list($hour, $minute, $second) = explode(':', $time);

$timestamp = mktime($hour, $minute, $second, $month, $day, $year);

return $timestamp;
}

?>
