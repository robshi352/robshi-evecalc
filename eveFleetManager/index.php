<?php

/* TODO: - script security (sql injections etc)
         
    V2:  - Class Redesign (Display Class, Fleet Class)
         - sprintf useage
         - statistics
         - preferred settings
         - admin(?)
         - fleet Builder (select Ships / fittings you want & display who is in what
            - generate fleetList with Ship
            - Signup by that list (just expected number of fits)*/


$config = parse_ini_file("efm.ini", true);
require_once("classes/efmClass.php");
require_once("classes/menuClass.php");
require_once("classes/efmDisplayerClass.php");

define("MEMBER", 1);
define("SCOUT", 2);
define("COLEADER", 3);
define("LEADER", 4);

if(preg_match('#\bEVE\-IGB$#i', $_SERVER['HTTP_USER_AGENT']))
    define('IGB', true);
else
    define('IGB', false);

function requestTrust()
{
    $requestURL = "http://";
    $requestURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    
    echo '<script type="text/javascript">';
    echo "CCPEVE.requestTrust('".$requestURL."')";
    echo "</script>";
}

//menu short => (description, viewable if in fleet, minimum security to view)
$menuFunctions = array("create" => array("description"  => "Create Fleet",
                                         "inProgress"   => false,
                                         "security"     => MEMBER),
                       "change" => array("description"  => "change Ship",
                                         "inProgress"   => true,
                                         "security"     => MEMBER),
                       "manage" => array("description"  => "Manage Fleet",
                                         "inProgress"   => true,
                                         "security"     => COLEADER),
                       "view"   => array("description"  => "E-War Overview",
                                         "inProgress"   => true,
                                         "security"     => MEMBER),
                       "ships"  => array("description"  => "Ship Overview",
                                         "inProgress"   => true,
                                         "security"     => MEMBER),
                       "join"   => array("description"  => "Join a Fleet",
                                         "inProgress"   => false,
                                         "security"     => MEMBER),
                       "leave"  => array("description"  => "Leave Fleet",
                                         "inProgress"   => true,
                                         "security"     => MEMBER),
                       "end"    => array("description"  => "End Fleet",
                                         "inProgress"   => true,
                                         "security"     => COLEADER));

$efm = new efm($config);
$menu = new menu($menuFunctions, $efm->isFleetInProgress(), $efm->getPilotRole());
$efmDisplayer = new efmDisplayer($menu->getCurrentFunction());


