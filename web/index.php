<?php

require_once("classes/dbLinkClass.php");
require_once("classes/siteFunctionsClass.php");

    
    $functions = array("about" => array("about", "about.php"),
                       "ice" => array("Ice Calculation", "ice.php"),
                       "pos" => array("POS Refill Calculation", "pos.php"),
                       "station" => array("Station Locator", "station.php"),
                       "agent" => array("Agent Locator", "agent.php"),
                       "t1profit" => array("T1 Profit Calculation", "t1profit.php"),
                       "t2comps" => array("T2 Component Calculator", "comp.php"));

    $config = parse_ini_file("eveTools.ini", true);
    
    $db = new dbLink($config["db"]["host"], $config["db"]["user"], $config["db"]["password"], $config["db"]["database"]);
    $siteFunctions = new siteFunctions($functions);
    $siteFunctions->current();
?>

<html>
    <head>
        <title>Misc. Eve Tools</title>
        <link rel='stylesheet' href='css/toolset.css'>
    </head>
    <body>
        
    <!-- MENU -->
        <?php
            echo "<div id=menu>";
            $siteFunctions->display();
            echo "</div>";

            echo "<div id=content>";
            if ($siteFunctions->getFunctionFile() != "")
            {
                include($siteFunctions->getFunctionFile($currentFunction));                
            }
            echo "</div>";
            echo "<div style='clear: both;'></div>";
        ?>
    
    </body>
</html>