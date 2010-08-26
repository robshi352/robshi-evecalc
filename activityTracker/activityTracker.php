<?php
/*Copyright 2010 @ masubious*/

require_once("ale/factory.php");
require_once("classes/activityTrackerClass.php");
require_once("classes/activityDisplayerClass.php");
require_once("classes/trackerMenuClass.php");

$config = parse_ini_file("tracker.ini", true);

//setup modes and styles for the menu
$modes = array( "show"          => array(   "displayName"      => "Tracking",
                                            "displayParent"    => null,
                                            "display"          => true),
                "both"          => array(   "displayName"      => "Both",
                                            "displayParent"    => "show",
                                            "display"          => true),
                "inv"           => array(   "displayName"      => "Invention",
                                            "displayParent"    => "show",
                                            "display"          => true),
                "prod"          => array(   "displayName"      => "Production",
                                            "displayParent"    => "show",
                                            "display"          => true),
                "track"         => array(   "displayName"      => "Update",
                                            "displayParent"    => null,
                                            "display"          => false),
                "stats"         => array(   "displayName"      => "Stats",
                                            "displayParent"    => null,
                                            "display"          => true),
                "curr"          => array(   "displayName"      => "current week",
                                            "displayParent"    => "stats",
                                            "display"          => true),
                "last"          => array(   "displayName"      => "last week",
                                            "displayParent"    => "stats",
                                            "display"          => true),
                "all"           => array(   "displayName"      => "all weeks",
                                            "displayParent"    => "stats",
                                            "display"          => false),
                "default"       => array(   "displayName"      => "both",
                                            "displayParent"    => null,
                                            "display"          => false),
                "defaultshow"   => array(   "displayName"      => "both",
                                            "displayParent"    => "show",
                                            "display"          => false),
                "defaultstats"  => array(   "displayName"      => "curr",
                                            "displayParent"    => "stats",
                                            "display"          => false));

$tracker = new activityTracker($config);
$trackerMenu = new trackerMenu($modes, $tracker->getLatestEntry());
$displayer = new activityDisplayer();

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

echo '<html>
        <header>
            <title>Invention &amp; Production Tracker</title>
            <link rel="stylesheet" href="css/tracker.css">
        </header>
        
        <body>';

$trackerMenu->display();

echo '<div id="content">';

if ($trackerMenu->getCurrentMode() == "track")
{
    $tracker->init();
    $displayer->displayTrackingInfo($tracker->track());
    $tracker->updateMembers();
    if ($config["user"]["generateXML"])
        $tracker->generateXML($config["user"]["xmlWeekCount"], $currentWeekStart);
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

elseif($trackerMenu->getCurrentMode() == "both")
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

elseif($trackerMenu->getCurrentMode() == "curr")
{
    $displayer->displayStats($tracker->getStatData($currentWeekStart, $currentWeekEnd));
}

elseif($trackerMenu->getCurrentMode() == "last")
{
    $displayer->displayStats($tracker->getStatData($lastWeekStart, $lastWeekEnd));
}


echo "</div>
      </body>
      </html>";

?>