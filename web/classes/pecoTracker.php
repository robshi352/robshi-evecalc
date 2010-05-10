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
                    echo " || ".$job->installedItemLocationID;
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
    
    function printInventions($startTime, $endTime, $divID, $outputMode="table")
    {
        $query = "SELECT memberName as Name, count(*) as Inventions, sum(completedStatus) as Successful
                  FROM pecoActivityTracking, pecoMemberID
                  WHERE installerID = memberID
                  AND entryDate >= '".date("o-m-d H:i:s", $startTime)."'
                  AND entryDate < '".date("o-m-d H:i:s", $endTime)."'
                  AND activityID = ".$this->inventionID."
                  GROUP BY installerID
                  ORDER BY memberName";
        $this->db->query($query);
        //echo "<b><i>".$query."</i></b>";
        $totalInventions = 0;
        $overallCompleted = 0;
        
        if ($outputMode == "table")
            echo "<div id=".$divID.">\n";
        
        echo "<b><u>Invention Report for Week ".date("W", $startTime)."</u></b><br>\n";
        //echo date("l jS \of F Y", $startTime)." - ".date("l jS \of F Y", $endTime)."<br><br>\n";
        echo date("F jS Y", $startTime)." - ".date("F jS Y", $endTime)."<br><br>\n";
        
        if ($outputMode == "table")
        {
            echo "<table>\n";
            echo "<tr>";
            foreach(array_keys(mysql_fetch_assoc($this->db->result)) as $key)
                echo "<th>".$key."</th>";
            echo "</tr>\n";
            mysql_data_seek($this->db->result, 0);
        }
        
        if (mysql_num_rows($this->db->result) > 0)
        {
            while($line = mysql_fetch_assoc($this->db->result))
            {
                if ($outputMode == "table")
                {
                    echo "<tr>
                            <td>".$line["Name"]."</td>
                            <td>".$line["Inventions"]."</td>
                            <td>".round($line["Successful"] / $line["Inventions"] * 100, 2)."%</td>
                          </tr>\n";
                }
                elseif($outputMode == "text")
                {
                    echo "<u>".$line["Name"]."</u>: ";
                    echo $line["Inventions"]." Inventions ";
                    echo "(".round($line["Successful"] / $line["Inventions"] * 100, 2)."% success)<br>\n";
                }
                $totalInventions += $line["Inventions"];
                $overallCompleted += $line["Successful"];
            }
            
            if ($outputMode == "table")
            {
                echo "<tr>
                        <td><b>Total</b></td>
                        <td><b>".$totalInventions."</b></td>
                        <td><b>".round($overallCompleted / $totalInventions * 100, 2)."%</b></td>
                      </tr>";
            }
            elseif($outputMode == "text")
            {
                echo "<br><b>Total</b>: ";
                echo $totalInventions." Inventions ";
                echo "(".round($overallCompleted / $totalInventions * 100, 2)."% success)<br><br>";
            }
        }
        else
        {
            echo "<b>No inventions happened.</b><br>";
        }
        if ($outputMode == "table")
        {
            echo "</table>";
            echo "</div>";
        }
    }
    
    function printProductions($startTime, $endTime, $divID, $outputMode="table")
    {
        $buildingSlots = 10;
        
        //select T2jobs interfering the the selected timeframe
        $query = "SELECT memberName, endProductionTime, beginProductionTime, metaGroupID
                  FROM (pecoActivityTracking AS t1, pecoMemberID AS t2)
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
        if ($outputMode == "table")
            echo "<div id=".$divID.">\n";
            
        echo "<b><u>Production Report for Week ".date("W", $startTime)."</u></b><br>";
        //echo "(".date("l jS \of F Y", $startTime)." - ".date("l jS \of F Y", $endTime).")</u><br><br>";
        echo date("F jS Y", $startTime)." - ".date("F jS Y", $endTime)."<br><br>\n";

        if ($outputMode == "table")
        {
            echo "<table>\n";
            echo "<tr>";
            echo "<th>Name</th>
                  <th>Other Prod.</th>
                  <th>Other Util.</th>
                  <th>T2 Prod</th>
                  <th>T2 Util.</th>
                  <th>Total Util</th>";
            echo "</tr>\n";
        }
        $totalProductions = 0;
        
        if (mysql_num_rows($this->db->result) > 0)
        {
            //T2 Stuff
            while($line = mysql_fetch_assoc($this->db->result))
            {
                $startProductionTime = strtotime($line["beginProductionTime"]);
                $endProductionTime = strtotime($line["endProductionTime"]);
                $name = $line["memberName"];
                if (!$line["metaGroupID"])
                    $meta = 1;
                else
                    $meta = 2;
                
                $output[$name][$meta."count"] += 1;
                if ($startProductionTime < $startTime)
                    $startProductionTime = $startTime;
                if ($endProductionTime > $endTime)
                    $endProductionTime = $endTime;
                
                $output[$name][$meta."duration"] += $endProductionTime - $startProductionTime;
                $output[$name]["totalduration"] += $endProductionTime - $startProductionTime;
            }
            
            foreach($output as $key => $value)
            {
                if ($outputMode == "table")
                {
                    echo "<tr>
                            <td>".$key."</td>";
                    for($meta=1; $meta <= 2; $meta++)
                    {
                            $util = round($value[$meta."duration"] / ($buildingSlots * ($endTime - $startTime)) * 100, 2);
                            echo "<td>".$value[$meta."count"]."</td>";
                            if ($util != 0)
                                echo "<td>".$util."%</td>";
                            else
                                echo "<td>&nbsp;</td>";
                    }
                    echo "<td><b>".round($value["totalduration"] / ($buildingSlots * ($endTime - $startTime)) * 100, 2)."%</b></td>";
                    echo "</tr>";
                }

                elseif($outputMode == "text")
                {
                    echo "<u>".$key."</u>: ";

                    for($meta=1; $meta <= 2; $meta++)
                    {
                        if ($value[$meta."count"])
                        {
                            if ($meta == 1)
                                echo "<b>Other:</b> ";
                            elseif ($meta == 2)
                                echo "<b>T2:</b> ";
    
                            echo $value[$meta."count"]." Productions ";
                            echo "(".round($value[$meta."duration"] / ($buildingSlots * ($endTime - $startTime)) * 100, 2)."%, ";
                            echo "utilization)";
                                echo " | ";
                        }
                    }
                    echo "<b>Total: ".round($value["totalduration"] / ($buildingSlots * ($endTime - $startTime)) * 100, 2)."%</b> Utilization";
                    echo "<br>";
                }
            }
            echo "<br>";
        }
        else
        {
            echo "<b>No production happened.</b><br>";
        }
        
        if ($outputMode == "table")
        {
            echo "</table>";
            echo "</div>";
        }        
    }
    
    function inventionStatus($style = "table")
    {
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
        
        if ($style == "table")
        {
            echo "<div id=invention>";
            $this->printInventions($currentWeekStart, $currentWeekEnd, "invCurr", "table");
            $this->printInventions($lastWeekStart, $lastWeekEnd, "invLast", "table");
            echo "</div>";
            echo "<div style='clear: both;'></div>";
        }
        elseif ($style == "text")
        {
            $this->printInventions($currentWeekStart, $currentWeekEnd, "invCurr", "text");
            $this->printInventions($lastWeekStart, $lastWeekEnd, "invLast", "text");
        }
    }
    
    function ProductionStatus($style)
    {
        $now = time();
        $weekday = date("N", $now);
        $hour = date("H", $now);
        $minute = date("i", $now);
        $seconds = date("s", $now);
        
        $currentWeekStart = $now - $seconds - ($minute * 60) - ($hour * 60 * 60) - (($weekday - 1) * 60 * 60 * 24);
        $currentWeekEnd = $currentWeekStart + (7 * 24 * 60 * 60) - 1;
        $lastWeekStart = $currentWeekStart - (7 * 24 * 60 * 60);
        $lastWeekEnd = $currentWeekEnd - (7 * 24 * 60 * 60);
        
        if ($style == "table")
        {
            echo "<div id=production>";
            $this->printProductions($currentWeekStart, $currentWeekEnd, "prodCurr", "table");
            $this->printProductions($lastWeekStart, $lastWeekEnd, "prodCurr", "table");
            echo "</div>";
            echo "<div style='clear: both;'></div>";
        }
        elseif ($style == "text")
        {
            $this->printProductions($currentWeekStart, $currentWeekEnd, "prodCurr", "text");
            $this->printProductions($lastWeekStart, $lastWeekEnd, "prodCurr", "text");
        }
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