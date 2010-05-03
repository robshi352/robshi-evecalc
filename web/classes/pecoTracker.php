<?php
class pecoTracker
{
    function __construct($userID, $apiKey, $charName, $dbLink)
    {
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
    }
    
    function inventionAlreadyTracked($inventionID)
    {
        $this->db->query("SELECT *
                          FROM pecoInvention
                          WHERE inventionID = ".$inventionID);
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
        $query = "SELECT distinct inventorID
                  FROM pecoInvention
                  WHERE inventorID NOT IN (
                      SELECT memberID
                      FROM pecoMemberID)";
        $this->db->query($query);
        
        $charIDs = array("ids" => array());
        if (mysql_num_rows($this->db->result) > 0)
        {
            while($line = mysql_fetch_assoc($this->db->result))
            {
                //echo "fetching ".$line["inventorID"]."<br>\n";
                $charIDs["ids"][] = $line["inventorID"];
            }
            $charNames = $this->ale->eve->CharacterName($charIDs);
            print_r($charNames);
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
    
    function trackInvention()
    {
        try
        {
            $corpJobs = $this->ale->corp->IndustryJobs();
            //print_r($corpJobs);
            
            $count = 0;
            $countSuccessfully = 0;
            $countCompleted = 0;
            
            echo "<u>INVENTION</u><br><br>\n";
            
            foreach ($corpJobs->result->jobs as $job)
            {
                if ($job->activityID == $this->inventionID)
                {
                    $count++;
                    echo "JobID: ".$job->jobID." (".$job->beginProductionTime." - ".$job->endProductionTime.") by ".$job->installerID;
                    
                    if ($job->completed)
                    {
                        //enter the completed jobs to the DB
                        $countCompleted++;
                        echo " completed";
                        if ($job->completedStatus)
                        {
                            $countSuccessfully++;
                            echo " successfully";
                        }

                        if (!$this->inventionAlreadyTracked($job->jobID))
                        {
                            $query = "INSERT INTO pecoInvention VALUES (";
                            $query = $query.$job->jobID.",";
                            $query = $query.$job->installerID.",";
                            $query = $query."'".date("o-m-d H:i:s")."',";
                            $query = $query.$job->outputTypeID.",";
                            $query = $query.$job->completedStatus.")";
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
                        echo "in progress ...";
                    }
                    echo "<br> \n";
                }
            }

            echo "Total: ".$count."<br>\n";
            echo "Total Completed: ".$countCompleted."<br>\n";
            echo "Total Successful: ".$countSuccessfully."<br><br>\n";
            $this->updateMembers();

            echo "Cached until: ".$corpJobs->cachedUntil."\n";
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    
    function inventionStatus()
    {
        $query = "SELECT min(entryDate), max(entryDate)
                  FROM pecoInvention";
        $this->db->query($query);
        $dates = mysql_fetch_assoc($this->db->result);
        
        echo "<u>Invention Report</u><br>(".$dates["min(entryDate)"]." - ".$dates["max(entryDate)"].")</u><br><br>";
        
        $query = "SELECT memberName, inventorID, count(*), sum(completedStatus)
                  FROM pecoInvention, pecoMemberID
                  WHERE inventorID = memberID
                  GROUP BY inventorID
                  ORDER BY memberName";
        $this->db->query($query);
        
        $totalInventions = 0;
        $totalInventors = 0;
        $overallSuccess = 0;
        
        while($line = mysql_fetch_assoc($this->db->result))
        {
            echo "<b>".$line["memberName"]."</b>: ";
            echo $line["count(*)"]." Inventions ";
            echo "(".round($line["sum(completedStatus)"] / $line["count(*)"] * 100, 2)."% success)<br>\n";
            
            $totalInventions += $line["count(*)"];
            $totalInventors++;
            $overallSuccess += $line["sum(completedStatus)"] / $line["count(*)"];
        }
        
        echo "<br><b>Total</b>: ";
        echo $totalInventions." Inventions ";
        echo "(".round($overallSuccess / $totalInventors * 100, 2)."% success)<br>";
    }
    
    function setupTables()
    {
        
    }
}
?>