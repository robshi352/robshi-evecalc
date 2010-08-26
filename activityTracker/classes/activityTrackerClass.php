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
                      FROM ".$this->prefix."MemberID)";
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
                    $query2 = "SELECT typeName = 'Mobile Laboratory' as lab
                               FROM invTypes
                               WHERE typeID = ".$job->containerTypeID;
                    $this->db->query($query2);
                    $lab = mysql_fetch_assoc($this->db->result);
                    if ($lab["lab"])
                        $timeFactor = 0.5;
                    else
                        $timeFactor = 1;
                
                $query = "INSERT INTO ".$this->prefix."ActivityTracking
                          VALUES (
                            ".$job->jobID.",
                            ".$job->activityID.",
                            ".$job->installerID.",
                            '".$job->beginProductionTime."',
                            '".$job->endProductionTime."',
                            '".date("o-m-d H:i:s", $now)."',
                            ".$job->outputTypeID.",
                            ".$job->runs.",
                            ".$job->completedStatus.",
                            ".$timeFactor.")";
                if (($job->activityID == $this->inventionID) || ($job->activityID == $this->productionID))
                {
                    if ($job->activityID == $this->inventionID)
                        $trackingInfo[$job->jobID]["activity"] = "Invention";
                    if ($job->activityID == $this->productionID)
                        $trackingInfo[$job->jobID]["activity"] = "Production";

                    $trackingInfo[$job->jobID]["installerID"] = $job->installerID;
                    $trackingInfo[$job->jobID]["beginProductionTime"] = $job->beginProductionTime;
                    $trackingInfo[$job->jobID]["endProductionTime"] = $job->endProductionTime;
                    $trackingInfo[$job->jobID]["containerTypeID"] = $job->containerTypeID;
                    $trackingInfo[$job->jobID]["timeFactor"] = $timeFactor;
                    $trackingInfo[$job->jobID]["runs"] = $job->runs;
                    
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
                         sum((UNIX_TIMESTAMP(endProductionTime) - UNIX_TIMESTAMP(beginProductionTime)) / timeFactor)  as prodTime
                  FROM ".$this->prefix."ActivityTracking, ".$this->prefix."MemberID
                  WHERE installerID = memberID
                  AND entryDate >= '".date("o-m-d H:i:s", $startTime)."'
                  AND entryDate <= '".date("o-m-d H:i:s", $endTime)."'
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
                $inventionData["data"][$line["name"]]["t2ModFactor"] = $line["prodTime"] / 3600 / 2.5 / $line["inventions"];

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
    
    function getStatData($startTime, $endTime)
    {
        $statData["startDate"] = date("F jS Y", $startTime);
        $statData["endDate"] = date("F jS Y", $endTime);
        $statData["Week"] = date("W", $startTime);
        
        //fetch invention data
        $query = "SELECT t3.typeName, COUNT(*) AS cnt, SUM(t1.completedStatus) AS succ
                    FROM ".$this->prefix."ActivityTracking AS t1, invBlueprintTypes AS t2, invTypes as t3
                    WHERE t1.outputTypeID = t2.blueprintTypeID
                    AND t2.productTypeID = t3.typeID
                    AND t1.activityID = 8
                    AND t1.entryDate > '".date("o-m-d H:i:s", $startTime)."'
                    AND t1.entryDate <= '".date("o-m-d H:i:s", $endTime)."'
                    GROUP BY t1.outputTypeID
                    ORDER BY t3.typeName";
        //echo "<pre>".$query."</pre>";
        $this->db->query($query);
        
        while($line = mysql_fetch_assoc($this->db->result))
        {
            $statData["data"][$line["typeName"]]["invcnt"] += $line["cnt"];
            $statData["data"][$line["typeName"]]["invsucc"] += $line["succ"];
        }
        
        //fetch production data
        $query = "SELECT t2.typeName, SUM(t1.runs) AS cnt
                    FROM ".$this->prefix."ActivityTracking AS t1, invTypes as t2
                    WHERE t1.outputTypeID = t2.typeID
                    AND t1.activityID = 1
                    AND t1.endProductionTime > '".date("o-m-d H:i:s", $startTime)."'
                    GROUP BY t1.outputTypeID
                    ORDER BY t2.typeName";
        //echo "<pre>".$query."</pre>";
        $this->db->query($query);
        
        while($line = mysql_fetch_assoc($this->db->result))
        {
            $statData["data"][$line["typeName"]]["prodcnt"] += $line["cnt"];
        }
        ksort($statData["data"]);
        
        return $statData;
    }
    
    function generateXML($weekCount, $currentWeekStart)
    {
        $weekDuration = 7 * 24 * 60 * 60;
        
        
        $weekStart = $currentWeekStart;
        $weekEnd = $weekStart + $weekDuration - 1;
        
        $xml = new SimpleXMLElement("<activity></activity>");
        
        for ($i = 1; $i <= $weekCount; $i++)
        {
            $inventionData = $this->getInventionData($weekStart, $weekEnd);
            $productionData = $this->getProductionData($weekStart, $weekEnd);

            $week = $xml->addChild("week");
            $week->addAttribute("number", $inventionData["week"]);
            
            $invention = $week->addChild("invention");
            $production = $week->addChild("production");
            
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
                
                $name = $invention->addChild("total");
                $name->addAttribute("inventions", $inventionData["totalInventions"]);
                $name->addAttribute("successful", $inventionData["totalSuccessful"]);
            }
            
            if ($productionData["data"])
            {
                foreach($productionData["data"] as $producer => $producerDetails)
                {
                    $name = $production->addChild("producer");
                    $name->addAttribute("name", $producer);
                    
                    if ($producerDetails["otherCount"])
                        $name->addAttribute("otherCount", $producerDetails["otherCount"]);
                    else
                        $name->addAttribute("otherCount", 0);
                        
                    if ($producerDetails["otherUtil"])
                        $name->addAttribute("otherUtil", $producerDetails["otherUtil"]);
                    else
                        $name->addAttribute("otherUtil", 0);
                        
                    if ($producerDetails["t2Count"])
                        $name->addAttribute("t2Count", $producerDetails["t2Count"]);
                    else
                        $name->addAttribute("t2Count", 0);
                        
                    if ($producerDetails["t2Util"])
                        $name->addAttribute("t2Util", $producerDetails["t2Util"]);
                    else
                        $name->addAttribute("t2Util", 0);
                }
            }
            
            $weekStart = $weekStart - $weekDuration;
            $weekEnd = $weekEnd - $weekDuration;
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