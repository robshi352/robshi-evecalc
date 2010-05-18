<?php

    class marketGroupConverter
    {
        public $marketGroups;
        
        function createTable()
        {
            global $db;
            
            $query = "CREATE TABLE IF NOT EXISTS invNestedMarketGroups (
                        nestedID int(11) UNSIGNED AUTO_INCREMENT,
                        marketGroupID int(11),
                        lft smallint UNSIGNED,
                        rgt smallint UNSIGNED,
                        PRIMARY KEY(nestedID),
                        KEY (marketGroupID))";
            $db->query($query);
        }
        
        function setMarketGroups()
        {
            global $db;
            
            $query = "SELECT marketGroupID, parentGroupID
                      FROM invMarketGroups
                      ORDER BY parentGroupID ASC, marketGroupID ASC";
            $db->query($query);
            
            while($line = mysql_fetch_assoc($db->result))
            {
                $this->marketGroups[$line["marketGroupID"]] = $line["parentGroupID"];
                $this->groupConverted[$line["marketGroupID"]] = false;
            }
            echo "Market Groups Set<br>";
        }
        
        function alreadyConverted($marketGroupID)
        {
            global $db;
            
            $query = "SELECT marketGroupID
                      FROM invNestedMarketGroups
                      WHERE marketGroupID = ".$marketGroupID;
            $db->query($query);
            
            if (mysql_num_rows($db->result) == 0)
                return false;
            
            $line = mysql_fetch_assoc($db->result);
            {
                if (!$line["marketGroupID"] and !$this->groupConverted[$marketGroupID])
                    return false;
                else
                    return true;
            }
        }
        
        function convert()
        {
            $i = 0;
            $all = false;
            
            while ($all == false)
            {
                $all = true;
                foreach($this->marketGroups as $marketGroupID => $parentGroupID)
                {
                    if (!$this->alreadyConverted($marketGroupID))
                    {
                        $i ++;
                        echo "<b>$i: </b>";
                        $all = false;
                        $this->insert($marketGroupID, $parentGroupID);
                        $this->groupConverted[$marketGroupID] = true;
                    }
                }
            }
        }
        
        function insert($marketGroupID, $parentGroupID)
        {
            global $db;
            
            echo "trying ".$marketGroupID." - ".$parentGroupID." | ";

            if ($parentGroupID)
            {
                $query = "SELECT lft, rgt
                          FROM invNestedMarketGroups
                          WHERE marketGroupID = ".$parentGroupID;
                $db->query($query);
                if (mysql_num_rows($db->result) > 0)
                {
                    $line = mysql_fetch_assoc($db->result);
                    $parentLeft = $line["lft"];
                    $parentRight = $line["rgt"];
                    echo "parent: ".$parentLeft." - ".$parentRight;
                }
                else
                {
                    echo "failed, missing parent<br>";
                    return;
                }
            }
            else
            {
                $query = "SELECT max(rgt)
                          FROM invNestedMarketGroups";
                $db->query($query);
                
                $line = mysql_fetch_assoc($db->result);
                if ($line["max(rgt)"])
                    $parentRight = $line["max(rgt)"] + 1;
                else
                    $parentRight = 1;
                echo "No parent: ".$parentRight;
            }
            
            $query = "UPDATE invNestedMarketGroups
                      SET rgt = rgt + 2
                      WHERE rgt >= ".$parentRight;
            $db->query($query);
            
            $query = "UPDATE invNestedMarketGroups
                      SET lft = lft + 2
                      WHERE lft > ".$parentRight;
            $db->query($query);
            
            $query = "INSERT INTO invNestedMarketGroups
                      VALUES (NULL, ".$marketGroupID.", ".$parentRight.", ".($parentRight + 1).")";
            $db->query($query);
            echo " | inserted ".$parentRight." - ".($parentRight + 1)."<br>";
            
            return;
        }
    }
?>