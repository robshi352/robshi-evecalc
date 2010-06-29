<?php

class assetSpy
{
    function assetSpy($config)
    {
        require_once("ale/factory.php");
        require_once("classes/dbLinkClass.php");
        $this->config = $config;
        
        $this->db = new dbLink($this->config["db"]["host"],
                               $this->config["db"]["user"],
                               $this->config["db"]["password"],
                               $this->config["db"]["database"]);
    }
    
    function getAPIData()
    {
        try
        {   
            $ale = AleFactory::getEVEOnline();
            //set user credentials, third parameter $characterID is also possible;
            $ale->setCredentials($this->config["user"]["userID"],
                                 $this->config["user"]["apiKey"]);
            
            //all errors are handled by exceptions
            //let's fetch characters first.
            $account = $ale->account->Characters();
            //you can traverse rowset element with attribute name="characters" as array
            foreach ($account->result->characters as $character)
            {
                //this is how you can get attributes of element
                $characterID = (string) $character->characterID;
                //set characterID for CharacterSheet
                $ale->setCharacterID($characterID);
                //$characterSheet = $ale->char->CharacterSheet();
                if($character->name == $this->config["user"]["charName"])
                {
                    break;
                }
            }
            
            $this->corpSheet = $ale->corp->CorporationSheet();
            
            $this->assets = $ale->corp->assetList();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    
    function displayLocations()
    {
        foreach($this->assets->result->assets as $location)
        {
            $locationID = $location->locationID;
            
            if (($locationID >= 30000000) && ($locationID <= 39999999))
            {
                //location is a Solar System
                $query = "SELECT solarSystemName
                          FROM mapSolarSystems
                          WHERE solarSystemID = ".$locationID;
                $this->db->query($query);
  
                if (mysql_num_rows($this->db->result) > 0)
                {
                    $line = mysql_fetch_assoc($this->db->result);
                    $locationName = $line["solarSystemName"];
                }
                else
                    $locationName = "No Location found";
            }
            else
            {
                //location is a Station
                if (($locationID >= 66000000) && ($locationID <= 66999999))
                {
                    $locationID -= 6000001;
                }
                
                $query = "SELECT stationName
                          FROM staStations
                          WHERE stationID = ".$locationID;
                $this->db->query($query);
    
                if (mysql_num_rows($this->db->result) > 0)
                {
                    $line = mysql_fetch_assoc($this->db->result);
                    $locationName = $line["stationName"];
                }
                else
                    $locationName = "No Location Found";
            }
            
            if ($location->contents)
            {
                $query = "SELECT typeName
                          FROM invTypes
                          WHERE typeID = ".$location->typeID;
                $this->db->query($query);
                
                if (mysql_num_rows($this->db->result) > 0)
                {
                    $line = mysql_fetch_assoc($this->db->result);
                    $typeName = $line["typeName"];
                }
                else
                    $typeName = "No Type found";
                
                echo "<u>".$location->locationID." (".$locationID.") ".$locationName." - ".$typeName."</u><br>";
    
                foreach($location->contents as $content)
                {
                    $query = "SELECT typeName
                              FROM invTypes
                              WHERE typeID = ".$content->typeID;
                    $this->db->query($query);
                    
                    if (mysql_num_rows($this->db->result) > 0)
                    {
                        $line = mysql_fetch_assoc($this->db->result);
                        $typeName = $line["typeName"];
                    }
                    else
                        $typeName = "No typeName found";
                    
                    $query = "SELECT flagText
                              FROM invFlags
                              WHERE flagID = ".$content->flag;
                    $this->db->query($query);
                    
                    if (mysql_num_rows($this->db->result) > 0)
                    {
                        $line = mysql_fetch_assoc($this->db->result);
                        $flagText = $line["flagText"];
                    }
                    else
                        $flagText = "No flagText found";
                    
                    $contentTypes[$content->flag][$content->typeID] += $content->quantity;
                    $contentNames[$content->typeID] = $typeName;
                    $contentFlags[$content->flag] = $flagText;
                }
                
                foreach($contentTypes as $flag => $contentType)
                {
                    echo "--<b>".$contentFlags[$flag]."</b><br>";
                    
                    foreach($contentType as $typeID => $quantity)
                    {
                        echo "----".$contentNames[$typeID].":  ".$quantity."<br>";
                    }
                }
                
                unset($contentTypes, $contentNames, $contentFlags);
                
                //echo " | Flag: ".$flagText." (".$content->flag.")<br>";

            }
        }
    }
    
}


?>