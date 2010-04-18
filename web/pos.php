<?php
    echo "<form action=".$PHP_SELF."?func=".$function." method=POST>\n";
?>

<select name=tower>
    <?php
        $query = "SELECT controlTowerTypeID, typeName, capacity
                  FROM invtypes AS t1, invcontroltowerresources AS t2
                  WHERE t1.typeID = t2.controlTowerTypeID
                  GROUP BY controlTowerTypeID
                  ORDER BY typeName";
        $result = mysql_query($query) or die("Anfrage fehlgeschlagen: " . mysql_error());
        while($line = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            echo "<option value=".$line["controlTowerTypeID"]." ";
            if ($line["controlTowerTypeID"] == $_POST["tower"])
            {
                echo "selected";
                $size = $line["capacity"];
            }
            echo ">".$line["typeName"]."</option>\n";
        }
    ?>
</select>

<input type=submit value=submit name=submit>


<?php
    if($_POST["submit"])
    {
        echo "<input type=hidden name=tower value=".$_POST["tower"].">\n";
        echo "<input type=hidden name=submit value=submit>\n";
        
        $query = "SELECT resourceTypeID, typeID, typeName, quantity, volume
                  FROM invtypes AS t1, invcontroltowerresources AS t2
                  WHERE t1.typeID = t2.resourceTypeID
                  AND t2.controlTowerTypeID = ".$_POST["tower"]."
                  ORDER BY typeName";
         $result = mysql_query($query) or die("Anfrage fehlgeschlagen: " . mysql_error());
?>

<table border=1>
    <tr>
        <th>Type</th>
        <th>Size</th>
        <th>Use/h</th>
        <th>Current</th>
        <?php
            if($_POST["calculate"])
            {
                echo "        <th>to Fill</th>";
            }
        ?>
        <th>max</th>
    </tr>
    <?php
        $sum = 0;
        while($line = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            if($_POST["calculate"])
            {
                $package = floor($size / $_POST["sum"]);
            }
            
            if (($line["typeName"] != "Amarr Empire Starbase Charter") and
                ($line["typeName"] != "Ammatar Mandate Starbase Charter") and
                ($line["typeName"] != "Gallente Federation Starbase Charter") and
                ($line["typeName"] != "Khanid Kingdom Starbase Charter") and
                ($line["typeName"] != "Minmatar Republic Starbase Charter") and
                ($line["typeName"] != "Strontium Clathrates"))
            {
                echo "<tr>";
                echo "  <td>".$line["typeName"]."</td>";
                echo "  <td>".$line["volume"]."</td>";
                if (($line["typeName"] == "Heavy Water") or ($line["typeName"] == "Liquid Ozone"))
                {
                    echo "  <td><input size=6 name=".$line["typeID"]."_use value=".$_POST[$line["typeID"]."_use"]."></td>\n";
                    $quantity = $_POST[$line["typeID"]."_use"];
                }
                else
                {
                    echo "  <td>".$line["quantity"]."</td>\n";
                    $quantity = $line["quantity"];
                }
                $sum += $quantity * $line["volume"];
                echo "  <td><input size=6 name=".$line["typeID"]."_curr value=".$_POST[$line["typeID"]."_curr"]."></td>\n";
                
                if ($_POST["calculate"])
                {
                    echo "  <td>".(($quantity * $package) - $_POST[$line["typeID"]."_curr"])."</td>\n";
                }
                echo "  <td>".($package * $quantity)."</td>\n";
                echo "</tr>";
            }
        }
        echo "<input type=hidden name=sum value=".$sum.">\n";
    echo "<tr>\n";
    echo "  <td>Sum</td>\n";
    echo "  <td>".$_POST["sum"]."</td>\n";
    echo "  <td>-</td><td>-</td>\n";
    if($_POST["calculate"])
    {
        echo "<td>-</td>";
    }
    echo "  <td>".$package."</td>\n";
    echo "</tr>";
    ?>
</table>
<input type=submit value=calculate name=calculate>
</form>

<?php
    }
?>