<?php

require_once("classes/dbLinkClass.php");
require_once("classes/siteMenuClass.php");

$modes = array( "about"         => array(   "displayName"      => "about",
                                            "displayParent"    => null,
                                            "display"          => true),
                "ice"           => array(   "displayName"      => "Ice",
                                            "displayParent"    => null,
                                            "display"          => false),
                "pos"           => array(   "displayName"      => "POS Refuel",
                                            "displayParent"    => null,
                                            "display"          => true),
                "t2comps"       => array(   "displayName"      => "T2 Components",
                                            "displayParent"    => null,
                                            "display"          => true),
                "default"       => array(   "displayName"      => "about",
                                            "displayParent"    => null,
                                            "display"          => false));

//$functions = array("about"      => array( "description" => "about",
//                                          "file"        => "about.php",
//                                          "visible"     => true),
//                   "ice"        => array( "description" => "Ice Reprocessing",
//                                          "file"        => "ice.php",
//                                          "visible"     => false),
//                   "pos"        => array( "description" => "Pos Refill Calculations",
//                                          "file"        => "pos.php",
//                                          "visible"     => true),
//                   "station"    => array( "description" => "Station Locator",
//                                          "file"        => "station.php",
//                                          "visible"     => true),
//                   "agent"      => array( "description" => "Agent Locator",
//                                          "file"        => "agent.php",
//                                          "visible"     => true),
//                   "t2comps"    => array( "description" => "T2 Comp Calculation",
//                                          "file"        => "comp.php",
//                                          "visible"     => true));

$config = parse_ini_file("eveTools.ini", true);

$db = new dbLink($config["db"]["host"], $config["db"]["user"], $config["db"]["password"], $config["db"]["database"]);
$siteMenu = new siteMenu($modes);

echo '<html>
    <head>
        <title>Misc. Eve Tools</title>
        <link rel="stylesheet" href="css/toolset.css">
    </head>
    <body>';

$siteMenu->display();

echo '<div id="content">';

switch ($siteMenu->getCurrentMode())
{
    case "about": include("about.php");
                    break;
    case "pos": include("pos.php");
                    break;
    case "t2comps": include("comp.php");
}

echo "</div>";
echo '<div style="clear: both;"></div>';

    
echo "</body>
</html>";

?>