<?php
class pecoTracker
{
    function __construct($userID, $apiKey, $charName, $dbLink)
    {
        global $config;
        
        try
        {   
            $this->ale = AleFactory::getEVEOnline();
            //set user credentials, third parameter $characterID is also possible;
            $this->ale->setCredentials($userID, $apiKey);
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
                if($character->name == $charName)
                {
                    break;
                }
            }
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }

        $this->db = $dbLink;
        $this->db->query("SELECT activityID
                          FROM ramactivities
                          WHERE activityName = 'Invention'");
        $inventionID = mysql_fetch_assoc($this->db->result);
        $this->inventionID = $inventionID["activityID"];
        
        $this->db->query("SELECT activityID
                          FROM ramactivities
                          WHERE activityName = 'Manufacturing'");
        $productionID = mysql_fetch_assoc($this->db->result);
        $this->productionID = $productionID["activityID"];        
    }
    
    function jobAlreadyTracked($jobID)
    {
        $this->db->query("SELECT *
                          FROM pecoActivityTracking
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
        echo "<u>User Update</u><br><br>\n";
        
        $query = "SELECT distinct installerID
                  FROM pecoActivityTracking
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
                $query = "INSERT INTO pecoMemberID
                          VALUES(".$charName->characterID.", '".$charName->name."')";
                $this->db->query($query);
                echo $charName->name." (".$charName->characterID.") added<br>\n";
            }
        }
        else
        {
            echo "No new Members <br>\n";
        }
    }
    
    function track()
    {
        try
        {
            $corpJobs = $this->ale->corp->IndustryJobs();
            //print_r($corpJobs);
            
            echo "<u>Tracking</u><br><br>\n";
            $now = time();
            
            foreach ($corpJobs->result->jobs as $job)
            {
                $query = "INSERT INTO pecoActivityTracking VALUES (";
                $query = $query.$job->jobID.","; //jobID
                $query = $query.$job->activityID.", "; //activityID
                $query = $query.$job->installerID.","; //installerID
                $query = $query."'".$job->beginProductionTime."',"; //beginProductionTime
                $query = $query."'".$job->endProductionTime."',"; //endProductionTime
                $query = $query."'".date("o-m-d H:i:s", $now)."',"; //entryDate
                $query = $query.$job->outputTypeID.","; //outputTypeID
                $query = $query.$job->completedStatus.")"; //Inventionsuccess
                
                if (($job->activityID == $this->inventionID) || ($job->activityID == $this->productionID))
                {
                    if ($job->activityID == $this->inventionID)
                        echo "Invention | ";
                    elseif ($job->activityID == $this->productionID)
                        echo "Production | ";
                    
                    echo "JobID: ".$job->jobID." (".$job->beginProductionTime." - ".$job->endProductionTime.") by ".$job->installerID;
                    
                    if (($job->completed) && ($job->activityID == $this->inventionID))
                    {
                        //enter the completed invention jobs to the DB
                        echo " completed";
                        if ($job->completedStatus)
                        {
                            echo " successfully";
                        }

                        if (!$this->jobAlreadyTracked($job->jobID))
                        {
                            $this->db->query($query);
                            echo " | entered";
                        }
                        else
                        {
                            echo " | already tracked";
                        }
                    }
                    elseif($job->activityID == $this->productionID)
                    {
                        if (!$this->jobAlreadyTracked($job->jobID))
                        {
                            $this->db->query($query);
                            echo " | entered";
                        }
                        else
                        {
                            echo " | already tracked";
                        }
                    }
                    else
                    {
                        echo " in progress ...";
                    }
                    echo "<br> \n";
                }
            }
            echo "<br>";
            $this->updateMembers();
            echo "<br>";
            echo "Cached until: ".$corpJobs->cachedUntil."\n";
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    
    function printInventions($startTime, $endTime)
    {
        echo "<u>Invention Report for Week ".date("W", $startTime)."</u><br>";
        echo "(".date("l jS \of F Y", $startTime)." - ".date("l jS \of F Y", $endTime).")</u><br><br>";
        
        $query = "SELECT memberName, installerID, count(*), sum(completedStatus)
                  FROM pecoActivityTracking, pecoMemberID
                  WHERE installerID = memberID
                  AND entryDate >= '".date("o-m-d H:i:s", $startTime)."'
                  AND entryDate < '".date("o-m-d H:i:s", $endTime)."'
                  AND activityID = ".$this->inventionID."
                  GROUP BY installerID
                  ORDER BY memberName";
        $this->db->query($query);
        
        $totalInventions = 0;
        $overallCompleted = 0;
        
        if (mysql_num_rows($this->db->result) > 0)
        {
            while($line = mysql_fetch_assoc($this->db->result))
            {
                echo "<b>".$line["memberName"]."</b>: ";
                echo $line["count(*)"]." Inventions ";
                echo "(".round($line["sum(completedStatus)"] / $line["count(*)"] * 100, 2)."% success)<br>\n";
                
                $totalInventions += $line["count(*)"];
                $overallCompleted += $line["sum(completedStatus)"];
            }
            
            echo "<br><b>Total</b>: ";
            echo $totalInventions." Inventions ";
            echo "(".round($overallCompleted / $totalInventions * 100, 2)."% success)<br>";
        }
        else
        {
            echo "<b>No inventions happened.</b><br>";
        }
    }
    
    function inventionStatus()
    {
        global $config;
        
        //Select timeframe for this week and last week
        
        $now = time();
        $weekday = date("N", $now);
        $hour = date("H", $now);
        $minute = date("i", $now);
        $seconds = date("s", $now);
        
        $currentWeekStart = $now - $seconds - ($minute * 60) - ($hour * 60 * 60) - (($weekday - 1) * 60 * 60 * 24);
        $currentWeekEnd = $currentWeekStart + (7 * 24 * 60 * 60) - 1;
        $lastWeekStart = $currentWeekStart - (7 * 24 * 60 * 60);
        $lastWeekEnd = $currentWeekEnd - (7 * 24 * 60 * 60);
        
        $this->printInventions($currentWeekStart, $currentWeekEnd);
        echo "<br><br>";
        $this->printInventions($lastWeekStart, $lastWeekEnd);
    }
    
    function setupTables()
    {
        global $config;
        
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
        $this->db->query($query);
        
        $query = "CREATE TABLE IF NOT EXISTS ".$config["db"]["tablePrefix"]."MemberID(
                    memberID int(11) NOT NULL,
                    memberName char(50) NOT NULL,
                    PRIMARY KEY (memberID)
                  )";
        $this->db->query($query);
        echo "Setup Complete";
    }
}
?>