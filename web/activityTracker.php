<?php

$mode = $_GET["mode"];

require_once("ale/factory.php");
require_once("classes/dbLink.php");
require_once("classes/pecoTracker.php");

$config = parse_ini_file("tracker.ini", true);

$db = new dbLink($config["db"]["host"], $config["db"]["user"], $config["db"]["password"], $config["db"]["database"]);
$tracker = new pecoTracker($config["user"]["userID"], $config["user"]["apiKey"], $config["user"]["charName"], $db);


if ($mode == "track")
{
    $tracker->track();
}
else
{
    $tracker->inventionStatus();
}

?>