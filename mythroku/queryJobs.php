<?php

require 'settings.php';

// Expected parameters in $_GET:
//  chanid    => The channel ID
//  starttime => The actual start time (not scheduled start time).

$jobsPresent = 'false';

$db_handle = mysql_connect( $MysqlServer, $MythTVdbuser, $MythTVdbpass );
$db_found  = mysql_select_db( $MythTVdb, $db_handle );
if ( $db_found )
{
    $chanid    = $_GET['chanid'];
    $starttime = $_GET['starttime'];

    $SQL = <<<EOF
SELECT J.status FROM jobqueue J
JOIN (
    SELECT CASE value
      WHEN 'JobAllowTranscode' THEN CAST(0x0001 AS UNSIGNED)
      WHEN 'JobAllowCommFlag'  THEN CAST(0x0002 AS UNSIGNED)
      WHEN 'JobAllowMetadata'  THEN CAST(0x0004 AS UNSIGNED)
      WHEN 'JobAllowUserJob1'  THEN CAST(0x0100 AS UNSIGNED)
      WHEN 'JobAllowUserJob2'  THEN CAST(0x0200 AS UNSIGNED)
      WHEN 'JobAllowUserJob3'  THEN CAST(0x0400 AS UNSIGNED)
      WHEN 'JobAllowUserJob4'  THEN CAST(0x0800 AS UNSIGNED)
      END AS type
    FROM settings
    WHERE value LIKE 'JobAllow%' AND data = 1
) AS S ON S.type = J.type
WHERE J.chanid = '$chanid' AND J.starttime = '$starttime'

EOF;

    $SQLs = <<<EOF
SELECT status FROM jobqueue
WHERE chanid = '$chanid' AND starttime = '$starttime'

EOF;

    $result = mysql_query($SQL);
    if ( mysql_num_rows($result) )
    {
        $jobsPresent = 'true';
    }

    mysql_close( $db_handle );
}

echo $jobsPresent;

?>
