<?php

if (isset($_GET["eve"]))
{
    require_once("classes/dbLinkClass.php");
    $config = parse_ini_file("tracker.ini", true);
    
    $eveFiles = array("sql/tyr101-invTypes-mysql5-v1.sql");
    
    $db = new dbLink($config["db"]["host"],
             $config["db"]["user"],
             $config["db"]["password"],
             $config["db"]["database"]);
    
    $prefix = $config["db"]["tablePrefix"];
    
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
    
    $query = "CREATE TABLE IF NOT EXISTS `".$prefix."ActivityTracking` (
                `jobID` int(11) NOT NULL,
                `activityID` int(2) NOT NULL,
                `installerID` int(11) NOT NULL,
                `beginProductionTime` datetime DEFAULT NULL,
                `endProductionTime` datetime DEFAULT NULL,
                `entryDate` datetime DEFAULT NULL,
                `outputTypeID` int(11) NOT NULL,
                `completedStatus` tinyint(1) DEFAULT NULL,
                `timeFactor` float NOT NULL DEFAULT '1',
                PRIMARY KEY (`jobID`)
              )";
              
    $db->query($query);
    
    $query = "CREATE TABLE IF NOT EXISTS `".$prefix."MemberID`(
                `memberID` int(11) NOT NULL,
                `memberName` char(50) NOT NULL,
                PRIMARY KEY (`memberID`)
              )";
    $db->query($query);
    
    echo "Setup Complete";
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