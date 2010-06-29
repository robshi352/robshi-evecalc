<?php

//  fitting links for testing purposes

//  fitting:22544:1319;2:394;2:2539;1:22229;3:26072;2:2553;1:: Hulk
//  fitting:22544:1319;2:394;2:2539;1:22229;3:26072;2:2553;1:14264;2:19927;3:19933;4:: Faction Scram  & Jam Hulk
//  fitting:22544:1319;2:394;2:2539;1:22229;3:26072;2:2553;1:14264;2:: Faction Scram Hulk
//  fitting:12023:31360;1:2048;1:1952;1:3082;5:31564;1:10190;3:1999;2:5975;1:1978;1:5135;1:: Deimos Sniper
//  fitting:12023:11648;1:2048;1:25861;1:3530;1:31372;2:10190;2:2032;2:12058;1:11644;1:3146;5:2185;5::  Deimos PvE
//  fitting:641:2048;1:1952;1:25894;3:2024;1:10190;2:3090;7:5399;1:5945;1:1306;2:11325;2:: Mega Fleet
//  fitting:641:1952;1:4027;1:25894;3:11303;1:11305;1:11277;1:10190;2:11279;2:3186;7:5945;1:5403;1:2446;5::  Mega Blaster

class efm
{
    function __construct($config)
    {
        $this->config = $config;
        
        // fetch some info from the config file
        $this->prefix = $this->config["db"]["tablePrefix"];
        $this->createRights = $this->config["general"]["fleetCreation"];
        $this->createCorp = $this->config["general"]["corporation"];
        $this->createAlliance = $this->config["general"]["alliance"];
        
        require_once("classes/dbLinkClass.php");
        $this->db = new dbLink($this->config["db"]["host"],
                               $this->config["db"]["user"],
                               $this->config["db"]["password"],
                               $this->config["db"]["database"]);
        
        // fetch pilot information from IGB headers
        $this->pilotInfo["pilotName"] = stripslashes(strip_tags($_SERVER["HTTP_EVE_CHARNAME"]));
        $this->pilotInfo["pilotCorp"] = stripslashes(strip_tags($_SERVER["HTTP_EVE_CORPNAME"]));
        $this->pilotInfo["pilotAlliance"] = stripslashes(strip_tags($_SERVER["HTTP_EVE_ALLIANCENAME"]));
        
        // for testing purpose only!
        if ($this->pilotInfo["pilotName"] == "")
            $this->pilotInfo["pilotName"] = "Test Pilot";

        // check if the current pilot is in a fleet
        $query = "SELECT memberName, memberRole, fleetName, t1.fleetID, fleetPass, fittingLink, fleetType
                  FROM ".$this->prefix."FleetMembers AS t1, ".$this->prefix."Fleets AS t2
                  WHERE memberName = '".mysql_real_escape_string($this->pilotInfo["pilotName"])."'
                  AND fleetEnd IS NULL
                  AND t1.fleetID = t2.fleetID
                  AND memberLeft IS NULL";
        $this->db->query($query);
        
        if ((mysql_num_rows($this->db->result) > 0) && (urldecode($_COOKIE["pilotName"]) == $this->pilotInfo["pilotName"]))
        {
            $this->fleetInProgress = true;
            $line = mysql_fetch_assoc($this->db->result);
            
            $this->currentRole = $line["memberRole"];
            $this->currentFleetID = $line["fleetID"];
            $this->currentFittingLink = $line["fittingLink"];
            $this->currentFleetType = $line["fleetType"];
            
            $this->fleetInfo["fleetID"] = $line["fleetID"];
            $this->fleetInfo["fleetName"] = $line["fleetName"];
            $this->fleetInfo["fleetPass"] = $line["fleetPass"];
            $this->fleetInfo["fleetType"] = $line["fleetType"];
            
            $this->pilotInfo["pilotRole"] = $line["memberRole"];
            $this->pilotInfo["pilotFittingLink"] = $line["fittingLink"];
            
            // get membercount
            $query = "SELECT memberName
                      FROM ".$this->prefix."FleetMembers
                      WHERE fleetID = ".mysql_real_escape_string($this->currentFleetID)."
                      AND memberLeft IS NULL";
            $this->db->query($query);
            
            $this->fleetInfo["fleetMemberCount"] = mysql_num_rows($this->db->result);
        }
        else
        {
            $this->pilotInfo["pilotRole"] = MEMBER;
            $this->fleetInProgress = false;
        }
    }
    
