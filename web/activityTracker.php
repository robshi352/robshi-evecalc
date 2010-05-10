<?php

$info = $_GET["info"];

echo "<html>
        <header>
            <title>Invention &amp; Production Tracker</title>
            <link rel='stylesheet' href='css/greyscale.css'>

        </header>
        
        <body>";

require_once("ale/factory.php");
require_once("classes/dbLink.php");
require_once("classes/pecoTracker.php");
require_once("classes/trackerMenu.php");

$config = parse_ini_file("tracker.ini", true);

//setup modes and styles for the menu
$modes = array("both" => "Both", "inv" => "Invention", "prod" => "Production", "default" => "both");
$styles = array("table" => "Table", "text" => "Text", "default" => "table");

$trackerMenu = new trackerMenu($modes, $styles);
$trackerMenu->getCurrent();

$db = new dbLink($config["db"]["host"], $config["db"]["user"], $config["db"]["password"], $config["db"]["database"]);
$tracker = new pecoTracker($config["user"]["userID"], $config["user"]["apiKey"], $config["user"]["charName"], $db);


if ($trackerMenu->currentStyle == "table")
{
    echo "<div id=menu>";
    $trackerMenu->display();
    if ($info)
        echo " | <a href=".$PHP_SELF."?mode=".$trackerMenu->currentMode."&style=".$trackerMenu->currentStyle.">hide Info</a>";
    else
        echo " | <a href=".$PHP_SELF."?mode=".$trackerMenu->currentMode."&style=".$trackerMenu->currentStyle."&info=show>show Info</a>";
    echo "</div>";
}
elseif ($trackerMenu->currentStyle == "text")
{
    $trackerMenu->display();
    echo "<br><br>";
}


if ($info)
{
    echo "<div id=info>
            <u><b>Random Notes</b></u>
            <ul>
                <li>Update once a day, roughly 10:30 GMT</li>
                <li>Inventions will only be tracked if they're delivered at the time of an update</li>
                <li>Utilization = (sum of time manufacturing / (Time in a week * 10))</li>
                <li>Utilization is based on 10 manufacturing jobs</li>
            </ul>
          </div>";
}

if ($trackerMenu->currentMode == "track")
    $tracker->track();

elseif($trackerMenu->currentMode == "inv")
    $tracker->inventionStatus($trackerMenu->currentStyle);

elseif($trackerMenu->currentMode == "prod")
    $tracker->productionStatus($trackerMenu->currentStyle);

else
{
    $tracker->inventionStatus($trackerMenu->currentStyle);
    $tracker->productionStatus($trackerMenu->currentStyle);
}

echo "  </body>
      </html>";

?>