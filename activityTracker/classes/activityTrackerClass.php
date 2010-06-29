<?php
/*Copyright 2010 @ masubious*/

class activityTracker
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
        
        $this->inventionID = 8;
        $this->productionID = 1;
    }

    function getLatestEntry()
    {
        $query = "SELECT max(entryDate)
                  FROM ".$this->prefix."ActivityTracking";
        $this->db->query($query);
        
        $latestEntry = mysql_fetch_assoc($this->db->result);
        $latestEntry = $latestEntry["max(entryDate)"];
        
        return strtotime($latestEntry);
    }
    
    function init()
    {
        try
        {   
            $this->ale = AleFactory::getEVEOnline();
            //set user credentials, third parameter $characterID is also possible;
            $this->ale->setCredentials($this->userID, $this->apiKey, $this->charID);
            //all errors are handled by exceptions
            //let's fetch characters first.
            $account = $this->ale->account->Characters();
            //you can traverse rowset element with attribute name="characters" as array
            foreach ($account->result->characters as $character)
            {
                //this is how you can get attributes of element
                $characterID = (string) $character->characterID;
                //set characterID for CharacterSheet
                $this->ale->setCharacterID($characterID);
                if($character->name == $this->charName)
                {
                    break;
                }
            }
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    
    function jobAlreadyTracked($jobID)
    {
        $this->db->query("SELECT *
                          FROM ".$this->prefix."ActivityTracking
                          WHERE jobID = ".$jobID);
        if (mysql_numrows($this->db->result) == 0)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    function updateMembers()
    {
        $query = "SELECT distinct installerID
                  FROM ".$this->prefix."ActivityTracking
                  WHERE installerID NOT IN (
                      SELECT memberID
                      FROM pecoMemberID)";
        $this->db->query($query);
        
        $charIDs = array("ids" => array());
        if (mysql_num_rows($this->db->result) > 0)
        {
            while($line = mysql_fetch_assoc($this->db->result))
            {
                //echo "fetching ".$line["inventorID"]."<br>\n";
                $charIDs["ids"][] = $line["installerID"];
            }
            $charNames = $this->ale->eve->CharacterName($charIDs);
            foreach($charNames->result->characters as $charName)
            {
                $query = "INSERT INTO ".$this->prefix."MemberID
                          VALUES(".$charName->characterID.", '".$charName->name."')";
                $this->db->query($query);
            }
        }
    }
    
    function track()
    {
        try
        {
            $corpJobs = $this->ale->corp->IndustryJobs();
            //print_r($corpJobs);
            
            $now = time();
            
            foreach ($corpJobs->result->jobs as $job)
            {
                $query = "INSERT INTO ".$this->prefix."ActivityTracking
                          VALUES (
                            ".$job->jobID.",
                            ".$job->activityID.",
                            ".$job->installerID.",
                            '".$job->beginProductionTime."',
                            '".$job->endProductionTime."',
                            '".date("o-m-d H:i:s", $now)."',
                            ".$job->outputTypeID.",
                            ".$job->completedStatus.")";
                if (($job->activityID == $this->inventionID) || ($job->activityID == $this->productionID))
                {
                    if ($job->activityID == $this->inventionID)
                        $trackingInfo[$job->jobID]["activity"] = "Invention";
                    if ($job->activityID == $this->productionID)
                        $trackingInfo[$job->jobID]["activity"] = "Production";

                    $trackingInfo[$job->jobID]["installerID"] = $job->installerID;
                    $trackingInfo[$job->jobID]["beginProductionTime"] = $job->beginProductionTime;
                    $trackingInfo[$job->jobID]["endProductionTime"] = $job->endProductionTime;
                    
                    if (($job->completed) && ($job->activityID == $this->inventionID))
                    {
                        //enter the completed invention jobs to the DB
                        if (!$this->jobAlreadyTracked($job->jobID))
                        {
                            $this->db->query($query);
                            $trackingInfo[$job->jobID]["trackingStatus"] = "entered";
                        }
                        else
                            $trackingInfo[$job->jobID]["trackingStatus"] = "tracked";
                    }
                    elseif($job->activityID == $this->productionID)
                    {
                        if (!$this->jobAlreadyTracked($job->jobID))
                        {
                            $this->db->query($query);
                            $trackingInfo[$job->jobID]["trackingStatus"] = "entered";
                        }
                        else
                            $trackingInfo[$job->jobID]["trackingStatus"] = "tracked";
                    }
                    else
                        $trackingInfo[$job->jobID]["trackingStatus"] = "in progress";
                }
            }
            $trackingInfo["cached"] = $corpJobs->cachedUntil;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
        return $trackingInfo;
    }
    
    function getInventionData($startTime, $endTime)
    {
        $query = "SELECT memberName as name,
                         count(*) as inventions,
                         sum(completedStatus) as successful,
                         sum(UNIX_TIMESTAMP(endProductionTime) - UNIX_TIMESTAMP(beginProductionTime)) as prodTime
                  FROM ".$this->prefix."ActivityTracking, ".$this->prefix."MemberID
                  WHERE installerID = memberID
                  AND entryDate >= '".date("o-m-d H:i:s", $startTime)."'
                  AND entryDate < '".date("o-m-d H:i:s", $endTime)."'
                  AND activityID = ".$this->inventionID."
                  GROUP BY installerID
                  ORDER BY memberName";
        $this->db->query($query);
        //echo "<b><i>".$query."</i></b>";
        $inventionData["totalInventions"] = 0;
        $inventionData["totalSuccess"] = 0;
        
        $inventionData["startDate"] = date("F jS Y", $startTime);
        $inventionData["endDate"] = date("F jS Y", $endTime);
        $inventionData["week"] = date("W", $startTime);
        
        if (mysql_num_rows($this->db->result) > 0)
        {
            while($line = mysql_fetch_assoc($this->db->result))
            {
                $inventionData["data"][$line["name"]]["inventions"] = $line["inventions"];
                $inventionData["data"][$line["name"]]["successful"] = $line["successful"];
                $inventionData["data"][$line["name"]]["t2ModFactor"] =$line["prodTime"] / 3600 / 1.25 / $line["inventions"];

                $inventionData["totalInventions"] += $line["inventions"];
                $inventionData["totalSuccessful"] += $line["successful"];
            }
        }
        else
        {
            $inventionData["data"] = false;
        }
        
        return $inventionData;
    }
    
    function getProductionData($startTime, $endTime)
    {
        $buildingSlots = 10;
        
        //select T2jobs interfering the the selected timeframe
        $query = "SELECT memberName, endProductionTime, beginProductionTime, metaGroupID
                  FROM (".$this->prefix."ActivityTracking AS t1, ".$this->prefix."MemberID AS t2)
                       LEFT JOIN invMetaTypes AS t3 ON t3.typeID = t1.outputTypeID
                  WHERE t1.installerID = t2.memberID
                  AND
                  (
                    beginProductionTime BETWEEN '".date("o-m-d H:i:s", $startTime)."'
                                        AND '".date("o-m-d H:i:s", $endTime)."'
                    OR
                    endProductionTime BETWEEN '".date("o-m-d H:i:s", $startTime)."'
                                      AND '".date("o-m-d H:i:s", $endTime)."'
                    OR
                    (
                        beginProductionTime < '".date("o-m-d H:i:s", $startTime)."'
                        AND
                        endProductionTime > '".date("o-m-d H:i:s", $endTime)."'
                    )
                  )
                  AND activityID = ".$this->productionID."
                  ORDER BY memberName";
        //echo "<b><i>".$query."</i></b>";
        $this->db->query($query);
        
        $productionData["startDate"] = date("F jS Y", $startTime);
        $productionData["endDate"] = date("F jS Y", $endTime);
        $productionData["Week"] = date("W", $startTime);
        
        if (mysql_num_rows($this->db->result) > 0)
        {
            //T2 Stuff
            while($line = mysql_fetch_assoc($this->db->result))
            {
                $startProductionTime = strtotime($line["beginProductionTime"]);
                $endProductionTime = strtotime($line["endProductionTime"]);
                $name = $line["memberName"];
                
                if (!$line["metaGroupID"])
                    $meta = "other";
                else
                    $meta = "t2";

                if ($startProductionTime < time())
                {
                    //limit the borders of time tracking to the current week
                    if ($startProductionTime < $startTime)
                        $startProductionTime = $startTime;
                    if ($endProductionTime > min(time(), $endTime))
                        $endProductionTime = min(time(), $endTime);
                    
                    $productionData["data"][$name][$meta."Count"] += 1;
                    $productionData["data"][$name][$meta."Util"] += ($endProductionTime - $startProductionTime) / ((min($endTime, time()) - $startTime) * 10) * 100;
                }
            }
        }
        else
        {
            $productionData["data"] = false;
        }
        
        return $productionData;
    }
    
    function generateXML($lastWeekStart, $lastWeekEnd)
    {
        $inventionData = $this->getInventionData($lastWeekStart, $lastWeekEnd);
        $productionData = $this->getProductionData($lastWeekStart, $lastWeekEnd);

        $xml = new SimpleXMLElement("<activity></activity>");
        
        //$invention  = $xml->addChild("invention");
        $xml->addAttribute("week", $inventionData["week"]);
        $invention = $xml->addChild("invention");
        $production = $xml->addChild("production");
        
        if ($inventionData["data"])
        {
            foreach($inventionData["data"] as $inventor => $inventorDetails)
            {
                $name = $invention->addChild("inventor");
                $name->addAttribute("name", $inventor);
                $name->addAttribute("inventions", $inventorDetails["inventions"]);
                $name->addAttribute("successful", $inventorDetails["successful"]);
                $name->addAttribute("t2ModFactor", $inventorDetails["t2ModFactor"]);
            }
            
            $name = $xml->addChild("total");
            $name->addAttribute("inventions", $inventionData["totalInventions"]);
            $name->addAttribute("successful", $inventionData["totalSuccessful"]);
        }
        
        if ($productionData["data"])
        {
            foreach($productionData["data"] as $producer => $producerDetails)
            {
                $name = $production->addChild("producer");
                $name->addAttribute("name", $producer);
                $name->addAttribute("otherCount", $producerDetails["otherCount"]);
                $name->addAttribute("otherUtil", $producerDetails["otherUtil"]);
                $name->addAttribute("t2Count", $producerDetails["t2Count"]);
                $name->addAttribute("t2Util", $producerDetails["t2Util"]);
            }
        }
        
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        file_put_contents("activity.xml", $dom->saveXML());
        echo "<br><br><em>XML file generated.</em>";
        
    }
}
?>