    /* Fleet Creation Handling
      ============================================ */
    
    function hasCreateRights()
    {
        //check if the pilot has the rights to create the fleet
        return (($this->createRights == "public") ||
                (($this->createRights == "corp") && ($this->pilotInfo["pilotCorp"] == $this->createCorp)) ||
                (($this->createRights == "alliance") && ($this->pilotInfo["pilotAlliance"] == $this->createAlliance))
               );
    }
    
    function evalCreateForm()
    {
        //will gather the creation form data and check it
        $this->createFormData["submitted"] = stripslashes(strip_tags($_POST["submit"]));
        $this->createFormData["pilotName"] = $this->pilotInfo["pilotName"];
        $this->createFormData["fleetName"] = stripslashes(strip_tags($_POST["fleetName"]));
        $this->createFormData["fleetType"] = stripslashes(strip_tags($_POST["fleetType"]));
        $this->createFormData["fleetPass"] = stripslashes(strip_tags($_POST["fleetPass"]));
        $this->createFormData["fittingLink"] = stripslashes(strip_tags($_POST["fittingLink"]));
        
        $this->createFormError = false;
        
        if ($this->createFormData["submitted"])
        {
            if (!$this->checkFittingLink($this->createFormData["fittingLink"]))
                $this->createFormError["fittingLink"] = true;
            
            if (($this->createFormData["fleetName"] == "") || !$this->createFormData["fleetName"])
                $this->createFormError["fleetName"] = true;
            
            if ($this->createFormError)
                return false;
            else
                return true;
        }
        return false;
    }
    
    function getCreateFormData()
    {
        return $this->createFormData;
    }
    
    function getCreateFormError()
    {
        return $this->createFormError;
    }
    
    function createFleet()
    {
        //will put stuff in the db and set a cookie, valid for 24 hours
        $query = "INSERT INTO ".$this->prefix."Fleets
                  VALUES(NULL, 
                         '".mysql_real_escape_string($this->createFormData["pilotName"])."',
                         '".mysql_real_escape_string($this->createFormData["fleetName"])."',
                         '".date("o-m-d H:i:s", time())."',
                         NULL,
                         '".mysql_real_escape_string($this->createFormData["fleetPass"])."',
                         '".mysql_real_escape_string($this->createFormData["fleetType"])."')";
        $this->db->query($query);
        $insertID = mysql_insert_id($this->db->link);
        
        $query = "SELECT solarSystemID
                  FROM mapSolarSystems
                  WHERE solarSystemName = '".mysql_real_escape_string($_SERVER["HTTP_EVE_SOLARSYSTEMNAME"])."'";
        $this->db->query($query);
        
        if (mysql_num_rows($this->db->result) == 0)
            $locationID = 0;
        else
        {
            $line = mysql_fetch_assoc($this->db->result);
            $locationID = $line["solarSystemID"];
        }
        
        $query = "INSERT INTO ".$this->prefix."FleetMembers
                  VALUES(".mysql_real_escape_string($insertID).",
                         '".mysql_real_escape_string($this->createFormData["pilotName"])."',
                         ".LEADER.",
                         '".date("o-m-d H:i:s", time())."',
                         NULL,
                         '".mysql_real_escape_string($this->createFormData["fittingLink"])."',
                         ".mysql_real_escape_string($locationID).")";
        $this->db->query($query);
        
        setcookie("pilotName", urlencode($this->pilotInfo["pilotName"]), time() + (60*60*24));
    }    
    

    /* Fleet Join Handling
      ============================================ */

