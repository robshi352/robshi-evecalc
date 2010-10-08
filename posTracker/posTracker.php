<?php
/*Copyright 2010 @ masubious*/

require_once("ale/factory.php");
require_once("classes/posTrackerClass.php");
require_once("classes/posDisplayerClass.php");

$config = parse_ini_file("tracker.ini", true);

$tracker = new posTracker($config);
$displayer = new posDisplayer();

echo '<html>
        <header>
            <title>POS Status Tracker</title>
            <link rel="stylesheet" href="css/tracker.css">
        </header>
        
        <body>';

echo '<div id="content">';

if ($_GET["mode"] == "track")
{
    $displayer->displayTrackingInfo($tracker->track());
}
else
{
    $displayer->displayPosStatus($tracker->getPosData());
    $displayer->displayFuelData($tracker->getFuelData());
}

echo "</div>
      </body>
      </html>";

?>