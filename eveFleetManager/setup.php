<?php

if (isset($_GET["eve"]))
{
    $config = parse_ini_file("efm.ini", true);
    require_once("classes/dbLinkClass.php");
    $eveFiles = array("sql/tyr101-eveGraphics-mysql5-v1.sql",
                      "sql/tyr101-invGroups-mysql5-v1.sql",
                      "sql/tyr101-invTypes-mysql5-v1.sql",
                      "sql/tyr101-mapSolarSystems-mysql5-v1.sql");
    
    
    $db = new dbLink($config["db"]["host"],
                     $config["db"]["user"],
                     $config["db"]["password"],
                     $config["db"]["database"]);
        
    if ($_GET["eve"] == "yes")
    {
        foreach($eveFiles as $fileName)
        {
            $file = file_get_contents($fileName);
            $queries = explode(";\n", $file);
            array_pop($queries);
            
            foreach($queries as $query)
            {
                $db->query($query);
            }
            echo $fileName." imported<br>";
        }
    }
    $query = "CREATE TABLE IF NOT EXISTS `".$config["db"]["tablePrefix"]."FleetMembers` (
             `fleetID` int(11) DEFAULT NULL,
             `memberName` varchar(64) NOT NULL DEFAULT '',
             `memberRole` smallint(6) DEFAULT NULL,
             `memberJoin` datetime NOT NULL,
             `memberLeft` datetime DEFAULT NULL,
             `fittingLink` varchar(255) NOT NULL,
             `memberPositionID` int(11) NOT NULL,
             PRIMARY KEY (`memberName`,`memberJoin`),
             KEY `fleetID` (`fleetID`)
             )";
    $db->query($query);
    
    $query = "CREATE TABLE IF NOT EXISTS `".$config["db"]["tablePrefix"]."Fleets` (
             `fleetID` int(11) unsigned NOT NULL AUTO_INCREMENT,
             `fleetCreator` char(30) DEFAULT NULL,
             `fleetName` char(30) DEFAULT NULL,
             `fleetStart` datetime NOT NULL,
             `fleetEnd` datetime DEFAULT NULL,
             `fleetPass` char(64) NOT NULL,
             `fleetType` varchar(10) NOT NULL,
             PRIMARY KEY (`fleetID`),
             UNIQUE KEY `fleetID` (`fleetID`)
             )";
    $db->query($query);
    echo $config["db"]["tablePrefix"]."FleetMembers and ".$config["db"]["tablePrefix"]."Fleets created.<br>";
        echo "Setup complete. Have fun.";
}
else
{
    echo "Install Eve Static Data Dump Tables?";
    echo "<br>";
    echo "<a href=\"".$PHP_SELF."?eve=yes\">Yes</a>";
    echo " | ";
    echo "<a href=\"".$PHP_SELF."?eve=No\">No</a>";
}
?>