    function evalJoinForm()
    {
        $this->joinFormData["submitted"] = stripslashes(strip_tags($_POST["submit"]));
        $this->joinFormData["pilotName"] = $this->pilotInfo["pilotName"];
        $this->joinFormData["fleetID"] = stripslashes(strip_tags($_POST["fleetID"]));
        $this->joinFormData["fleetPass"] = stripslashes(strip_tags($_POST["fleetPass"]));
        $this->joinFormData["fittingLink"] = stripslashes(strip_tags($_POST["fittingLink"]));
        
        $this->joinFormError = false;
        
        if ($this->joinFormData["submitted"])
        {
            if (!$this->checkFittingLink($this->joinFormData["fittingLink"]))
                $this->joinFormError["fittingLink"] = true;
            
            if (($this->joinFormData["fleetID"] == "") || !$this->joinFormData["fleetID"])
                $this->joinFormError["fleetID"] = true;
            else
            {
                $query = "SELECT fleetPass
                          FROM ".$this->prefix."Fleets
                          WHERE fleetID = ".mysql_real_escape_string($this->joinFormData["fleetID"]);
                $this->db->query($query);
                
                if (mysql_num_rows($this->db->result) > 0)
                {
                    $line = mysql_fetch_assoc($this->db->result);
                    
                    if ($this->joinFormData["fleetPass"] != $line["fleetPass"])
                        $this->joinFormError["fleetPass"] = true;
                }
            }
            if ($this->joinFormError)
                return false;
            else
                return true;
        }
        return false;
    }
    
    function getJoinFormData()
    {
        return $this->joinFormData;
    }
    
    function getJoinFormError()
    {
        return $this->joinFormError;
    }
    
    function getActiveFleets()
    {
        // will return all active fleets and evaluate if the current pilot can join those fleets or not
        $query = "SELECT fleetID, fleetName, fleetType
                  FROM ".$this->prefix."Fleets
                  WHERE fleetEnd IS NULL";
        $this->db->query($query);
        $activeFleets = false;
        
        if (mysql_num_rows($this->db->result) > 0)
        {
            while($line = mysql_fetch_assoc($this->db->result))
            {
                $activeFleets[$line["fleetID"]]["fleetName"] = $line["fleetName"];
                if (($line["fleetType"] == "public") ||
                    (($line["fleetType"] == "corp") && ($this->pilotCorp == $this->createCorp)) ||
                    (($line["fleetType"] == "alliance") && ($this->pilotAlliance == $this->createAlliance))
                   )
                    $activeFleets[$line["fleetID"]]["canJoin"] = true;
                else
                    $activeFleets[$line["fleetID"]]["canJoin"] = false;
            }
        }
        return $activeFleets;
    }

    function joinFleet()
    {
        // will handle the actual fleet join and set a cookie
        $query = "SELECT solarSystemID
                  FROM mapSolarSystems
                  WHERE solarSystemName = '".mysql_real_escape_string($_SERVER["HTTP_EVE_SOLARSYSTEMNAME"])."'";
        $this->db->query($query);
        
        if (mysql_num_rows($this->db->result) == 0)
            $locationID = 0;
        else
        {
            $line = mysql_fetch_assoc($this->db->result);
            $locationID = $line["solarSystemID"];
        }
        
        $query = "INSERT INTO ".$this->prefix."FleetMembers
                  VALUES (".mysql_real_escape_string($this->joinFormData["fleetID"]).",
                          '".mysql_real_escape_string($this->joinFormData["pilotName"])."',
                          ".MEMBER.",
                          '".date("o-m-d H:i:s", time())."',
                          NULL,
                          '".mysql_real_escape_string($this->joinFormData["fittingLink"])."',
                          ".mysql_real_escape_string($locationID).")";
        $this->db->query($query);
        
        setcookie("pilotName", urlencode($this->pilotInfo["pilotName"]), time() + (60*60*24));
    }
    
    /* Fleet End Handling
      ============================================ */
    
