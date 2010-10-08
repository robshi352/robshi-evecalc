<?php

class compCalc
{
    public $submitted;

    function __construct($db)
    {
        require_once("classes/dbLinkClass.php");
        $config = parse_ini_file("eveTools.ini", true);

        $this->db = new dbLink($config["db"]["host"], $config["db"]["user"], $config["db"]["password"], $config["db"]["database"]);
        
        $this->formCount = 3;

        //select the top level 'Ship Equipment' group
        $query = "SELECT COUNT(*)-1 AS level,
                         t1.lft AS lft,
                         t1.rgt AS rgt
                  FROM invNestedMarketGroups AS t1,
                       invNestedMarketGroups AS t2,
                       invMarketGroups AS t3
                  WHERE t1.lft BETWEEN t2.lft AND t2.rgt
                  AND t1.marketGroupID = t3.marketGroupID
                  AND t3.marketGroupName = 'Ship Equipment'
                  GROUP BY t1.lft";
        $this->db->query($query);
        
        while($line = mysql_fetch_assoc($this->db->result))
        {
            if ($line["level"] == 0)
            {
                $leftID = $line["lft"];
                $rightID = $line["rgt"];
            }
        }
        
        $query = "SELECT t1.typeID AS typeID, t1.typeName AS typeName
                  FROM invTypes AS t1,
                       invNestedMarketGroups AS t2,
                       invMetaTypes AS t3
                  WHERE t2.lft BETWEEN ".$leftID." AND ".$rightID."
                  AND t1.marketGroupID = t2.marketGroupID
                  AND t1.typeID = t3.typeID
                  AND published = 1
                  AND t3.metaGroupID = 2
                  ORDER BY t1.typeName";
        $this->db->query($query);
        
        //echo "<pre>".$query."</pre>";
        $this->t2Items[0] = "select one";
        while($line = mysql_fetch_assoc($this->db->result))
        {
            $this->t2Items[$line["typeID"]] = $line["typeName"];
        }
    }
    
    function getFormValues()
    {
        $this->submitted = $_POST["submit"];
        $this->added = $_POST["add"];
        $this->resetted = $_POST["reset"];
        
        if ($this->resetted)
        {
            $this->formCount = 3;
            return;
        }
        
        if ($this->added || $this->submitted)
            $this->formCount = max($_POST["formCount"] + $_POST["formCountAdd"], 1);
        elseif(!$_POST["formCount"])
        {
            $this->formCount = 3;
        }

        for($i=0; $i<$this->formCount; $i++)
        {
            $this->buildItems[$i] = $_POST["buildItem".$i];
            $this->buildCount[$i] = $_POST["buildCount".$i];
        }
    }
    
    function displayForm()
    {
        global $siteFunctions;
        
        echo "<form method=POST action="."http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].">";
        echo "<div style=\"visibility:hidden;width:0px; height:0px;\"><input type=submit value=calculate name=submit></div>";
        for($i=0; $i<$this->formCount; $i++)
        {
            echo "<select name=buildItem".$i.">";
            foreach($this->t2Items as $typeID => $typeName)
            {
                if($typeID == $this->buildItems[$i])
                    echo "<option value=".$typeID." selected>".$typeName."</option>";
                else
                    echo "<option value=".$typeID.">".$typeName."</option>";
            }
            echo "</select>\n";
            echo "<input type=text size=4 name=buildCount".$i." value=".$this->buildCount[$i]."><br>";
        }
        echo "<input type=text size=3 name=formCountAdd>";
        echo "<input type=hidden name=formCount value=".$this->formCount.">";
        
        echo "<input type=submit name=add value='Add more Rows'><br>";
        echo "<input type=submit value=calculate name=submit>";
        echo "<input type=submit name=reset value=reset>";
        echo "</form>";
    }
    
