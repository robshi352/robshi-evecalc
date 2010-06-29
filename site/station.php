<?php

class stationLocator
{
    function __construct($db, $currentFunction)
    {
        $this->currentFunction = $currentFunction;
        $this->db = $db;
        $this->services = array("Medical", "Reprocessing");
        
        $query = "SELECT corporationID, itemName
                  FROM crpNpcCorporations AS t1, eveNames AS t2
                  WHERE t1.corporationID = t2.itemID
                  ORDER BY itemName ASC";
        $this->db->query($query);
        
        while($line = mysql_fetch_assoc($this->db->result))
        {
            $this->corpList[$line["corporationID"]] = $line["itemName"];
        }
        $this->formSubmitted = $this->evalForm();
    }
    
    function evalForm()
    {
        if ($_POST["getStations"])
        {
            $this->submitted = true;
            
            $this->formValues["locationName"] = $_POST["locationName"];
            $this->formValues["corpID"] = $_POST["corpID"];
            
            foreach($this->services as $serviceName)
            {
                $this->formValues[$serviceName] = $_POST[$serviceName];
            }
            
            $query = "SELECT solarSystemID
                      FROM mapSolarSystems
                      WHERE solarSystemName = '".mysql_real_escape_string($_POST["locationName"])."'";
            $this->db->query($query);
            
            if (mysql_num_rows($this->db->result) > 0)
            {
                $locationID = mysql_fetch_assoc($this->db->result);
                $locationID = $locationID["solarSystemID"];
                
                $query = "SELECT stationName, distance
                          FROM staStations AS t1, mapDistance as t2
                          WHERE t1.solarSystemID = t2.toSolarSystemID
                          AND t2.fromSolarSystemID = ".$locationID."
                          AND t1.corporationID = ".mysql_real_escape_string($_POST["corpID"])."
                          ORDER BY distance ASC";
                $this->db->query($query);
                while($line = mysql_fetch_assoc($this->db->result))
                    $this->stationList[$line["stationName"]] = $line["distance"];
            }
            else
            {
                $this->formErrors["locationName"] = true;
            }
        }
        else
            $this->submitted = false;
    }
    
    function getServices()
    {
        return $this->services;
    }
    
    function getCorpList()
    {
        return $this->corpList;
    }
    
    function getStationList()
    {
        return $this->stationList;
    }
    
    function getEvalState()
    {
        return $this->evalState;
    }
    
    function getFormValues()
    {
        return $this->formValues;
    }
    
    function formSubmitted()
    {
        return $this->submitted;
    }
    
    function getFormErrors()
    {
        return $this->formErrors;
    }
}

class stationDisplay
{
    function __construct($currentFunction)
    {
        $this->currentFunction = $currentFunction;
    }
    
    function displayForm($corpList, $formSubmitted, $formValues, $formErrors, $services)
    {
        echo "<div id=\"stationLocatorForm\" class=\"content\">";
        echo sprintf("<form method=\"POST\" action=\"%s?func=%s\">", $PHP_SELF, $this->currentFunction);

        echo "Corporation: <select name=\"corpID\">";
        foreach($corpList as $corpID => $corpName)
        {
            if ($corpID == $formValues["corpID"])
                echo sprintf("<option value=\"%d\" selected>%s</option>", $corpID, $corpName);
            else
                echo sprintf("<option value=\"%d\">%s</option>", $corpID, $corpName);
        }
        echo "</select><br>";
        
        echo sprintf("Location: <input type=\"text\" name=\"locationName\" value=\"%s\">", $formValues["locationName"]);
        if ($formErrors["locationName"])
            echo "<strong> Could not find that System.</strong>";
        echo "<br>";
        
        echo "Services: ";
        $firstRun = true;
        foreach($services as $serviceName)
        {
            if (!$firstRun)
                echo " | ";
            if ($formValues[$serviceName] || !$formSubmitted)
                echo sprintf("<input type=\"checkbox\" name=\"%s\" checked>%s", $serviceName, $serviceName);
            else
                echo sprintf("<input type=\"checkbox\" name=\"%s\">%s", $serviceName, $serviceName);
            $firstRun = false;
        }
        echo "<br>";
        
        echo "<input type=\"submit\" value=\"calculate\" name=\"getStations\">";
        echo "</form>";
    }
    
    function displayResult($stationList)
    {
        echo "<div id=\"stationLocatorResult\" class=\"content\">";
        
        echo "<ol>";
        
        foreach($stationList as $stationName => $distance)
        {
            echo "<li>";
            echo sprintf("%d - %s", $distance, $stationName);
            echo "</li>";
        }
        echo "</ol>";
        
        echo "</div>";
    }
}

$stationLocator = new stationLocator($db, $siteFunctions->currentFunction);
$stationDisplay = new stationDisplay($siteFunctions->currentFunction);

$stationDisplay->displayForm($stationLocator->getCorpList(),
                             $stationLocator->formSubmitted(),
                             $stationLocator->getFormValues(),
                             $stationLocator->getFormErrors(),
                             $stationLocator->getServices());

if ($stationLocator->formSubmitted() && sizeof($stationLocator->getFormErrors()) == 0)
    $stationDisplay->displayResult($stationLocator->getStationList());

?>