    function endFleetConfirmed()
    {
        return (($_GET["confirmFleetEnd"] == "yes") && ($this->pilotInfo["pilotRole"] >= LEADER));
    }
    
    function endFleet()
    {
        $query = "UPDATE ".$this->prefix."Fleets
                  SET fleetEnd = '".date("o-m-d H:i:s", time())."'
                  WHERE fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"]);
        $this->db->query($query);
        
        $query = "UPDATE ".$this->prefix."FleetMembers
                  SET memberLeft = '".date("o-m-d H:i:s", time())."'
                  WHERE fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                  AND memberLeft IS NULL";
        $this->db->query($query);
        
        // invalidate the cookie
        setcookie("pilotName", "", time() - 3600);
    }
    
    /* Fleet Leave Handling
      ============================================ */
    
    function leaveFleetConfirmed()
    {
        return ($_GET["confirmLeaveFleet"] == "yes");
    }
    
    function leaveFleet()
    {
        // invalidate cookie
        setcookie("pilotName", "", time() - 3600);

        $query = "UPDATE ".$this->prefix."FleetMembers
                  SET memberLeft = '".date("o-m-d H:i:s", time())."'
                  WHERE memberName = '".mysql_real_escape_string($this->pilotInfo["pilotName"])."'
                  AND fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                  AND memberLeft IS NULL";
        $this->db->query($query);
        //echo "You left the fleet. Fly safe.";

        // check if just the last person of that fleet left, if yes .. close the fleet
        $query = "SELECT *
                  FROM ".$this->prefix."FleetMembers
                  WHERE fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                  AND memberLeft IS NULL";
        $this->db->query($query);
        if (mysql_num_rows($this->db->result) == 0)
        {
            $query = "UPDATE ".$this->prefix."Fleets
                      SET fleetEnd = '".date("o-m-d H:i:s", time())."'
                      WHERE fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"]);
            $this->db->query($query);
            return;
        }
        $this->checkFleetLeader();
    }
    

    /* Ship Change Handling
      ============================================ */

    function evalChangeShipForm()
    {
        $this->changeFormData["submitted"] = $_POST["submit"];
        $this->changeFormData["fittingLink"] = $this->pilotInfo["pilotFittingLink"];
        
        $parsedFittingLink = $this->parseFittingLink($this->pilotInfo["pilotFittingLink"]);
        $this->changeFormData["pilotShip"] = $parsedFittingLink["shipName"];
        
        $this->changeFormError = false;
        
        if ($this->changeFormData["submitted"])
        {
            $this->changeFormData["fittingLink"] = $_POST["fittingLink"];
            if (!$this->checkFittingLink($this->changeFormData["fittingLink"]))
                $this->changeFormError["fittingLink"] = true;
            
            if ($this->changeFormError)
                return false;
            else
                return true;
        }
        return false;
    }
    
    function changeShip()
    {
        $query = "SELECT memberPositionID
                  FROM ".$this->prefix."FleetMembers
                  WHERE fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                  AND memberName = '".mysql_real_escape_string($this->pilotInfo["pilotName"])."'
                  AND memberLeft IS NULL";
        $this->db->query($query);
        
        $positionID = mysql_fetch_assoc($this->db->result);
        $positionID = $positionID["memberPositionID"];
        
        $query = "UPDATE ".$this->prefix."FleetMembers
                  SET memberLeft = '".date("o-m-d H:i:s", time())."'
                  WHERE fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                  AND memberName = '".mysql_real_escape_string($this->pilotInfo["pilotName"])."'
                  AND memberLeft IS NULL";
        $this->db->query($query);
        
        $query = "INSERT INTO ".$this->prefix."FleetMembers
                  VALUES (".mysql_real_escape_string($this->fleetInfo["fleetID"]).",
                          '".mysql_real_escape_string($this->pilotInfo["pilotName"])."',
                          ".mysql_real_escape_string($this->pilotInfo["pilotRole"]).",
                          '".date("o-m-d H:i:s", time())."',
                          NULL,
                          '".mysql_real_escape_string($this->changeFormData["fittingLink"])."',
                          ".$positionID.")";
        $this->db->query($query);
        
        $this->pilotInfo["pilotFittingLink"] = $this->changeFormData["fittingLink"];
        $parsedFittingLink = $this->parseFittingLink($this->changeFormData["fittingLink"]);
        $this->changeFormData["pilotShip"] = $parsedFittingLink["shipName"];
    }
    
