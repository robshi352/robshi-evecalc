<?php
    $submit = $_POST["submit"];
    $locationID = $_POST["location"];
    $selectedCorpID = $_POST["corp"];
    $medical = $_POST["medical"];
    $jumpclone = $_POST["jumpclone"];
    $hisec = $_POST["hisec"];
    $losec = $_POST["losec"];
    $zero = $_POST["zero"];

    $medicalID = 512;
    $jumpcloneID = 8388608;
    class station
    {
        function station($name, $security, $locationID, $distance)
        {
            $this->name = $name;
            $this->security = $security;
            $this->locationID = $locationID;
            $this->distance = $distance;
        }
        
        function printStationDistance()
        {
            echo "<b>".$this->distance."</b> - ";
            echo $this->name." (".round($this->security, 1).")<br>\n";
        }
        
        function printStation()
        {
            echo $this->name." (".round($this->security, 1).")<br>\n";
        }
    }
    class corporation
    {        
        function corporation($selectedID)
        {
            $this->selectedID = $selectedID;
            $this->query = "SELECT corporationID, itemName
                            FROM crpnpccorporations AS t1, evenames AS t2
                            WHERE t1.corporationID = t2.itemID
                            ORDER BY itemName";
        }
        
        function printSelection()
        {
            global $db;

            echo "Corporation: <select name=corp>";
            $db->query($this->query);
            while($line = mysql_fetch_array($db->result, MYSQL_ASSOC))
            {
                if ($this->selectedID == $line["corporationID"])
                {
                    echo "<option value=".$line["corporationID"]." selected>".$line["itemName"]."</option>\n";    
                }
                else
                {
                    echo "<option value=".$line["corporationID"].">".$line["itemName"]."</option>\n";
                }
            }
            echo "</select><br>\n";
        }
    }
    
    
    class stationList
    {
        function stationList()
        {
            $this->stationList = array();
        }
        
        function add($station)
        {
            $this->stationList[] = $station;
        }
        
        function printStationDistance()
        {
            foreach ($this->stationList as $key => $value)
            {
                $value->printStationDistance();
            }
        }
        
        function printStation()
        {
            foreach ($this->stationList as $key => $value)
            {
                $value->printStation();
            }
        }
    }
    echo "<form action=".$PHP_SELF."?func=".$currentFunction." method=POST>\n";
    if ($submit)
    {
        $query =   "SELECT stationName, t2.security
                    FROM stastations AS t1, mapsolarsystems AS t2, staoperationservices AS t3
                    WHERE t1.corporationID = ".$selectedCorpID."
                    AND t1.solarSystemID = t2.solarSystemID
                    AND t1.operationID = t3.operationID
                    ";
        if ($location)
        {
            $query2 =   "SELECT solarSystemID
                        FROM mapsolarsystems
                        WHERE solarSystemName = '".$locationID."'";
            $db->query($query2);
            while($line = mysql_fetch_array($db->result, MYSQL_ASSOC))
            {
                $locationID = $line["solarSystemID"];
            }
            
            if ($locationID)
            {
            $query =   "SELECT stationName, t2.security, distance
                        FROM stastations AS t1, mapsolarsystems AS t2, staoperationservices AS t3, mapdistance AS t4
                        WHERE t1.corporationID = ".$selectedCorpID."
                        AND t1.solarSystemID = t2.solarSystemID
                        AND t1.operationID = t3.operationID
                        AND t4.fromSolarSystemID = ".$locationID."
                        AND t4.toSolarSystemID = t1.solarSystemID
                        ";                
            }
            else
            {
                $locationError = 1;
            }

        }

        if ($hisec and $losec)
        {
            $query = $query."AND t2.security > 0
                            ";
        }
        else if ($hisec and $zero)
        {
            $query = $query."AND (t2.security > 0.5
                             OR t2.security <= 0)
                            ";
        }
        else if ($losec and $zero)
        {
            $query = $query."AND t2.security < 0.5
                            ";
        }
        else if ($hisec)
        {
            $query = $query."AND t2.security >= 0.5
                            ";
        }
        else if ($losec)
        {
            $query = $query."AND t2.security < 0.5
                             AND t2.security > 0
                             ";
        }
        else if ($zero)
        {
            $query = $query."AND t2.security <= 0
                            ";
        }
        if ($medical){
            $query = $query."AND t3.serviceID = ".$medicalID."
                            ";
        }
        if ($jumpclone)
        {
            $query = $query."AND t3.serviceID = ".$jumpcloneID."
                            ";
        }
        $query = $query."GROUP BY stationID
                        ";
        if ($locationID)
        {
            $query = $query. "ORDER BY distance ASC, stationName ASC";
        }
        else
        {
            $query = $query. "ORDER BY stationName ASC";
        }
        $result = mysql_query($query) or die("Anfrage fehlgeschlagen: " . mysql_error());
    }

    echo "Location: <input type=text value='".$location."' name=location>";
    if ($locationError)
    {
        echo " <font color=red>check spelling</font>";
    }
    echo "<br>\n";

    $corporations = new corporation($selectedCorpID);
    $corporations->printSelection();
    
    if ($medical)
    {
        echo "<input type=checkbox name=medical checked>Medical";
    }
    else
    {
        echo "<input type=checkbox name=medical>Medical";        
    }

    if ($jumpclone)
    {
        echo "<input type=checkbox name=jumpclone checked>Jump Clone<br>";
    }
    else
    {
        echo "<input type=checkbox name=jumpclone>Jump Clone<br>";        
    }

    if ($hisec or !$submit)
    {
        echo "<input type=checkbox name=hisec checked>Highsec";        
    }
    else
    {
        echo "<input type=checkbox name=hisec>Highsec";                
    }
    if ($losec or !$submit)
    {
        echo "<input type=checkbox name=losec checked>Lowsec";        
    }
    else
    {
        echo "<input type=checkbox name=losec>Lowsec";                
    }
    if ($zero or !$submit)
    {
        echo "<input type=checkbox name=zero checked>0.0<br>\n";        
    }
    else
    {
        echo "<input type=checkbox name=zero>0.0<br>\n";
    }

    echo "<input type=submit value=submit name=submit>";
    echo "</form><br>";

    if ($submit)
    {
        if ($locationID)
        {
            echo "<u>Distance - Station (Security)";
        }
        else
        {
            echo "<u>Station (Security)";
        }
        echo "</u><br>";
        $db->query($query);
        while($line = mysql_fetch_array($db->result, MYSQL_ASSOC))
        {
            if ($locationID)
            {
                echo "<b>".$line["distance"]."</b> - ";
            }
            echo $line["stationName"]." (".round($line["security"], 1).")<br>\n";
        }
    }
?>