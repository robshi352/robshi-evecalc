<?php
/*Copyright 2010 @ masubious*/

class posDisplayer
{
    function displayTrackingInfo($trackingInfo)
    {
        if (is_array($trackingInfo))
        {
            foreach($trackingInfo as $posID => $posDetails)
            {
                printf("<em><u>POS: %s</u></em><br>", $posID);
                
                if ($posDetails["fuel"])
                {
                    foreach($posDetails["fuel"] as $fuelID => $fuelDetails)
                    {
                        printf("%s | old: %s | new: %s | need: %s<br>", $fuelID, $fuelDetails["old"], $fuelDetails["new"], $fuelDetails["need"]);
                    }
                }
            }
        }
        else
        {
            printf("<em>No update done. Next Update possible in %s minutes</em>", $trackingInfo);
        }
    }
    
    function displayPosStatus($posData)
    {
        //echo "<pre>";
        //print_r($posData);
        //print "</pre>";
        if (!$posData)
        {
            echo "<em>No POS present.</em>";
            return;
        }
        foreach($posData as $posID => $posInfo)
        {
            echo '<div class="posData">';
            
            if($posInfo["info"]["state"] == "Online")
                $img = "box_green.png";
                
            if($posInfo["info"]["state"] == "Offline")
                $img = "box_red.png";
                
            if ($posInfo["info"]["name"])
            {
                printf("<u><em>%s</em></u>", $posInfo["info"]["name"]);
                printf('<img align="right" src="images/%s" alt="%s" title="%2$s">', $img, $posInfo["info"]["state"]);
                echo "<br>";
                printf("%s<br>", $posInfo["info"]["location"]);
            }
            else
            {
                printf("<u><em>%s</em></u>", $posInfo["info"]["location"]);
                printf('<img align="right" src="images/%s" alt="%s" title="%2$s">', $img, $posInfo["info"]["state"]);
                echo "<br>";

            }
            //printf("State: %s<br>", $posInfo["info"]["state"]);
            
            echo "<br>";
            
            if ($posInfo["minFuel"])
            {
                echo "<em>Fueled for</em>: ";
                printf("%s<br>", $posInfo["minFuel"]);
                //if ($posInfo["fuel"])
                //{
                //    echo "<br>";
                //    echo "<em>Fuel Details</em><br>";
                //    foreach($posInfo["fuel"] as $fuelName => $fuelInfo)
                //    {
                //        printf("%s: %s (%s)<br>", $fuelName, $fuelInfo["fuelNeed"], $fuelInfo["fuelReserve"]);
                //    }
                //}
            }
            else
            {
                echo "<em>No fuel.</em><br>";
            }
            echo "<br>";
            
            echo "<em>Refueling</em><br>";
            $refillVolume = 0;
            
            if ($posInfo["minFuel"])
            {
                foreach($posInfo["fuel"] as $fuelName => $fuelInfo)
                {
                    if ($fuelInfo["refill"] != 0)
                    {
                        printf("%s: <em>%s</em><br>", $fuelName, $fuelInfo["refill"]);
                        $totalRefill[$fuelName] += $fuelInfo["refill"];
                    }
                    $refillVolume += $fuelInfo["refillVolume"];
                    $totalRefillVolume += $fuelInfo["refillVolume"];
                }
                printf("<em>Volume</em>: %s m^3<br>", $refillVolume);
            }
            else
                echo "No refilling.";
            echo "</div>";
        }
        
        //echo total refill data
        echo '<div class="posData">';
        echo "<u><em>Total Refill</em></u><br><br>";
        foreach($totalRefill as $fuelName => $quantity)
        {
            printf("<em>%s</em>: %s<br>", $fuelName, $quantity);
        }
        echo "<br>";
        printf("<em>Volume</em>: %s m^3", $totalRefillVolume);
        echo "</div>";
    }
    
    function displayFuelData($fuelData)
    {
        echo '<div class="posData">';
        echo "<u><em>Fuel Details</em></u><br><br>";
        
        echo '<table>';
        echo '<tr>
                <th>Fuel Name</th>
                <th>hourly Need</th>
                <th>30-day Need</th>
                <th>Reserve (qty)</th>
                <th>Reserve (time)</th>
              </tr>';
        foreach($fuelData as $fuelName => $fuelDetail)
        {
            if ($fuelDetail["need"] > 0)
                printf("<tr>
                         <td><em>%s</em></td>
                         <td>%s</td>
                         <td>%s</td>
                         <td>%s</td>
                         <td>%02sd%02sh</td>
                        </tr>", $fuelName,
                                $fuelDetail["need"],
                                $fuelDetail["need"] * 24 * 30,
                                $fuelDetail["quantity"],
                                (int)($fuelDetail["quantity"] / $fuelDetail["need"] / 24),
                                (int)($fuelDetail["quantity"] / $fuelDetail["need"] % 24));
            else
                printf("<tr>
                         <td><em>%s</em></td>
                         <td>-</td>
                         <td>-</td>
                         <td>%s</td>
                         <td>-</td>
                        </tr>", $fuelName,
                                $fuelDetail["quantity"]);
        }
        echo "</table";
        
        echo "</div>";
        
        echo '<div class="posData">';
        echo "<u><em>Fuel Reserve Locations</em></u><br><br>";
        
        foreach($fuelData as $fuelName => $fuelDetail)
        {
            printf("<em>%s</em>: %s<br>", $fuelName, $fuelDetail["quantity"]);
            foreach($fuelDetail["info"] as $locationName => $locationDetails)
            {
                foreach($locationDetails as $containerName => $containerDetails)
                {
                    foreach($containerDetails as $flagName => $quantity)
                    {
                        printf('<p class="indent">(%s - %s - %s: <em>%s</em>)</p>', $locationName, $containerName, $flagName, $quantity);
                    }
                }
            }
        }
        
    }
}
?>