    function evaluateForm()
    {
        $me = -4;
        
        for($i=0; $i<$this->formCount; $i++)
        {
            if (($this->buildItems[$i] != 0) && ($this->buildCount[$i] > 0))
            {
                unset($tempBuildID);
                
                $query = "SELECT blueprintTypeID, wasteFactor
                          FROM invBlueprintTypes
                          WHERE productTypeID = ".$this->buildItems[$i];
                $this->db->query($query);
                
                $line = mysql_fetch_assoc($this->db->result);
                $blueprintTypeID = $line["blueprintTypeID"];
                $wasteFactor = $line["wasteFactor"] / 100;
                
                //normal build requirements
                $query = "SELECT t1.materialTypeID AS materialID,
                                 t2.typeName AS materialName,
                                 t1.quantity AS quantity
                          FROM invTypeMaterials AS t1,
                               invTypes AS t2
                          WHERE t1.typeID = ".$this->buildItems[$i]."
                          AND t1.materialTypeID = t2.typeID";
                $this->db->query($query);
                
                //echo "<pre>".$query."</pre>";
                while($line = mysql_fetch_assoc($this->db->result))
                {
                    //echo "1: ".$line["materialName"]." (".$line["materialID"].") - ".$line["quantity"]."<br>";
                    $tempBuildID[$line["materialID"]] += $line["quantity"];
                    $this->buildName[$line["materialID"]] = $line["materialName"];
                }
                
                //recycled stuff, basically have to subtract t1 build requirements for some t2 mods
                $query = "SELECT materialTypeID AS materialID,
                                 t3.quantity * damagePerJob AS quantity
                          FROM ramTypeRequirements AS t1,
                               invBlueprintTypes AS t2,
                               invTypeMaterials AS t3
                          WHERE t1.typeID = t2.blueprintTypeID
                          AND t3.typeID = requiredTypeID
                          AND t2.productTypeID = ".$this->buildItems[$i]."
                          AND activityID = 1
                          AND damagePerJob > 0
                          AND recycle = 1";
                $this->db->query($query);
                //echo "<pre>".$query."</pre>";
                
                if (mysql_num_rows($this->db->result) > 0)
                {
                    while($line = mysql_fetch_assoc($this->db->result))
                    {
                        $tempBuildID[$line["materialID"]] -= $line["quantity"];
                    }
                }
                //remove negative values from the subtraction and add ME effect
                foreach($tempBuildID as $materialID => $quantity)
                {
                    $quantity = ($quantity + round($quantity * $wasteFactor * (1 - $me))) * $this->buildCount[$i];
                    $buildID[$materialID] += max($quantity, 0);
                }
                
                //extra build components
                $query = "SELECT requiredTypeID AS materialID,
                                 t2.typeName AS materialName,
                                 quantity * damagePerJob AS quantity
                          FROM ramTypeRequirements AS t1,
                               invTypes AS t2,
                               invBlueprintTypes AS t3
                          WHERE t1.requiredTypeID = t2.typeID
                          AND t1.typeID = t3.blueprintTypeID
                          AND t3.productTypeID = ".$this->buildItems[$i]."
                          AND activityID = 1
                          AND damagePerJob > 0";
                $this->db->query($query);
                //echo "<pre>".$query."</pre>";

                while($line = mysql_fetch_assoc($this->db->result))
                {
                    $buildID[$line["materialID"]] += $line["quantity"] * $this->buildCount[$i];
                    $this->buildName[$line["materialID"]] = $line["materialName"];
                }                
            } // end if
        } // end for
        
        //structure into sections: minerals, trade goods, t2 components
        //and sort stuff by name for output
        
        asort($this->buildName);

        foreach($this->buildName as $materialID => $materialName)
        {
            if ($buildID[$materialID] != 0)
            {
                $query = "SELECT t3.marketGroupName AS marketGroupName
                          FROM invTypes AS t1,
                               invMarketGroups AS t2,
                               invMarketGroups AS t3
                          WHERE typeID = ".$materialID."
                          AND t1.marketGroupID = t2.marketGroupID
                          AND t2.parentGroupID = t3.marketGroupID";
                $this->db->query($query);
                
                $line = mysql_fetch_assoc($this->db->result);
                $marketGroupName = trim($line["marketGroupName"]);
                
                switch($marketGroupName)
                {
                    case "Ore & Minerals": $this->minerals[$materialID] = $buildID[$materialID];
                            break;
                    case "Construction Components": $this->components[$materialID] = $buildID[$materialID];
                            break;
                    case "Trade Goods": $this->tradeGoods[$materialID] = $buildID[$materialID];
                            break;
                    case "Planetary Materials": $this->tradeGoods[$materialID] = $buildID[$materialID];
                            break;
                    case "Research & Invention": $this->ram[$materialID] = $buildID[$materialID];
                            break;
                    default: $this->t1Items[$materialID] = $buildID[$materialID];
                }
            }
        }
    } // end function
    
    function displayResult()
    {
        echo '<div style="float:right;">';
        echo "(Total: <em>";
        echo sizeof($this->minerals) + sizeof($this->components) + sizeof($this->tradeGoods) + sizeof($this->ram) + sizeof($this->t1Items);
        echo "</em>)";
        echo "<br>";
        echo "(Prints: <em>".(array_sum($this->buildCount) / 10)."</em>)";
        echo "</div>";
        
        echo "<u>Minerals</u> (".sizeof($this->minerals).") <br><br>";
        if ($this->minerals)
        {
            foreach($this->minerals as $materialID => $quantity)
            {
                if ($quantity > 0)
                    echo $quantity." x ".$this->buildName[$materialID]."<br>";
            }
        }
        else
            echo "None<br>";
        
        echo "<br><u>T2 Components</u> (".sizeof($this->components).") <br><br>";
        if ($this->components)
        {
            foreach($this->components as $materialID => $quantity)
            {
                if ($quantity > 0)
                    echo $quantity." x ".$this->buildName[$materialID]."<br>";
            }
        }
        else
            echo "None<br>";

        echo "<br><u>Trade Goods</u> (".sizeof($this->tradeGoods).") <br><br>";
        if ($this->tradeGoods)
        {
            foreach($this->tradeGoods as $materialID => $quantity)
            {
                if ($quantity > 0)
                    echo $quantity." x ".$this->buildName[$materialID]."<br>";
            }
        }
        else
            echo "None<br>";

        echo "<br><u>R.A.M.</u> (".sizeof($this->ram).") <br><br>";
        if ($this->ram)
        {
            foreach($this->ram as $materialID => $quantity)
            {
                if ($quantity > 0)
                    echo $quantity." x ".$this->buildName[$materialID]."<br>";
            }
        }
        else
            echo "None<br>";

        echo "<br><u>T1 Items</u> (".sizeof($this->t1Items).") <br><br>";
        if ($this->t1Items)
        {
            foreach($this->t1Items as $materialID => $quantity)
            {
                if ($quantity > 0)
                    echo $quantity." x ".$this->buildName[$materialID]."<br>";
            }
        }
        else
            echo "None<br>";

    }
    
}

?>