    function getChangeFormData()
    {
        return $this->changeFormData;
    }
    
    function getChangeFormError()
    {
        return $this->changeFormError;
    }
    
    
    /* Fleet Management Handling
      ============================================ */
    
    function evalFleetManagementForm()
    {
        $this->manageFormData["submitted"] = $_POST["submit"];
        $this->manageFormData["pilotName"] = $this->pilotInfo["pilotName"];
        
        $query = "SELECT memberName, memberRole
                  FROM ".$this->prefix."FleetMembers
                  WHERE memberLeft is NULL
                  AND fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                  ORDER BY memberName";
        $this->db->query($query);
        
        while($line = mysql_fetch_assoc($this->db->result))
        {
            if ($this->manageFormData["submitted"])
                $this->manageFormData["members"][$line["memberName"]] = $_POST[urlencode($line["memberName"])];
            else
                $this->manageFormData["members"][$line["memberName"]] = $line["memberRole"];
        }
        
        $this->manageFormError = false;
        
        if ($this->manageFormData["submitted"])
        {
            $this->manageFormData["fleetType"] = $_POST["fleetType"];
            //$this->manage
            return true;
        }
        return false;
    }
    
    function updateFleet()
    {
        //update Fleet Type
        if ($this->pilotInfo["pilotRole"] < COLEADER)
            return false;
        
        $query = "UPDATE ".$this->prefix."Fleets
                  SET fleetType = '".mysql_real_escape_string($this->manageFormData["fleetType"])."'
                  WHERE fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"]);
        $this->db->query($query);
        
        $this->fleetInfo["fleetType"] = $this->manageFormData["fleetType"];
        
        //print_r($this->manageFormData);
        
        foreach($this->manageFormData["members"] as $memberName => $memberRole)
        {
            //update member Roles
            $query = "UPDATE ".$this->prefix."FleetMembers
                      SET memberRole = ".mysql_real_escape_string($memberRole)."
                      WHERE memberName = '".mysql_real_escape_string($memberName)."'
                      AND fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                      AND memberLeft IS NULL";
            $this->db->query($query);
        }
        
        $this->checkFleetLeader();
    }
    
    function checkFleetLeader()
    {
        // check if at least one fleet leader is present, if not .. promote someone
        $query = "SELECT *
                  FROM ".$this->prefix."FleetMembers
                  WHERE memberRole = ".LEADER."
                  AND fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                  AND memberLeft IS NULL";
        $this->db->query($query);
        
        // no other fleet leader present
        if (mysql_num_rows($this->db->result) == 0)
        {
            // select max rank present and oldest member that is non-scout
            $query = "SELECT memberName
                      FROM ".$this->prefix."FleetMembers
                      WHERE fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                      AND memberRole != ".SCOUT."
                      AND memberLeft IS NULL
                      ORDER BY memberRole DESC, memberJoin ASC";
            $this->db->query($query);
            
            $line = mysql_fetch_assoc($this->db->result);
            
            //printf('<br>New Leader of the fleet will be %s', $line["memberName"]);
            
            $query = "UPDATE ".$this->prefix."fleetMembers
                      SET memberRole = ".LEADER."
                      WHERE fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                      AND memberLeft IS NULL
                      AND membername = '".mysql_real_escape_string($line["memberName"])."'";
            $this->db->query($query);
            
            $this->manageFormData["members"][$line["memberName"]] = LEADER;
        }
    }
    
    function getManageFormData()
    {
        return $this->manageFormData;
    }
    
    function getManageFormError()
    {
        return $this->manageFormError;
    }

    /* Kick Handling
      ============================================ */
    
