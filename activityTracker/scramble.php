<?php

$config = parse_ini_file("tracker.ini", true);

require_once("classes/dbLinkClass.php");
$prefix = $config["db"]["tablePrefix"];

$this->db = new dbLink($config["db"]["host"],
         $config["db"]["user"],
         $config["db"]["password"],
         $config["db"]["database"]);

//scramble user names

?>