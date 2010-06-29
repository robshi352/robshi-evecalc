<?php
    $location = $_POST["location"];
    $submit = $_POST["submit"];
    $corp = $_POST["corp"];
    $hisec = $_POST["hisec"];
    $losec = $_POST["losec"];
    $zero = $_POST["zero"];
    for($i=1;$i<=5;$i++)
    {
        $level[$i] = $_POST["level".$i];
    }    
    
    if ($submit)
    {
        if ($location)
        {
            $query2 =  "SELECT solarSystemID
                        FROM mapsolarsystems
                        WHERE solarSystemName = '".$location."'";
            $result = mysql_query($query2) or die("Anfrage fehlgeschlagen: " . mysql_error());
            while($line = mysql_fetch_array($result, MYSQL_ASSOC))
            {
                $locationID = $line["solarSystemID"];
            }
        }
    }
    
    echo "<form action=".$PHP_SELF."?func=".$function." method=POST>\n";
    echo "Location: <input type=text value='".$location."' name=location>";
    if ((!$locationID) && ($submit) && ($location))
    {
        echo " <font color=red>check spelling</font>";
    }
    echo "<br>\n";
    echo "Corporation: <select name=corp>";
    
    $query =   "SELECT corporationID, itemName
                FROM crpnpccorporations AS t1, evenames AS t2
                WHERE t1.corporationID = t2.itemID
                ORDER BY itemName";
    $corporations = mysql_query($query) or die("Anfrage fehlgeschlagen: " . mysql_error());
    while($line = mysql_fetch_array($corporations, MYSQL_ASSOC))
    {
        if ($corp == $line["corporationID"])
        {
            echo "<option value=".$line["corporationID"]." selected>".$line["itemName"]."</option>\n";    
        }
        else
        {
            echo "<option value=".$line["corporationID"].">".$line["itemName"]."</option>\n";
        }
    }
    

    echo "</select><br>";
    echo "Level: ";
    $nolevel = true;
    for ($i=1; $i <=5; $i++)
    {
        if($level[$i] or !$submit)
        {
            $nolevel = false;
            echo "<input type=checkbox name=level".$i." checked>".$i." ";
        }
        else
        {
            echo "<input type=checkbox name=level".$i.">".$i." ";
        }
    }
    echo "<br>";
    
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
        echo "<input type=checkbox name=zero checked>0.0<br>";        
    }
    else
    {
        echo "<input type=checkbox name=zero>0.0<br>";                
    }
    
    echo "<input type=submit name=submit value=submit>";
    echo "</form><br>\n";
    
    if ($submit)
    {
        if ($locationID)
        {
            $query =   "SELECT agentID, itemName, level, quality, stationName, t4.security, distance
                        FROM agtagents AS t1, evenames AS t2, stastations AS t3, mapsolarsystems AS t4, mapdistance AS t5
                        WHERE agentID = t2.itemID
                        AND t1.corporationID = ".$corp."
                        AND t1.locationID = t3.stationID
                        AND t3.solarsystemID = t4.solarSystemID
                        AND t5.fromSolarSystemID = ".$locationID."
                        AND t5.toSolarSystemID = t4.solarSystemID
                        AND (";
            if (!$nolevel)
            {
                $one = true;
                for($i=1; $i<=5; $i++)
                {
                    if ($level[$i])
                    {
                        if($one)
                        {
                            $query = $query."level = ".$i."
                                            ";
                        }
                        else
                        {
                            $query = $query."OR level = ".$i."
                                            ";
                        }
                        $one = false;
                    }
                }
                $query = $query.")
                                ";
            }
            $query = $query."ORDER BY distance ASC, level DESC, quality DESC";
        }
        else
        {
            $query =   "SELECT agentID, itemName, level, quality, stationName, t4.security
                        FROM agtagents AS t1, evenames AS t2, stastations AS t3, mapsolarsystems AS t4
                        WHERE agentID = t2.itemID
                        AND t1.corporationID = ".$corp."
                        AND t1.locationID = t3.stationID
                        AND t3.solarsystemID = t4.solarSystemID
                        ORDER BY level DESC, quality DESC";
        }
        
        if ($locationID)
        {
            echo "<u>Distance - Name - Level - Quality - Location";
        }
        else
        {
            echo "<u>Name - Level - Quality - Location";
        }
        echo "</u><br><br>";
        $result = mysql_query($query) or die("Anfrage fehlgeschlagen: " . mysql_error());
        while($line = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            if ($locationID)
            {
                echo "<b>".$line["distance"]."</b> ";
            }
            echo $line["itemName"]. " (L".$line["level"]." Q".$line["quality"].") - ".$line["stationName"]." (".round($line["security"], 1).")<br>\n";
        }

    }
?>