    function kickMode()
    {
        if ($this->pilotInfo["pilotRole"] >= COLEADER)
            return isset($_GET["kick"]);
        else
            return false;
    }
    
    function kickConfirmed()
    {
        if ($this->pilotInfo["pilotRole"] >= COLEADER)
            return isset($_GET["confirmKick"]);
        else
            return false;
    }
    
    
    function kick()
    {
        $kickPilot = urldecode($_GET["kick"]);
        
        $query = "UPDATE ".$this->prefix."FleetMembers
                  SET memberLeft = '".date("o-m-d H:i:s", time())."'
                  WHERE memberName = '".mysql_real_escape_string($kickPilot)."'
                  AND fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                  AND memberLeft IS NULL";
        $this->db->query($query);
        
        $this->checkFleetLeader();
    }

    /* Scout Handling
      ============================================ */
    function fleetHasScout()
    {
        $query = "SELECT memberName
                  FROM ".$this->prefix."FleetMembers
                  WHERE fleetID = ".mysql_real_escape_string($this->currentFleetID)."
                  AND memberLeft IS NULL
                  AND memberRole = ".SCOUT;
        $this->db->query($query);
        
        if (mysql_num_rows($this->db->result) == 0)
            return false;
        else
            return true;
    }
    
    function updateScoutPosition()
    {
        $query = "SELECT solarSystemID
                  FROM mapSolarSystems
                  WHERE solarSystemName = '".mysql_real_escape_string($_SERVER["HTTP_EVE_SOLARSYSTEMNAME"])."'";
        $this->db->query($query);
        
        if (mysql_num_rows($this->db->result) == 0)
            $locationID = 0;
        else
        {
            $line = mysql_fetch_assoc($this->db->result);
            $locationID = $line["solarSystemID"];
        }
        
        $query = "UPDATE ".$this->prefix."FleetMembers
                  SET memberPositionID = ".$locationID."
                  WHERE fleetID = ".mysql_real_escape_string($this->currentFleetID)."
                  AND memberName = '".mysql_real_escape_string($this->pilotInfo["pilotName"])."'
                  AND memberLeft IS NULL";
        $this->db->query($query);
    }
    
    function getScoutPosition()
    {
        $query = "SELECT memberName, solarSystemName
                  FROM ".$this->prefix."FleetMembers LEFT JOIN mapSolarSystems ON memberPositionID = solarSystemID
                  WHERE fleetID = ".mysql_real_escape_string($this->currentFleetID)."
                  AND memberLeft IS NULL
                  AND memberRole = ".SCOUT."
                  ORDER BY memberName";
        $this->db->query($query);
        
        while($line = mysql_fetch_assoc($this->db->result))
        {
            $scoutPositions[$line["memberName"]] = $line["solarSystemName"];
        }
        
        return $scoutPositions;
    }
        
    function scoutMode()
    {
        return (($this->currentRole >= SCOUT) && ($this->fleetHasScout()));
    }
    
    /* various get Functions Handling
      ============================================ */
    
    function getPilotName()
    {
        return $this->pilotInfo["pilotName"];
    }
    
    function getPilotRole()
    {
        return $this->pilotInfo["pilotRole"];
    }
    
    function getFleetInfo()
    {
        return $this->fleetInfo;
    }
    
    function getFleetEWar()
    {
        $query = "SELECT memberName, fittingLink
                  FROM ".$this->prefix."FleetMembers
                  WHERE fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                  AND memberLeft IS NULL
                  ORDER BY memberName ASC";
        $this->db->query($query);
        $result = $this->db->result;
        
        
        while($line = mysql_fetch_assoc($result))
        {
            $fittings[$line["memberName"]] = $this->parseFittingLink($line["fittingLink"]);
        }

        foreach($fittings as $memberName => $fitting)
        {
            foreach($fitting as $fittingShort => $fittingDetail)
            {
                $overallFitting[$fittingShort] += $fittingDetail["count"];
            }
        }
        $fittings["fleet_overall"] = $overallFitting;
        
        return $fittings;
    }
    
