<?php

class efmDisplayer
{
    function __construct($currentFunction)
    {
        $this->roles = array(MEMBER     => "Member",
                             SCOUT      => "Scout",
                             COLEADER   => "Coleader",
                             LEADER     => "Leader");
        
        $this->currentFunction = $currentFunction;
        $this->currentLink = "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    
    function displayCreateForm($formData, $formError)
    {
        //print_r($formData);
        printf('<form method="POST" action="%s">', $this->currentLink);
        
        echo "<fieldset>";
        echo "<legend>Create a Fleet</legend>";
        echo "<ol>";
        echo "<li>";
        echo "<label>";
        printf("<em>Pilot Name:</em> %s", $formData["pilotName"]);
        echo "</label>";
        echo "</li>";
        
        echo "<li>";
        echo '<label for="fleetName">Name of the Fleet</label>';
        printf('<input type="text" name="fleetName" value="%s">', $formData["fleetName"]);
        if ($formError["fleetName"])
            echo "<strong> Please enter a Fleet Name.</strong>";
        echo "</li>";

        echo "<li>";
        echo '<label for="fleetType">';
        echo "Type of the Fleet";
        echo "</label>";
        echo '<select name="fleetType">';
        
        foreach(array("public", "corp", "alliance") as $fleetType)
        {
            if ($formData["fleetType"] == $fleetType)
                printf('<option value="%s" selected>%1$s</option>', $fleetType);
            else
                printf('<option value="%s">%1$s</option>', $fleetType);
        }
        echo "</select>";
        echo "</li>";
        
        echo "<li>";
        echo '<label for="fittingLink">';
        echo "Fitting Link";
        echo "</label>";
        printf('<input type="text" name="fittingLink" value="%s">', $formData["fittingLink"]);
        if ($formError["fittingLink"])
            echo "<strong> Please enter a correct fitting link.</strong>";
        echo "</li>";
        
        echo "<li>";
        echo '<label for="fleetPass">';
        echo "Fleet Password";
        echo "</label>";
        printf('<input type="text" name="fleetPass" value="%s">', $formData["fleetPass"]);
        echo "</li>";
        
        echo "</ol>";
        echo "</fieldset>";
        
        echo '<fieldset class="submit">';
        echo '<input class="submit" type="submit" name="submit" value="Create Fleet">';
        echo "</fieldset>";
        echo "</form>";
    }
    
    function displayScoutPosition($scoutPositions)
    {
        echo "<div id=\"scoutPosition\">";
        
        $firstRun = true;
        foreach($scoutPositions as $scoutName => $scoutLocation)
        {
            if (!$firstRun)
                echo " | ";
            printf("<em>%s</em>: %s", $scoutName, $scoutLocation);
            $firstRun = false;
        }
        
        echo "</div>";
    }
    
    function displayCredits()
    {
        echo "<div id=\"credits\">";
        
        echo "<u>Features</u>";
        echo "<ul>
                <li>Easy Fleet Management</li>
                <li>Security Settings for Fleet creation (corp | alliance | public)</li>
                <li>Security Settings for joining a Fleet (corp | alliance | public)</li>
                <li>Fleet Overview for E-War & Remote Rep Modules</li>
                <li>Automatic Scout Location Update</li>
              </ul>";
        echo "<u>Fitting Link</u>";
        echo "<ol>";
        echo "<li>Select your Fitting, Drag &amp; Drop to the Chat Area</li>
              <li>Right Click the Link and open in the Ingame Browser</li>
              <li>select the URL and copy it, it should like \"fitting://\" followed by some numbers</li>
              <li>Paste into the appropiate input box and have fun</li>";
        echo "</ol>";
        echo "<br>";
        echo "<u>Developer</u>: <em>Gar Karath</em>";
        echo "</div>";
    }

    function displayFleetInfo($fleetInfo, $pilotRole)
    {
        echo '<div id="fleetinfo">';
        
        printf("<em>Fleet</em>: %s - %s | ", $fleetInfo["fleetName"], $fleetInfo["fleetType"]);
        
        printf("<em>Role</em>: %s | ", $this->roles[$pilotRole]);
        
        printf("<em>Members</em>: %s | ", $fleetInfo["fleetMemberCount"]);
        
        if ($fleetInfo["fleetPass"] == "")
            echo "No Password";
        else
            printf("<em>Password</em>: %s", $fleetInfo["fleetPass"]);
        echo "</div>";
    }
    
    function displayJoinForm($formData, $formError, $activeFleets)
    {
        echo '<div id="fleetJoin">';
        
        if ($activeFleets)
        {
            printf('<form method="POST" action="%s">', $this->currentLink);
            echo "<fieldset>";
            echo "<legend>";
            echo "Join a Fleet";
            echo "</legend>";
            echo "<ol>";
            
            echo "<li>";
            echo "<label>";
            printf('<em>Pilot Name: </em>%s', $formData["pilotName"]);
            echo "</label>";
            echo "</li>";

            echo '<label for="fleetID">';
            echo "Fleet";
            echo "</label>";            

            foreach($activeFleets as $fleetID => $fleetDetails)
            {
                echo "<li>";
                if ($fleetDetails["canJoin"])
                {
                    if ($formData["fleetID"] == $fleetID)
                        printf('<input type="radio" name="fleetID" value="%s" checked> %s', $fleetID, $fleetDetails["fleetName"]);
                    else
                        printf('<input type="radio" name="fleetID" value="%s"> %s', $fleetID, $fleetDetails["fleetName"]);
                }
                else
                {
                    printf('<input type="radio" name="fleetID" value="%s" disabled> %s', $fleetID, $fleetDetails["fleetName"]);
                    echo " | <strong>You don't have the permission to join this Fleet, sorry.</strong>";
                }
                echo "</li>";
            }
            
            if ($formError["fleetID"])
                echo "<strong>Please Select a Fleet to Join</strong>";
            
            echo "<li>";
            echo '<label for="fittingLink">';
            echo "Fitting Link";
            echo "</label>";
            
            printf('<input type="text" name="fittingLink" value="%s">', $formData["fittingLink"]);
            if ($formError["fittingLink"])
                echo "<strong>Please check the fitting Link.</strong>";
            echo "</li>";
            
            echo "<li>";
            echo '<label for="fleetPass">';
            echo "Fleet Password";
            echo "</label>";
            
            echo '<input type="text" name="fleetPass\">';
            if ($formError["fleetPass"])
                echo "<strong>Sorry, wrong Password</strong>";
            echo "</li>";
            
            echo "</ol>";
            echo "</fieldset>";
            
            echo '<fieldset class="submit">';
            echo '<input type="submit" value="join Fleet" name="submit">';
            echo "</fieldset>";
            echo "</form>";
        }
        else
            echo "Sorry, there are no active fleets at the moment.";
        echo "</div>";
    }
    
    function displayEWar($fleetEWar, $pilotName)
    {

        $iconsize = "32x32";
        $resize = 24;
        
        echo '<div id="viewFleet">';
        
        printf('<table>
                <tr>
                    <th><a href="%s&mode=2">> v2</a></th>', "http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."?func=".$this->currentFunction);
        echo '<th class="mark">Ship</th>';
        
        //display table header
        foreach($fleetEWar[$pilotName] as $fittingShort => $fittingDetail)
        {
            if ($fittingShort != "shipName")
                printf('<th><img alt="%s" height="%s" width="%2$s" title="%s" src="images/%s/%1$s.png"></th>', $fittingShort, $resize, $fittingDetail["description"], $iconsize);
        }
        echo "</tr>";
        
        //display member stats
        foreach($fleetEWar as $memberName => $fitting)
        {
            if ($memberName != "fleet_overall")
            {
                echo "<tr>";
                printf("<td>%s</td>", $memberName);
                printf('<td class="mark">%s</td>', $fitting["shipName"]);
                
                foreach($fitting as $fittingShort => $fittingDetail)
                {
                    if ($fittingShort != "shipName")
                    {
                        if ($fittingDetail["count"] > 0)
                            printf("<td>%s</td>", $fittingDetail["count"]);
                        else
                            echo "<td>-</td>";
                    }
                }
                echo "</tr>";
            }
        }
        
        //display overall
        echo '<tr class="mark">
                <td><em>Overall</em></td>
                <td class="mark">-</td>';
        foreach($fleetEWar["fleet_overall"] as $fittingShort => $count)
        {
            if ($fittingShort != "shipName")
            {
                if ($count > 0)
                    printf("<td><em>%s</em></td>", $count);
                else
                    echo "<td>-</td>";
            }
        }
        echo "</tr>";            
        
        echo "</table>";
        
        echo "</div>";
    }
    
    function displayVertEWar($fleetEWar, $pilotName)
    {

        $iconsize = "32x32";
        $resize = 24;
        
        echo '<div id="viewFleet">';

        printf('<table>
                <tr class="vmark">
                    <th><a href="%s&mode=3">> v3</a></th>', "http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."?func=".$this->currentFunction);

        
        foreach($fleetEWar as $memberName => $fitting)
        {
            if ($memberName != "fleet_overall")
            {
                echo "<th>";
                for ($i=0; $i<=strlen($memberName) - 1; $i++)
                    echo $memberName[$i]."<br>";
                echo "</th>";
            }
        }
        
        echo '<th class="vmark"><em>';
        $caption = "Overall";
        for($i=0; $i<=strlen($caption) - 1; $i++)
            echo $caption[$i]."<br>";
        echo '</em></th></tr>';
        
        foreach($fleetEWar[$pilotName] as $fittingShort => $fittingDetail)
        {
            if ($fittingShort != "shipName")
            {
                echo "<tr>";
                printf('<td><img alt="%s" height="%s" width="%2$s" title="%s" src="images/%s/%1$s.png"></td>', $fittingShort, $resize, $fittingDetail["description"], $iconsize);
                foreach($fleetEWar as $memberName => $fitting)
                {
                    if ($memberName != "fleet_overall")
                        printf('<td>%s</td>', $fleetEWar[$memberName][$fittingShort]["count"]);
                }
                printf('<td class="vmark"><em>%s</em></td>', $fleetEWar["fleet_overall"][$fittingShort]);
                echo "</tr>";
            }
        }
        
        echo "</table>";
        
        echo "</div>";
    }
    
    function displayVertEWar2($fleetEWar, $pilotName)
    {

        $iconsize = "32x32";
        $resize = 24;
        
        echo '<div id="viewFleet">';

        printf('<table>
                <tr class="vmark">
                    <th><a href="%s&mode=1">> v1</a></th>', "http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]."?func=".$this->currentFunction);
        
        foreach($fleetEWar as $memberName => $fitting)
        {
            if ($memberName != "fleet_overall")
            {
                printf("<th>%.3s</th>", $memberName);
            }
        }
        
        printf('<th class="vmark"><em>%.3s</em></th></tr>', "Overall");
        
        foreach($fleetEWar[$pilotName] as $fittingShort => $fittingDetail)
        {
            if ($fittingShort != "shipName")
            {
                echo "<tr>";
                printf('<td><img alt="%s" height="%s" width="%2$s" title="%s" src="images/%s/%1$s.png"></td>', $fittingShort, $resize, $fittingDetail["description"], $iconsize);
                foreach($fleetEWar as $memberName => $fitting)
                {
                    if ($memberName != "fleet_overall")
                        printf('<td>%s</td>', $fleetEWar[$memberName][$fittingShort]["count"]);
                }
                printf('<td class="vmark"><em>%s</em></td>', $fleetEWar["fleet_overall"][$fittingShort]);
                echo "</tr>";
            }
        }
        
        echo "</table>";
        
        echo "</div>";
    }
    
    function displayEndConfirm($pilotRole)
    {
        if ($pilotRole >= LEADER)
        {
            echo '<div id="confirmEnd">';
            echo "Really end this Fleet?<br>";
            printf('<a href="%s&confirmFleetEnd=yes">Yes</a>', $this->currentLink);
            echo " | ";
            printf('<a href="%s?func=view">No</a>', "http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]);
            echo "</div>";
        }
        else
            echo "Sorry, you cannot do this";
    }
    
    function displayLeaveConfirm()
    {
            echo '<div id="confirmLeave">';
            echo "Really leave this Fleet?<br>";
            printf('<a href="%s&confirmLeaveFleet=yes">Yes</a>', $this->currentLink);
            echo " | ";
            printf('<a href="%s?func=view">No</a>', "http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"]);
            echo "</div>";
    }
    
    function displayShips($ships)
    {
        // reparse the ship list
        foreach($ships as $memberName => $ship)
        {
            $shipList[$ship] += 1;
            
            if ($pilotList[$ship])
                $pilotList[$ship] .= ", ";
            $pilotList[$ship] .= $memberName;
        }

        echo '<div id="shipOverview">';
        echo "<ul>";
        
        foreach($shipList as $ship => $count)
        {
            echo "<li>";
            printf("<em>%s</em>: %s (%s)", $ship, $count, $pilotList[$ship]);
            echo "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    function displayChangeShipForm($formData, $formError)
    {
        echo '<div id="changeForm">';
        printf('<form method="POST" action="%s">', $this->currentLink);
        
        echo "<fieldset>";
        echo "<legend>";
        echo "change Ship";
        echo "</legend>";
        echo "<ol>";
        echo "<li>";
        echo '<label for="newFittingLink">';
        printf('current Ship: <em>%s</em>', $formData["pilotShip"]);
        echo "</label>";
        
        printf('<input type="text" size="30" name="fittingLink" value="%s">', $formData["fittingLink"]);
        if ($formError["fittingLink"])
            echo "<strong>Please check the Fitting Link</strong>";
        echo "</li>";
        echo "</ol>";
        echo "</fieldset>";
        
        echo '<fieldset class="submit">';
        echo '<input type="submit" value="change Fitting" name="submit">';
        echo "</fieldset>";
        echo "</form>";
        echo "</div>";
    }
    
    function displayFleetManagementForm($formData, $formError)
    {
        echo '<div id="fleetManagement">';
        echo "<fieldset>";
        echo "<legend>Fleet Management</legend>";
        
        printf('<form method="POST" action="%s">', $this->currentLink);
        echo "<ol>";
        echo "<li>";
        echo '<label for="fleetType">';
        echo "<em>Fleet Type</em>";
        echo "</label>";
        echo '<select name="fleetType">';
        
        foreach(array("public", "corp", "alliance") as $fleetType)
        {
            if ($formData["fleetType"] == $fleetType)
                printf('<option value="%s" selected>%1$s</option>', $fleetType);
            else
                printf('<option value="%s">%1$s</option>', $fleetType);
        }
        echo "</select>";
        echo "</li>";
        
        echo "<li>";
        echo "</li>";
        
        foreach($formData["members"] as $memberName => $memberRole)
        {
            echo "<li>";
            printf('<label for="%s">', urlencode($memberName));
            echo $memberName;
            
            if ($formData["pilotName"] != $memberName)
                printf(' (<a href="%s&kick=%s">Kick</a>)', $this->currentLink, urlencode($memberName));
            echo "</label>";
            
            printf('<select name="%s">', urlencode($memberName));
            foreach($this->roles as $roleID => $roleName)
            {
                if ($memberRole == $roleID)
                    printf('<option value="%s" selected>%s</option>', $roleID, $roleName);
                else
                    printf('<option value="%s">%s</option>', $roleID, $roleName);
            }
            echo "</select>";
            echo "</li>";
        }
        
        echo "</ol>";
        echo "</fieldset>";
        echo '<fieldset class="submit">';
        echo '<input type="submit" value="update Fleet" name="submit">';
        echo "</fieldset>";
        echo "</div>";
    }
    
    function displayKickConfirm()
    {
        $kickPilot = $_GET["kick"];
        
        echo '<div id="confirmKick">';
        printf("Really kick <em>%s</em>?<br>", urldecode($kickPilot));
        printf('<a href="%s&confirmKick=yes">Yes</a>', $this->currentLink);
        echo " | ";
        printf('<a href="%s&confirmKick=no">No</a>', $this->currentLink);
        echo "</div>";
    }    
    
}
?>