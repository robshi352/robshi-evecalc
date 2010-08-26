<?php
/*Copyright 2010 @ masubious*/

require_once("ale/factory.php");
require_once("classes/iceTrackerClass.php");
require_once("classes/iceDisplayerClass.php");
require_once("classes/menuClass.php");

$modes = array( "stats"         => array(   "displayName"      => "Stats",
                                            "displayParent"    => null,
                                            "display"          => true),
                "graph"         => array(   "displayName"      => "Graphs",
                                            "displayParent"    => null,
                                            "display"          => true),
                "track"         => array(   "displayName"      => "Update",
                                            "displayParent"    => null,
                                            "display"          => false),
                "default"       => array(   "displayName"      => "stats",
                                            "displayParent"    => null,
                                            "display"          => false));

$config = parse_ini_file("ice.ini", true);

$menu = new menu($modes);
$tracker = new iceTracker($config);
$displayer = new iceDisplayer();

echo '<html>
        <header>
            <title>Invention &amp; Production Tracker</title>
            <link rel="stylesheet" href="css/ice.css">
        </header>
        
        <body>';

$menu->display();

echo '<div id="content">';

if ($menu->getCurrentMode() == "track")
{
    $tracker->track();
}

echo "</div>
      </body>
      </html>";

?>