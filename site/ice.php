<?php
    $iceGroup = 465;
    $iconSize = "32_32";
    $query = "SELECT typeID, typeName, icon
              FROM invtypes AS t1, evegraphics AS t2
              WHERE t1.graphicID = t2.graphicID
              AND groupID = ".$iceGroup."
              AND typeName not like '%Compressed%'
              ORDER BY typeName";
    $result = mysql_query($query) or die("Anfrage fehlgeschlagen: " . mysql_error());
    echo $_POST["calculate"];
    echo "<form action=".$PHP_SELF."?func=".$function." method=POST>";
    
?>

<table border=1>
    <tr>
        <th>Ice Type</th>
        <th>Quantity</th>
    </tr>
    <?php
        while($line = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            echo "<tr>";
            echo "  <td><img src='images/icons/".$iconSize."/icon".$line[icon].".png' border=0>".$line["typeName"]."</td>";
            echo "  <td><input name=".$line["typeID"]." size=6></td>";
            echo "</tr>";
        }
    ?>
</table>
<input type=submit name=submit value=calculate>
</form>