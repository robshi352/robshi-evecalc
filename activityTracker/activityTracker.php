<?php
/*Copyright 2010 @ masubious*/

require_once("ale/factory.php");
require_once("classes/activityTrackerClass.php");
require_once("classes/activityDisplayerClass.php");
require_once("classes/trackerMenuClass.php");

$config = parse_ini_file("tracker.ini", true);

//setup modes and styles for the menu
$modes = array("both"       => array("displayName"  => "Both",
                                     "show"         => true),
               "inv"        => array("displayName"  => "Invention",
                                     "show"         => true),
               "prod"       => array("displayName"  => "Production",
                                     "show"         => true),
               "track"      => array("displayName"  => "Update",
                                     "show"         => false),
               "default"    => array("displayName"  => "both",
                                     "show"         => false));

$tracker = new activityTracker($config);
$trackerMenu = new trackerMenu($modes, $tracker->getLatestEntry());
$displayer = new activityDisplayer();

$now = time();
$weekday = date("N", $now);
$hour = date("H", $now);
$minute = date("i", $now);
$seconds = date("s", $now);

$currentWeekStart = $now - $seconds - ($minute * 60) - ($hour * 60 * 60) - (($weekday - 1) * 60 * 60 * 24);
$currentWeekEnd = $currentWeekStart + (7 * 24 * 60 * 60) - 1;
$lastWeekStart = $currentWeekStart - (7 * 24 * 60 * 60);
$lastWeekEnd = $currentWeekEnd - (7 * 24 * 60 * 60);

echo '<html>
        <header>
            <title>Invention &amp; Production Tracker</title>
            <link rel="stylesheet" href="css/tracker.css">
        </header>
        
        <body>';

$trackerMenu->display();

//echo '<div class="clear"></div>';

if ($trackerMenu->showInfo())
    $displayer->displayInfo();

echo '<div id="content">';

if ($trackerMenu->getCurrentMode() == "track")
{
    $tracker->init();
    $displayer->displayTrackingInfo($tracker->track());
    if ($config["user"]["generateXML"])
        $tracker->generateXML($lastWeekStart, $lastWeekEnd);
}

elseif($trackerMenu->getCurrentMode() == "inv")
{
    $displayer->DisplayInvention($tracker->getInventionData($currentWeekStart, $currentWeekEnd));
    $displayer->DisplayInvention($tracker->getInventionData($lastWeekStart, $lastWeekEnd));
}

elseif($trackerMenu->getCurrentMode() == "prod")
{
    $displayer->DisplayProduction($tracker->getProductionData($currentWeekStart, $currentWeekEnd));
    $displayer->DisplayProduction($tracker->getProductionData($lastWeekStart, $lastWeekEnd));
}
else
{
    echo '<div id="inventionWrapper">';
    $displayer->DisplayInvention($tracker->getInventionData($currentWeekStart, $currentWeekEnd));
    $displayer->DisplayInvention($tracker->getInventionData($lastWeekStart, $lastWeekEnd));
    echo "</div>";
    echo '<div class="clear"></div>';
    echo '<div id="productionWrapper">';
    $displayer->DisplayProduction($tracker->getProductionData($currentWeekStart, $currentWeekEnd));
    $displayer->DisplayProduction($tracker->getProductionData($lastWeekStart, $lastWeekEnd));
    echo '</div>';
}

echo "</div>
      </body>
      </html>";

?>