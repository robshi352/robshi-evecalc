<?php
/*Copyright 2010 @ masubious*/

class posTracker
{
    function __construct($config)
    {
        require_once("classes/dbLinkClass.php");

        $this->userID   = $config["user"]["userID"];
        $this->apiKey   = $config["user"]["apiKey"];
        $this->charID   = $config["user"]["charID"];
        
        $this->db = new dbLink($config["db"]["host"],
                               $config["db"]["user"],
                               $config["db"]["password"],
                               $config["db"]["database"]);
        
        $this->prefix = $config["db"]["tablePrefix"];
    }

    function getPosData()
    {
        $posStates = array("Unanchored", "Offline", "Onlining", "Reinforced", "Online");
        
        $query = sprintf("SELECT posID, posName, state, updateTime, itemName, capacity
                          FROM %sTracking AS t1, eveNames AS t2, invTypes AS t3
                          WHERE t1.moonID = t2.itemID
                          AND t1.typeID = t3.typeID
                          ORDER BY itemName", $this->prefix);
        $this->db->query($query);
        $result = $this->db->result;
        while($line = mysql_fetch_assoc($result))
        {
            //get common info
            $posData[$line["posID"]]["info"]["location"] = $line["itemName"];
            $posData[$line["posID"]]["info"]["state"] = $posStates[$line["state"]];
            $posData[$line["posID"]]["info"]["name"] = $line["posName"];
            $posData[$line["posID"]]["info"]["update"] = $line["updateTime"];
            $posCap = $line["capacity"];
            $batchVolume = 0;

            //get fuel info
            $query = sprintf("SELECT typeName, fuelNeed, fuelQuantity, volume
                              FROM %sFuel AS t1, invTypes AS t2
                              WHERE t1.fuelID = t2.typeID
                              AND t1.posID = %s
                              ORDER BY typeName", $this->prefix, $line["posID"]);
            $this->db->query($query);
            //echo "<pre>".$query."</pre>";
            while($line2 = mysql_fetch_assoc($this->db->result))
            {
                $posData[$line["posID"]]["fuel"][$line2["typeName"]]["fuelQuantity"] = $line2["fuelQuantity"];
                $posData[$line["posID"]]["fuel"][$line2["typeName"]]["fuelNeed"] = $line2["fuelNeed"];
                $fuelVolume[$line2["typeName"]] = $line2["volume"];
                
                if ($line2["fuelNeed"] != 0)
                {
                    $fuelReserve  = $line2["fuelQuantity"] / $line2["fuelNeed"];
                    if ($min)
                        $min = min($fuelReserve, $min);
                    else
                        $min = $fuelReserve;
                    
                    $days = (int)($fuelReserve / 24);
                    $hours = (int)$fuelReserve % 24;
                    $posData[$line["posID"]]["fuel"][$line2["typeName"]]["fuelReserve"] = sprintf("<em>%02s</em>d<em>%02s</em>h", $days, $hours);
                    
                    $batchVolume += $line2["volume"] * $line2["fuelNeed"];
                }
                else
                    $posData[$line["posID"]]["fuel"][$line2["typeName"]]["fuelReserve"] = "NaN";
            }
            if ($min)
            {
                $days = (int)($min / 24);
                $hours = (int)$min % 24;
                $posData[$line["posID"]]["minFuel"] = sprintf("<em>%02s</em>d<em>%02s</em>h", $days, $hours);
                unset($min);
            }
            
            //echo "<pre>";
            //print_r($fuelVolume);
            //echo "</pre>";
            
            //refuel calculation
            if ($batchVolume)
            {
                $batchCount = (int)($posCap / $batchVolume);
                foreach($posData[$line["posID"]]["fuel"] as $fuelName => $fuelInfo)
                {
                    if ($fuelName != "Strontium Clathrates")
                    {
                        $opt = $fuelInfo["fuelNeed"] * $batchCount;
                        $posData[$line["posID"]]["fuel"][$fuelName]["refill"] = $opt - $fuelInfo["fuelQuantity"];
                        $posData[$line["posID"]]["fuel"][$fuelName]["refillVolume"] = ($opt - $fuelInfo["fuelQuantity"]) * $fuelVolume[$fuelName];
                    }
                }
            }
        }
        return $posData;
    }
    
    function getFuelData()
    {
        
        //fetch total fuel need
        $query = sprintf("SELECT typeName as fuelName, sum(fuelNeed) as totalNeed
                          FROM %sFuel AS t1, invTypes AS t2
                          WHERE t1.fuelID = t2.typeID
                          GROUP BY fuelID
                          ORDER BY fuelName", $this->prefix);
        $this->db->query($query);
        
        while($line = mysql_fetch_assoc($this->db->result))
        {
            $fuelData[$line["fuelName"]]["need"] = $line["totalNeed"];
        }
        
        //fetch fuel locations
        $query = sprintf("SELECT itemName, t3.typeName AS containerName, t4.typeName AS fuelName, fuelQuantity, flagName
                          FROM %sFuelReserves AS t1, eveNames AS t2, invTypes AS t3, invTypes AS t4, invFlags AS t5
                          WHERE t1.locationID = t2.itemID
                          AND t1.containerID = t3.typeID
                          AND t1.fuelID = t4.typeID
                          AND t1.flag = t5.flagID
                          ORDER BY fuelName, itemName", $this->prefix);
        $this->db->query($query);
        
        while($line = mysql_fetch_assoc($this->db->result))
        {
            $fuelData[$line["fuelName"]]["info"][$line["itemName"]][$line["containerName"]][$line["flagName"]] = $line["fuelQuantity"];
            
            $fuelData[$line["fuelName"]]["quantity"] += $line["fuelQuantity"];
        }
        
        return $fuelData;
    }

    function track()
    {
        /* POS States
           0 - Unanchored
           1 - Anchored / Offline
           2 - Onlining
           3 - Reinforced
           4 - Online
        */
        $now = time();
        $query = "SELECT max(updateTime)
                  FROM ".$this->prefix."Tracking";
        $this->db->query($query);
        $line = mysql_fetch_assoc($this->db->result);
        $lastUpdate = strtotime($line["max(updateTime)"]);
        $trackingInfo = false;
        
        //only run update if at least 2 hours have passed
        if ($now - $lastUpdate > 7200)
        {
            try
            {
                $this->ale = AleFactory::getEVEOnline();
                //set user credentials, third parameter $characterID is also possible;
                $this->ale->setCredentials($this->userID, $this->apiKey, $this->charID);
                
                $oldPosList = array();
                $newPosList = array();
                
                $query = "SELECT posID
                          FROM ".$this->prefix."Tracking";
                $this->db->query($query);
                while($line = mysql_fetch_assoc($this->db->result))
                {
                    $oldPosList[] = $line["posID"];
                }
                // get old values from the db
                $query = "SELECT t1.posID, t2.fuelID, t2.fuelQuantity
                          FROM ".$this->prefix."Tracking AS t1, ".$this->prefix."Fuel AS t2
                          WHERE t1.posID = t2.posID";
                $this->db->query($query);
                
                while($line = mysql_fetch_assoc($this->db->result))
                {
                    $trackingInfo[$line["posID"]]["fuel"][$line["fuelID"]]["old"] = $line["fuelQuantity"];
                    if (!in_array($line["posID"], $oldPosList))
                        $oldPosList[] = $line["posID"];
                }
                
                $posList = $this->ale->corp->StarbaseList();
                
                foreach ($posList->result->starbases as $pos)
                {
                    $newPosList[] = $pos->itemID;
                    
                    $posDetails = $this->ale->corp->StarbaseDetail(array("itemID" => $pos->itemID));
    
                    foreach($posDetails->result->fuel as $fuel)
                    {
                        $trackingInfo[$pos->itemID]["fuel"][$fuel->typeID]["new"] = $fuel->quantity;
                    }
                    unset($posDetails);
                    
                    $trackingInfo[$pos->itemID]["info"]["locationID"] = $pos->locationID;
                    $trackingInfo[$pos->itemID]["info"]["moonID"] = $pos->moonID;
                    $trackingInfo[$pos->itemID]["info"]["typeID"] = $pos->typeID;
                    $trackingInfo[$pos->itemID]["info"]["cached"] = $pos->cachedUntil;
                    $trackingInfo[$pos->itemID]["info"]["state"] = $pos->state;
                    //$trackingInfo[$pos->itemID]["info"]["updateTime"] = date("o-m-d H:i:s", $now);
                }
                unset($posList);
                //update quantities & fuel needs for the pos
                
                //echo "<pre>";
                //print_r($trackingInfo);
                //echo "</pre>";
                
                foreach($trackingInfo as $posID => $posInfo)
                {
                    // update posDetails
                    if (!in_array($posID, $oldPosList))
                    {
                        // first entry / new pos
                        $query = sprintf("INSERT INTO %sTracking
                                          VALUES(%s, %s, '%s', %s, %s, %s, %s)", $this->prefix,
                                                                                 $posID,
                                                                                 $posInfo["info"]["state"],
                                                                                 date("o-m-d H:i:s", $now),
                                                                                 $posInfo["info"]["locationID"],
                                                                                 $posInfo["info"]["moonID"],
                                                                                 $posInfo["info"]["typeID"],
                                                                                 "");
                        $this->db->query($query);
                        
                        if ($posInfo["fuel"])
                        {
                            $firstrun = true;
                            $query = "INSERT INTO ".$this->prefix."Fuel
                                      VALUES ";
                            foreach($posInfo["fuel"] as $fuelID => $fuelInfo)
                            {
                                if (!$firstrun)
                                    $query .= ",";
                                $query .= sprintf("(%s, %s, %s, %s)", $posID, $fuelID, 0, $fuelInfo["new"]);
                                $firstrun = false;
                            }
                            $this->db->query($query);
                        }
                    }
                    else
                    {
                        // pos update
                        $query = sprintf("UPDATE %sTracking
                                          SET state = %s,
                                              updateTime = '%s'
                                          WHERE posID = %s", $this->prefix, $posInfo["info"]["state"], date("o-m-d H:i:s", $now), $posID);
                        $this->db->query($query);
                        
                        if ($posInfo["fuel"])
                        {
                            //use robotics count to guess how much time has passed
                            $query = sprintf("SELECT fuelID, fuelQuantity
                                              FROM %sFuel AS t1, invTypes AS t2
                                              WHERE posID = %s
                                              AND t1.fuelID = t2.typeID
                                              AND t2.typeName = 'Robotics'", $this->prefix,
                                                                             $posID);
                            $this->db->query($query);
                            $line = mysql_fetch_assoc($this->db->result);
                            
                            //estimate time difference in robotics that have been used
                            $timeDiffTicks = $line["fuelQuantity"] - $trackingInfo[$posID]["fuel"][$line["fuelID"]]["new"];
                            
                            //update if time has passed
                            if ($timeDiffTicks > 0)
                            {
                                foreach($posInfo["fuel"] as $fuelID => $fuelInfo)
                                {
                                    $fuelNeed = ($fuelInfo["old"] - $fuelInfo["new"]) / $timeDiffTicks;
                                    $trackingInfo[$posID]["fuel"][$fuelID]["need"] = $fuelNeed;
                                    
                                    //update if there is a positive need, negative need = stuff been put in the pos
                                    if ($fuelNeed >= 0)
                                    {
                                        $query = sprintf("UPDATE %sFuel
                                                          SET fuelQuantity = %s,
                                                              fuelNeed = %s
                                                          WHERE posID = %s
                                                          AND fuelID = %s", $this->prefix, $fuelInfo["new"], $fuelNeed, $posID, $fuelID);
                                        $this->db->query($query);
                                        //echo "<pre>".$query."</pre>";
                                    }
                                    unset($fuelNeed);
                                }
                            }
                            //refill happened
                            elseif($timeDiffTicks < 0)
                            {
                                foreach($posInfo["fuel"] as $fuelID => $fuelInfo)
                                {
                                    $query = sprintf("UPDATE %sFuel
                                                      SET fuelQuantity = %s
                                                      WHERE posID = %s
                                                      AND fuelID = %s", $this->prefix, $fuelInfo["new"], $posID, $fuelID);
                                    $this->db->query($query);
                                }
                            }
                        }
                    }
                }
                
                // check for disappeared pos's
                foreach($newPosList as $posID)
                {
                    if (in_array($posID, $oldPosList))
                        unset($oldPosList[array_search($posID, $oldPosList)]);
                }
                foreach($oldPosList as $posID)
                {
                    $query = sprintf("DELETE FROM %sTracking
                                      WHERE posID = %s", $this->prefix, $posID);
                    //echo "<pre>".$query."</pre>";
                    $this->db->query($query);
                    $query = sprintf("DELETE FROM %sFuel
                                      WHERE posID = %s", $this->prefix, $posID);
                    //echo "<pre>".$query."</pre>";
                    $this->db->query($query);
                }
                
                unset($oldPosList);
                unset($newPosList);
                
                /////////////////////////////////////
                // check asset api for available fuel
                $query = sprintf("SELECT distinct fuelID
                                  FROM %sFuel", $this->prefix);
                $this->db->query($query);
                
                $fuelList = array();
                while ($line = mysql_fetch_assoc($this->db->result))
                {
                    $this->fuelList[] = $line["fuelID"];
                }
                
                //iterate through assets, count fuel
                $corpAssets = $this->ale->corp->AssetList();
                $assets = $corpAssets->result->assets;
                
                $this->countItems($assets, null, null, null);
                
                //echo "<pre>";
                //print_r($this->fuelCount);
                //echo "</pre>";
                
                unset($corpAssets);
                unset($assets);
                
                //foreach($fuelCount)
                
                $query = sprintf("TRUNCATE TABLE %sFuelReserves", $this->prefix);
                $this->db->query($query);
                
                foreach($this->fuelCount as $fuelID => $fuelDetails)
                {
                    foreach($fuelDetails as $locationID => $locationDetails)
                    {
                        foreach($locationDetails as $containerID => $containerDetails)
                        {
                            foreach($containerDetails as $flag => $quantity)
                            {
                                //check through aggregated flags, only add warehouse / other divisions
                                $flags = explode("-", $flag);
                                $containers = explode("-", $containerID);
                                
                                $hangar = 0;
                                
                                foreach($flags as $flagID)
                                {
                                    if (($flagID == 4) or (($flagID >= 116) and ($flagID <= 121)))
                                        $hangar = $flagID;
                                }
                                
                                $container = end($containers);
                                
                                if (($locationID >= 66000000) and ($locationID <= 67000000))
                                    $locationID -= 6000001;
                                
                                //hangar 0, possibly a POS
                                if ($hangar != 0)
                                {
                                    $query = sprintf("INSERT INTO %sFuelReserves
                                                      VALUES(%s, %s, %s, %s, %s)", $this->prefix,
                                                                                   $locationID,
                                                                                   $container,
                                                                                   $hangar,
                                                                                   $fuelID,
                                                                                   $quantity);
                                    $this->db->query($query);
                                }
                            }
                        }
                    }
                }
                //echo "<pre>";
                //print_r($this->fuelCount);
                //echo "</pre>";
            }
            catch(Exception $e)
            {
                echo $e->getMessage();
            }
        }
        else
            $trackingInfo = (int)((7200 - $now + $lastUpdate) / 60);
        return $trackingInfo;
    }
    
    function countItems($items, $locationID, $containerID, $flag)
    {
        foreach($items as $item)
        {
            if ($item->contents)
            {
                if ($item->locationID)
                    $this->countItems($item->contents, $item->locationID, $item->typeID, $item->flag);
                else
                    $this->countItems($item->contents, $locationID, $containerID."-".$item->typeID, $flag."-".$item->flag);
            }
            else
                if (in_array($item->typeID, $this->fuelList))
                    $this->fuelCount[$item->typeID][$locationID][$containerID][$flag."-".$item->flag] += $item->quantity;
        }
    }
}
?>