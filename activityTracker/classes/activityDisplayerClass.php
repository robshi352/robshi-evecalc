<?php
/*Copyright 2010 @ masubious*/

class activityDisplayer
{
    function displayInfo()
    {
        echo '<div id="info">
        <u><b>Random Notes</b></u>
        <ul>
            <li>Update three times a day</li>
            <li>Inventions will only be tracked if they\'re delivered at the time of an update</li>
            <li>Utilization = (sum of time manufacturing / (Time in a week * 10))</li>
            <li>Utilization is based on 10 manufacturing jobs</li>
        </ul>
      </div>';
    }
    
    function displayTrackingInfo($trackingInfo)
    {
        foreach($trackingInfo as $jobID => $jobInfo)
        {
            if ($jobID != "cached")
                printf("<em>%s</em> | x%s | %s | %s - %s | by %s | in %s | factor %s | %s<br>", $jobID,
                                                                                $jobInfo["runs"],
                                                                                $jobInfo["activity"],
                                                                                $jobInfo["beginProductionTime"],
                                                                                $jobInfo["endProductionTime"],
                                                                                $jobInfo["installerID"],
                                                                                $jobInfo["containerTypeID"],
                                                                                $jobInfo["timeFactor"],
                                                                                $jobInfo["trackingStatus"]);
        }
        printf("<em>Cached until</em>: %s", $trackingInfo["cached"]);
    }
    
    function displayInvention($inventionData)
    {
        printf('<div class="invention">');
        printf('<em><u>Invention Report for Week %s</u></em><br>', $inventionData["week"]);
        printf("%s - %s <br><br>\n", $inventionData["startDate"], $inventionData["endDate"]);
        
        if (!$inventionData["data"])
            echo "<em>No inventions happened.</em>";
        else
        {
            echo "<table>\n";
            echo "<tr>";
            echo "<th>Name</th>";
            echo "<th># Inventions</th>";
            echo "<th>% Successful</th>";
            echo "<th>T2 Mod Factor</th>";
            echo "</tr>\n";
            foreach($inventionData["data"] as $inventor => $inventorData)
            {
                echo "<tr>";
                printf('<td>%s</td>', $inventor);
                printf('<td>%s</td>', $inventorData["inventions"]);
                printf('<td>%.2f%%</td>', $inventorData["successful"] / $inventorData["inventions"] * 100);
                printf('<td>%.2f</td>', $inventorData["t2ModFactor"]);
                echo "</tr>\n";
            }
            echo "<tr>";
            echo "<td><em>Total</em></td>";
            printf('<td><em>%s</em></td>', $inventionData["totalInventions"]);
            printf('<td><em>%.2f%%</em></td>', $inventionData["totalSuccessful"] / $inventionData["totalInventions"] * 100);
            echo '<td><em>-</em></td>';
            echo "</tr>";
            echo "</table>";
            echo "</div>";
        }
    }
    
    function displayProduction($productionData)
    {
        printf('<div class="production">');
        printf('<em><u>Production Report for Week %s</u></em><br>', $productionData["Week"]);
        printf("%s - %s <br><br>\n", $productionData["startDate"], $productionData["endDate"]);
        
        if (!$productionData["data"])
            echo "<em>No Production happened.</em>";
        else
        {
            echo "<table>\n";
            echo "<tr>";
            echo "<th>Name</th>";
            echo "<th># Other</th>";
            echo "<th>% Other Util</th>";
            echo "<th># T2</th>";
            echo "<th>% T2 Util</th>";
            echo "<th>% Total Util</th>";
            echo "</tr>\n";
            foreach($productionData["data"] as $producer => $producerData)
            {
                echo "<tr>";
                printf('<td>%s</td>', $producer);
                
                if ($producerData["otherCount"] > 0)
                    printf('<td>%s</td>', $producerData["otherCount"]);
                else
                    echo "<td>-</td>";
                
                if ($producerData["otherUtil"] > 0)
                    printf('<td>%.2f%%</td>', $producerData["otherUtil"]);
                else
                    echo "<td>-</td>";
                
                if ($producerData["t2Count"] > 0)
                    printf('<td>%s</td>', $producerData["t2Count"]);
                else
                    echo "<td>-</td>";
                if ($producerData["t2Util"] > 0)
                    printf('<td>%.2f%%</td>', $producerData["t2Util"]);
                else
                    echo "<td>-</td>";
                printf('<td><em>%.2f%%</em></td>', $producerData["t2Util"] + $producerData["otherUtil"]);
                echo "</tr>\n";
            }
            echo "</table>";
            echo "</div>";
        }
    }
    
    function displayStats($statData)
    {
        printf('<div class="stats">');
        printf('<em><u>Stats for Week %s</u></em><br>', $statData["Week"]);
        printf("%s - %s <br><br>\n", $statData["startDate"], $statData["endDate"]);
        
        if (!$statData["data"])
            echo "<em>Nothing happened.</em>";
        else
        {
            echo "<table>\n";
            echo "<tr>";
            echo "<th>Item</th>";
            echo "<th>Inventions</th>";
            echo "<th>Successful</th>";
            echo "<th>%</th>";
            echo "<th>built</th>";
            echo "</tr>\n";
            foreach($statData["data"] as $itemName => $itemData)
            {
                echo "<tr>";
                printf('<td><em>%s</em></td>', $itemName);
                
                if ($itemData["invcnt"] > 0)
                    printf('<td>%s</td>', $itemData["invcnt"]);
                else
                    echo "<td>-</td>";
                
                if ($itemData["invsucc"] > 0)
                    printf('<td>%s</td>', $itemData["invsucc"]);
                else
                    echo "<td>-</td>";
                
                if ($itemData["invcnt"] > 0)
                    printf('<td>%.2f%%</td>', $itemData["invsucc"] / $itemData["invcnt"] * 100);
                else
                    echo "<td>-</td>";
                    
                if ($itemData["prodcnt"] > 0)
                    printf('<td>%s</td>', $itemData["prodcnt"]);
                else
                    echo "<td>-</td>";
                    
                echo "</tr>\n";
            }
            echo "</table>";
            echo "</div>";
        }
    }
    
}
?>