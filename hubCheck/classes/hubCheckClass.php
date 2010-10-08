<?php

class hubCheck
{
    function __construct()
    {
        require_once("classes/dbLinkClass.php");
        $config = parse_ini_file("hubCheck.ini", true);
        $this->db = new dbLink($config["db"]["host"], $config["db"]["user"], $config["db"]["password"], $config["db"]["database"]);
        
        if ($_POST["formCount"])
            $this->formCount = $_POST["formCount"];
        else
            $this->formCount = 3;
        
        if ($_POST["add"])
            $this->formCount = $this->formCount + $_POST["formCountAdd"];
        
        if ($this->formCount < 3)
            $this->formCount = 3;
        
        if ($_POST["submit"] || $_POST["add"])
        {
            for($i = 0; $i < $this->formCount; $i++)
            {
                if ($_POST["hubItem".$i] != 0)
                    $this->selected[$i] = $_POST["hubItem".$i];
            }
        }
        
        $this->apiKey = "FF21D0199A20CA6539B47";
    }
    
    function formSubmitted()
    {
        return isset($_POST["submit"]);
    }
    
    function getItemList()
    {
        $itemList["formCount"] = $this->formCount;

        if ($this->selected)
        {
            foreach($this->selected as $i => $typeID)
            {
                $itemList["selected"][$i] = $typeID;
            }
        }
        
        //select the top level 'Ship Equipment' group
        $query = "SELECT COUNT(*)-1 AS level,
                         t1.lft AS lft,
                         t1.rgt AS rgt,
                         t3.marketGroupName
                  FROM invNestedMarketGroups AS t1,
                       invNestedMarketGroups AS t2,
                       invMarketGroups AS t3
                  WHERE t1.lft BETWEEN t2.lft AND t2.rgt
                  AND t1.marketGroupID = t3.marketGroupID
                  AND (t3.marketGroupName = 'Ship Equipment'
                    OR t3.marketGroupName = 'Drones'
                    OR t3.marketGroupName = 'Ships')
                  GROUP BY t1.lft";
        $this->db->query($query);
        
        while($line = mysql_fetch_assoc($this->db->result))
        {
            if ($line["level"] == 0)
            {
                $marketIDs[$line["marketGroupName"]]["lft"] = $line["lft"];
                $marketIDs[$line["marketGroupName"]]["rgt"] = $line["rgt"];
            }
        }
        
        foreach($marketIDs as $groupName => $groupInfo)
        {
            $query = "SELECT t1.typeID AS typeID, t1.typeName AS typeName
                      FROM invTypes AS t1,
                           invNestedMarketGroups AS t2,
                           invMetaTypes AS t3
                      WHERE t2.lft BETWEEN ".$groupInfo["lft"]." AND ".$groupInfo["rgt"]."
                      AND t1.marketGroupID = t2.marketGroupID
                      AND t1.typeID = t3.typeID
                      AND published = 1
                      AND t3.metaGroupID = 2
                      ORDER BY t1.typeName";
            $this->db->query($query);
            
            
            //echo "<pre>".$query."</pre>";
            while($line = mysql_fetch_assoc($this->db->result))
            {
                $itemList["items"][$line["typeID"]] = $line["typeName"];
            }
        }
        
        asort($itemList["items"]);
        
        return $itemList;
    }
    
    function getHubData()
    {
        $now = time();
        
        $query = "SELECT hubID, typeID, updateTime
                  FROM hubOrders";
        $this->db->query($query);

        while($line = mysql_fetch_assoc($this->db->result))
        {
            //blacklist recent items, so i don't query the eve metrics api
            $update[$line["hubID"]][$line["typeID"]] = ($now - strtotime($line["updateTime"])) > 3600;
            $indb[$line["hubID"]][$line["typeID"]] = true;
        }
        
        $query = "SELECT hubID, stationName, regionID
                  FROM hubList AS t1, staStations AS t2
                  WHERE t1.hubID = t2.stationID";
        $this->db->query($query);
        
        $regionList = array();
        
        while($line = mysql_fetch_assoc($this->db->result))
        {
            $hubList[$line["hubID"]] = $line["stationName"];
            if (!(in_array($line["regionID"], $regionList)))
                $regionList[] = $line["regionID"];
        }
        
        foreach(array_unique($this->selected) as $typeID)
        {
            
        }
        
        $typeIDs = implode(",", array_unique($this->selected));
        
        foreach($regionList as $regionID)
        {
            $apiURL = sprintf("http://eve-metrics.com/api/orders.xml?type_ids=%s&key=%s&region_ids=%s", $typeIDs,
                                                                                                        $this->apiKey,
                                                                                                        $regionID);
        }
        
        //    foreach($this->selected as $typeID)
        //    {
        //        $hubID = $line["hubID"];
        //        $hubName = $line["stationName"];
        //        $regionID = $line["regionID"];
        //        
        //        if (!$update[$line["hubID"]][$typeID])
        //        {
        //                                                                                                        
        //            echo "apiURL: ".$apiURL;
        //            
        //            $xml = simplexml_load_file($apiURL);
        //            
        //            foreach($xml->type->region->orders->order as $order)
        //            {
        //                if ($order["station_id"] == $hubID)
        //                    $min[$hubID][$typeID] = min($min[$hubID][$typeID], $order);
        //                
        //                //printf("station:%s - qty:%s - price:%s<br>", $order["station_id"],
        //                //                                                         $order["available_volume"],
        //                //                                                         $order);
        //            }
        //            //echo "<pre>";
        //            //print_r($xml);
        //            //echo "</pre>";
        //        }
        //    }
        //    exit;
        //}
        return $hubData;
    }
}

?>