    function getFleetShips()
    {
        $query = "SELECT memberName, fittingLink
                  FROM ".$this->prefix."FleetMembers
                  WHERE fleetID = ".mysql_real_escape_string($this->fleetInfo["fleetID"])."
                  AND memberLeft IS NULL
                  ORDER BY memberName";
        $this->db->query($query);
        $result = $this->db->result;
        
        while($line = mysql_fetch_assoc($result))
        {
            $fitting = $this->parseFittingLink($line["fittingLink"]);
            $ships[$line["memberName"]] = $fitting["shipName"];
            $shipList[$fitting["shipName"]] += 1;
        }
        return $ships;
    }
    
    function isFleetInProgress()
    {
        return $this->fleetInProgress;
    }
    
    
    /* Fitting Link Handling
      ============================================ */

    
    function parseFittingLink($fittingLink)
    {
        // will parse the fitting link to an array with shipname and
        // rest will be groupID => count based on the filterSettings
        
        //short (used as identifier & image name) => description text ... used for ordering and description
        $filterSettings = array("warpscram"     => array("description"  => "Warp Scrambler",
                                                         "groupName"    => "Warp Scrambler"),
                                "warpdis"       => array("description"  => "Warp Disruptor",
                                                         "groupName"    => "Warp Scrambler"),
                                "disfield"      => array("description"  => "Warp Disruption Field Generators",
                                                         "groupName"    => "Warp Disrupt Field Generator"),
                                "warpsphere"    => array("description"  => "Interdiction Sphere Launcher",
                                                         "groupName"    => "Warp Scrambler"),
                                "web"           => array("description"  => "Stasis Webifiers",
                                                         "groupName"    => "Stasis Web"),
                                "damp"          => array("description"  => "Remote Sensor Dampers",
                                                         "groupName"    => "Remote Sensor Damper"),
                                "paint"         => array("description"  => "Target Painters",
                                                         "groupName"    => "Target Painter"),
                                "disrupt"       => array("description"  => "Tracking Disruptors",
                                                         "groupName"    => "Tracking Disruptor"),
                                "remotesensor"  => array("description"  => "Remote Sensor Boosters",
                                                         "groupName"    => "Remote Sensor Booster"),
                                "vamp"          => array("description"  => "Energy Vampires",
                                                         "groupName"    => "Energy Vampire"),
                                "neut"          => array("description"  => "Energy Destabilizer",
                                                         "groupName"    => "Energy Destabilizer"),
                                "et"            => array("description"  => "Energy Transfer Arrays",
                                                         "groupName"    => "Energy Transfer Array"),
                                "rs"            => array("description"  => "Shield Transporters",
                                                         "groupName"    => "Shield Transporter"),
                                "ra"            => array("description"  => "Remote Armor Repair Systems",
                                                         "groupName"    => "Armor Repair Projector"),
                                "rh"            => array("description"  => "Remote Hull Repair Systems",
                                                         "groupName"    => "Remote Hull Repairer"),
                                "ecmbursts"     => array("description"  => "ECM Bursts",
                                                         "groupName"    => "ECM Burst"),
                                "gravjam"       => array("description"  => "Gravimetric Jammers",
                                                         "groupName"    => "ECM"),
                                "ladarjam"      => array("description"  => "Ladar Jammers",
                                                         "groupName"    => "ECM"),
                                "magjam"        => array("description"  => "Magnetometric Jammers",
                                                         "groupName"    => "ECM"),
                                "radarjam"      => array("description"  => "Radar Jammers",
                                                         "groupName"    => "ECM"),
                                "multijam"      => array("description"  => "Multi Spectrum Jammers",
                                                         "groupName"    => "ECM"));

        // select the group id's for the filter
        foreach($filterSettings as $filterShort => $filterDetail)
        {
            $query = "SELECT groupID
                      FROM invGroups
                      WHERE groupName = '".mysql_real_escape_string($filterDetail["groupName"])."'";
            $this->db->query($query);
            
            $line = mysql_fetch_assoc($this->db->result);
            $filterGroupID[] = $line["groupID"];
            $filterIDtoShort[$line["groupID"]] = $filterShort;
            $returnFittingArray[$filterShort] = array("description" => $filterDetail["description"], "count" => 0);
        }
        
        $fittingLink = str_replace("fitting:", "", $fittingLink);
        $fittingLink = str_replace("::", "", $fittingLink);
        
        // clean up the fitting link for parsing
        // fittting: id => (typeID, count)
        
        $fittings = explode(":", $fittingLink);
        foreach($fittings as $key => $fitting)
            $fittings[$key] = explode(";", $fitting);
        
        foreach($fittings as $key => $fitting)
        {
            $query = "SELECT typeName,
                             t1.groupID,
                             typeName LIKE '%Disruptor%' AS warpdis,
                             typeName LIKE '%Scrambler%' as warpscram,
                             t3.description LIKE 'Caldari%' as caldari,
                             t3.description LIKE 'Amarr%' as amarr,
                             t3.description LIKE 'Gallente%' as gallente,
                             t3.description LIKE 'Minmatar%' as minmatar
                      FROM invTypes AS t1,
                           invGroups AS t2,
                           eveGraphics AS t3
                      WHERE typeID = ".mysql_real_escape_string($fitting[0])."
                      AND t1.groupID = t2.groupID
                      AND t1.graphicID = t3.graphicID";
            $this->db->query($query);
            
            if (mysql_num_rows($this->db->result) > 0)
            {
                $line = mysql_fetch_assoc($this->db->result);
                $fittingName = $line["typeName"];
                $fittingGroupID = $line["groupID"];
            }
            else
                $fittingName = "No Name found";
            
            // key == 0 => it's the ship
            if ($key == 0)
                $returnFittingArray["shipName"] = $fittingName;
            
            // else only add, if it's in the filter
            elseif (in_array($fittingGroupID, $filterGroupID))
            {
                if (($filterIDtoShort[$fittingGroupID] == "warpscram") ||
                    ($filterIDtoShort[$fittingGroupID] == "warpdis") ||
                    ($filterIDtoShort[$fittingGroupID] == "warpsphere"))
                {
                    if ($line["warpdis"])
                        $returnFittingArray["warpdis"]["count"] += $fitting[1];
                    elseif ($line["warpscram"])
                        $returnFittingArray["warpscram"]["count"] += $fitting[1];
                    else
                        $returnFittingArray["warpsphere"]["count"] += $fitting[1];
                }
                elseif (($filterIDtoShort[$fittingGroupID] == "gravjam") ||
                        ($filterIDtoShort[$fittingGroupID] == "ladarjam") ||
                        ($filterIDtoShort[$fittingGroupID] == "magjam") ||
                        ($filterIDtoShort[$fittingGroupID] == "multijam") ||
                        ($filterIDtoShort[$fittingGroupID] == "radarjam"))
                {
                    if ($line["caldari"])
                        $returnFittingArray["gravjam"]["count"] += $fitting[1];
                    elseif ($line["minmatar"])
                        $returnFittingArray["ladarjam"]["count"] += $fitting[1];
                    elseif ($line["gallente"])
                        $returnFittingArray["magjam"]["count"] += $fitting[1];
                    elseif ($line["amarr"])
                        $returnFittingArray["radarjam"]["count"] += $fitting[1];
                    else
                        $returnFittingArray["multijam"]["count"] += $fitting[1];
                }
                else
                {
                    $returnFittingArray[$filterIDtoShort[$fittingGroupID]]["count"] += $fitting[1];
                }
            }
        }
        return $returnFittingArray;
    }
    
    function checkFittingLink($fittingLink)
    {
        if (!ereg("^fitting:", $fittingLink))
        {
            return false;
        }
        
        if (!ereg("::$", $fittingLink))
        {
            return false;
        }
        
        if (!ereg("[0-9]+:[0-9]+;", $fittingLink))
        {
            return false;
        }
        
        return true;
    }
}

?>