<?php

require_once("classes/dbLinkClass.php");
$config = parse_ini_file("tracker.ini", true);

$db = new dbLink($config["db"]["host"],
         $config["db"]["user"],
         $config["db"]["password"],
         $config["db"]["database"]);

$this->prefix = $config["db"]["tablePrefix"];

$query = "CREATE TABLE IF NOT EXISTS ".$config["db"]["tablePrefix"]."Activitytracking (
            jobID int(11) NOT NULL,
            activityID int(2) NOT NULL,
            installerID int(11) NOT NULL,
            beginProductionTime datetime DEFAULT NULL,
            endProductionTime datetime DEFAULT NULL,
            entryDate datetime DEFAULT NULL,
            outputTypeID int(11) NOT NULL,
            completedStatus tinyint(1) DEFAULT NULL,
            PRIMARY KEY (jobID)
          )";
$db->query($query);

$query = "CREATE TABLE IF NOT EXISTS ".$config["db"]["tablePrefix"]."MemberID(
            memberID int(11) NOT NULL,
            memberName char(50) NOT NULL,
            PRIMARY KEY (memberID)
          )";
$db->query($query);

echo "Setup Complete";

?>