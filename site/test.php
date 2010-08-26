<?php

require_once("classes/dbLinkClass.php");


echo '<html>
        <head>
            <title>Misc. Eve Tools</title>
            <link rel="stylesheet" href="css/tracker.css">
        </head>
        <body>';

$config = parse_ini_file("tracker.ini", true);
$db = new dbLink($config["db"]["host"], $config["db"]["user"], $config["db"]["password"], $config["db"]["database"]);

$now = time();
$weekday = date("N", $now);
$hour = date("H", $now);
$minute = date("i", $now);
$seconds = date("s", $now);

$weekDuration = 7 * 24 * 60 * 60;

$currentWeekStart = $now - $seconds - ($minute * 60) - ($hour * 60 * 60) - (($weekday - 1) * 60 * 60 * 24);

$currentWeekEnd = $currentWeekStart + $weekDuration - 1;
$lastWeekStart = $currentWeekStart - $weekDuration;
$lastWeekEnd = $currentWeekStart - 1;

$query = "SELECT t2.typeName, COUNT(*) AS cnt, SUM(t1.completedStatus) AS succ
            FROM ".$config["db"]["tablePrefix"]."ActivityTracking AS t1, invTypes as t2
            WHERE t1.outputTypeID = t2.typeID
            AND t1.activityID = 8
            AND t1.entryDate > '".date("o-m-d H:i:s", $lastWeekStart)."'
            AND t1.entryDate <= '".date("o-m-d H:i:s", $lastWeekEnd)."'
            GROUP BY t1.outputTypeID
            ORDER BY t2.typeName";
//echo "<pre>".$query."</pre>";
$db->query($query);

echo "<table>";
echo "<tr><th>Name</th><th>Successful Prints</th><th>Percent</th></tr>";

while($line = mysql_fetch_assoc($db->result))
{
    printf("<tr><td><em>%s</em></td><td>%s</td><td>%s%%</td></tr>", $line["typeName"], $line["succ"], $line["succ"] / $line["cnt"] * 100);
}


echo "</table>";
echo "</body>
    </html>;"


?>