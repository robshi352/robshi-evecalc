<?php

class t2ShipCalc
{
    function __construct($config)
    {
        require_once("classes/dbLinkClass.php");
        $config = parse_ini_file("eveTools.ini", true);

        $this->db = new dbLink($config["db"]["host"], $config["db"]["user"], $config["db"]["password"], $config["db"]["database"]);
    }
    
    function getShipInventionData()
    {
        
    }
}

?>