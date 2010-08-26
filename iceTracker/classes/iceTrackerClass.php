<?php

class iceTracker
{
    function __construct($config)
    {
        require_once("classes/dbLinkClass.php");

        $this->userID   = $config["user"]["userID"];
        $this->apiKey   = $config["user"]["apiKey"];
        $this->charName = $config["user"]["charName"];
        $this->charID   = $config["user"]["charID"];
        
        $this->db = new dbLink($config["db"]["host"],
                 $config["db"]["user"],
                 $config["db"]["password"],
                 $config["db"]["database"]);
        
        $this->prefix = $config["db"]["tablePrefix"];
    }
    
    function track()
    {
        try
        {   
            $this->ale = AleFactory::getEVEOnline();
            //set user credentials, third parameter $characterID is also possible;
            $this->ale->setCredentials($this->userID, $this->apiKey, $this->charID);
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
        
        $assets = $this->ale->char->AssetList();
        
        foreach($assets->result->assets as $asset)
        {
            //echo "<em>".$asset."</em><br>";
            //print_r($asset);
        }
    }
}
?>