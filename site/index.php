<?php

require_once("classes/dbLinkClass.php");
require_once("classes/siteMenuClass.php");

$menuItems = array( "about"         => array(   "displayName"      => "about",
                                            "displayParent"    => null,
                                            "display"          => true),
                "ice"           => array(   "displayName"      => "Ice",
                                            "displayParent"    => null,
                                            "display"          => false),
                "pos"           => array(   "displayName"      => "POS Refuel",
                                            "displayParent"    => null,
                                            "display"          => false),
                "t2comps"       => array(   "displayName"      => "T2 Components",
                                            "displayParent"    => null,
                                            "display"          => true),
                "default"       => array(   "displayName"      => "about",
                                            "displayParent"    => null,
                                            "display"          => false));

$siteMenu = new siteMenu($menuItems);

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
    case "t2comps": include("t2comps.php");
                    break;
    case "t2ships": include("t2ships.php");
}

echo "</div>";
echo '<div style="clear: both;"></div>';
    
echo "</body>
</html>";

?>