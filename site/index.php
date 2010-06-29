<?php

require_once("classes/dbLinkClass.php");
require_once("classes/siteFunctionsClass.php");

    
    $functions = array("about"      => array( "description" => "about",
                                              "file"        => "about.php",
                                              "visible"     => true),
                       "ice"        => array( "description" => "Ice Reprocessing",
                                              "file"        => "ice.php",
                                              "visible"     => false),
                       "pos"        => array( "description" => "Pos Refill Calculations",
                                              "file"        => "pos.php",
                                              "visible"     => true),
                       "station"    => array( "description" => "Station Locator",
                                              "file"        => "station.php",
                                              "visible"     => true),
                       "agent"      => array( "description" => "Agent Locator",
                                              "file"        => "agent.php",
                                              "visible"     => true),
                       "t2comps"    => array( "description" => "T2 Comp Calculation",
                                              "file"        => "comp.php",
                                              "visible"     => true));

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
                include($siteFunctions->getFunctionFile());                
            }
            echo "</div>";
            echo "<div style='clear: both;'></div>";
        ?>
    
    </body>
</html>