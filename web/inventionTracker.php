<?php

$mode = $_GET["mode"];

//details of the char with the rights to grab corp jobs
$userID = 1838466;
$apiKey = "fwC1wPrrDqa13d0nd3Q2mE7akMN6RzyDZbXXMJJzinuzLJod4wlBlshM0oKUrFHT";
$charName = "Gar Karath";

$dbServer = "localhost";
$dbUser = "root";
$dbPass = "";
$dbDatabase = "eve_online";

require_once("ale/factory.php");
require_once("classes/dbLink.php");
require_once("classes/pecoTracker.php");


$db = new dbLink("localhost", "root", "", "eve_online");
$tracker = new pecoTracker($userID, $apiKey, $charName, $db);

if ($mode == "track")
{
    $tracker->trackInvention();
}
else
{
    $tracker->inventionStatus();
}

?>