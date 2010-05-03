<?php

require_once("classes/dbLink.php");
require_once("classes/siteFunctions.php");

    
    $functions = array("about" => array("about", "about.php"),
                       "ice" => array("Ice Calculation", "ice.php"),
                       "pos" => array("POS Refill Calculation", "pos.php"),
                       "station" => array("Station Locator", "station.php"),
                       "agent" => array("Agent Locator", "agent.php"));
    
    $db = new dbLink("localhost", "root", "", "eve_online");
    $siteFunctions = new siteFunctions($functions);
    $currentFunction = $_GET["func"];
?>

<html>
    <head>
        <title>Misc. Eve Tools</title>
    </head>
    <body>
    <!-- MENU -->
        <?php
            $siteFunctions->printFunctions();
            echo "<br><br>";
            if ($siteFunctions->getFile($currentFunction) != "")
            {
                include($siteFunctions->getFile($currentFunction));                
            }
        ?>
    
    </body>
</html>


<?php
    //FREE MYSQL CONNECTION
    unset($db);
?>