//eval forms and redirect etc
if (($menu->getCurrentFunction() == "create") && $efm->evalCreateForm())
{
    $efm->createFleet();
    header("Location: http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."?func=view");
    echo sprintf('If you\'re not being redirected, click <a href="%s">here</a>', $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."?func=view");
    exit;
}

elseif(($menu->getCurrentFunction() == "join") && $efm->evalJoinForm())
{
    $efm->joinFleet();
    header("Location: http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."?func=view");
    echo sprintf('If you\'re not being redirected, click <a href="%s">here</a>', $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."?func=view");
    exit;
}

elseif(($menu->getCurrentFunction() == "end") && $efm->endFleetConfirmed())
{
    $efm->endFleet();
    header("Location: http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]);
    echo sprintf('If you\'re not being redirected, click <a href="%s">here</a>', $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]);
    exit;
}

elseif(($menu->getCurrentFunction() == "leave") && $efm->leaveFleetConfirmed())
{
    $efm->leaveFleet();
    header("Location: http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]);
    echo sprintf('If you\'re not being redirected, click <a href="%s">here</a>', $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]);
    exit;
}

elseif(($menu->getCurrentFunction() == "change") && $efm->evalChangeShipForm())
{
    $efm->changeShip();
}

elseif(($menu->getCurrentFunction() == "manage") && $efm->evalFleetManagementForm())
{
    $efm->updateFleet();
}

elseif(($menu->getCurrentFunction() == "manage") && $efm->kickConfirmed())
{
    $efm->kick();
    header("Location: http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."?func=manage");
    echo sprintf('If you\'re not being redirected, click <a href="%s">here</a>', $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."?func=manage");
}

//html header
echo '<html>
        <head>
            <title>Eve Fleet Manager</title>
            <link rel="stylesheet" href="css/efm.css" type="text/css" media="screen, projection">';

if ($efm->scoutMode() &&
    ($menu->getCurrentFunction() == "view" ||
     $menu->getCurrentFunction() == "leave" ||
     $menu->getCurrentFunction() == "end" ||
     $menu->getCurrentFunction() == "join"))
    printf('<meta http-equiv="Refresh" content="%s">', max($config["general"]["fleetRefresh"], 10));

echo "</head>";
echo sprintf("<body onload=\"CCPEVE.requestTrust('%s')\">", "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);

    //if script has no trust yet & we use the IGB, request trust
    if ($_SERVER["HTTP_EVE_TRUSTED"] == "No")
    {
        echo "Sorry, need trust to operate";
        echo "</body>";
        echo "</html>";
        exit;
    }

    //chancel if not in IGB mode
    if (!IGB)
    {
        echo "Sorry, will work in the IGB only for now.";
        echo "</body>";
        echo "</html>";
        exit;
    }
    
    //display menu
    echo '<div id="menuContainer" style="clear: both;">';

    if ($efm->isFleetInProgress())
        $efmDisplayer->displayFleetInfo($efm->getFleetInfo(), $efm->getPilotRole());
    
    if ($efm->scoutMode())
    {
        $efm->updateScoutPosition();
        $efmDisplayer->displayScoutPosition($efm->getScoutPosition());
    }
        
    $menu->display($efm->isFleetInProgress());

    echo "</div>";

    echo '<div id="contentContainer" style="clear: both;">';    
    echo '<div id="content">';

    if ($menu->getCurrentFunction() == "create")
    {
        if ($efm->hasCreateRights())
            $efmDisplayer->displayCreateForm($efm->getCreateFormData(), $efm->getCreateFormError());
        else
            echo "Sorry, you don't have the rights to create a Fleet";
    }
    
    elseif ($menu->getCurrentFunction() == "join")
    {
        $efmDisplayer->displayJoinForm($efm->getJoinFormData(), $efm->getJoinFormError(), $efm->getActiveFleets());
    }
    
    elseif ($menu->getCurrentFunction() == "view")
    {
        if ($_GET["mode"] == 2)
            $efmDisplayer->displayVertEWar($efm->getFleetEWar(), $efm->getPilotName());
        elseif ($_GET["mode"] == 3)
            $efmDisplayer->displayVertEWar2($efm->getFleetEWar(), $efm->getPilotName());
        else
            $efmDisplayer->displayEWar($efm->getFleetEWar(), $efm->getPilotName());
    }

    elseif ($menu->getCurrentFunction() == "ships")
    {
        $efmDisplayer->displayShips($efm->getFleetShips());
    }    

    elseif ($menu->getCurrentFunction() == "end")
    {
        $efmDisplayer->displayEndConfirm($efm->getPilotRole());
    }
    
    elseif ($menu->getCurrentFunction() == "leave")
    {
        $efmDisplayer->displayLeaveConfirm($efm->getPilotRole());
    }
    
    elseif ($menu->getCurrentFunction() == "change")
    {
        $efmDisplayer->displayChangeShipForm($efm->getChangeFormData(), $efm->getChangeFormError());
    }
    
    elseif ($menu->getCurrentFunction() == "manage")
    {
        if ($efm->kickMode())
            $efmDisplayer->displayKickConfirm();
        else
            $efmDisplayer->displayFleetManagementForm($efm->getManageFormData(), $efm->getManageFormError());
    }
    
    else
    {
        $efmDisplayer->displayCredits();
    }
    
    echo "</div>"; // emd content
    echo "</div>"; // end content container
    
    echo '<div id="footerContainer" style="clear: both;">';
    echo "</div>";

    echo "</body>";
    echo "</html>";
?>