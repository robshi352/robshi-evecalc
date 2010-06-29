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
                printf("<em>%s</em> | %s | %s - %s | installed by %s | %s<br>", $jobID,
                                                                                $jobInfo["activity"],
                                                                                $jobInfo["beginProductionTime"],
                                                                                $jobInfo["endProductionTime"],
                                                                                $jobInfo["installerID"],
                                                                                $jobInfo["trackingStatus"]);
        }
        printf("<em>Cached until</em>: %s", $trackingInfo["cached"]);
    }
    
    function displayInvention($inventionData)
    {
        printf('<div class="invention">', $inventionData["divID"]);
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
        printf('<div class="production">', $productionData["divID"]);
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
            foreach($productionData["data"] as $productor => $productorData)
            {
                echo "<tr>";
                printf('<td>%s</td>', $productor);
                
                if ($productorData["otherCount"] > 0)
                    printf('<td>%s</td>', $productorData["otherCount"]);
                else
                    echo "<td>-</td>";
                
                if ($productorData["otherUtil"] > 0)
                    printf('<td>%.2f%%</td>', $productorData["otherUtil"]);
                else
                    echo "<td>-</td>";
                
                if ($productorData["t2Count"] > 0)
                    printf('<td>%s</td>', $productorData["t2Count"]);
                else
                    echo "<td>-</td>";
                if ($productorData["t2Util"] > 0)
                    printf('<td>%.2f%%</td>', $productorData["t2Util"]);
                else
                    echo "<td>-</td>";
                printf('<td><em>%.2f%%</em></td>', $productorData["t2Util"] + $productorData["otherUtil"]);
                echo "</tr>\n";
            }
            echo "</table>";
            echo "</div>";
        }
    }
    
}
?>