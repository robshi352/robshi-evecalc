<?php

//date("o-m-d H:i:s", $endTime)

function updatePrices($priceList, $db)
{
    $now = time();
    
    foreach($priceList as $typeID => $status)
    {
        $query = "SELECT minSell, lastUpdate
                  FROM invPrices
                  WHERE typeID = ".$typeID;
        $db->query($query);
        
        if (mysql_num_rows($db->result) == 0)
        {
            $priceList[$typeID] = "insert";
            $check[] = $typeID;
        }
        else
        {
            $line2 = mysql_fetch_assoc($db->result);
            if (($now - strtotime($line2["lastUpdate"])) > 24 * 3600)
            {
                $priceList[$typeID] = "update";
                $check[] = $typeID;
            }
            else
                $priceList[$typeID] = $line2["minSell"];
        }
    }

    $i = 0;
    $checkString = "";
    $updateLimit = 25;
    
    foreach($check as $typeID)
    {
        if ($i < $updateLimit)
        {
            $checkString .= "typeid=".$typeID."&";
            $i++;
        }
        else
        {
            $xml = simplexml_load_file("http://api.eve-central.com/api/marketstat?".$checkString."regionlimit=10000002");
            
            foreach($xml->marketstat->type as $type)
            {
                if ($priceList[(int)$type["id"]] == "update")
                {
                    $query = "UPDATE invPrices
                              SET minSell = ".$type->sell->min."
                              AND avgSell = ".$type->sell->avg."
                              AND avgBuy = ".$type->buy->avg."
                              AND maxBuy = ".$type->buy->max."
                              AND lastUpdate = '".date("o-m-d H:i:s", $now)."'
                              WHERE typeID = ".$type["id"];
                    $db->query($query);
                    
                    $priceList[(int)$type["id"]] = $type->sell->min;
                }
                elseif($priceList[(int)$type["id"]] == "insert")
                {
                    $query = "INSERT INTO invPrices
                              VALUES(".$type["id"].",
                              ".$type->sell->min.",
                              ".$type->buy->max.",
                              ".$type->sell->avg.",
                              ".$type->buy->avg.",
                              '".date("o-m-d H:i:s", $now)."')";
                    $db->query($query);
                    
                    $priceList[(int)$type["id"]] = $type->sell->min;
                }
            }
            //exit;
            $i = 0;
            $checkString = "";
        }
    }
    return $priceList;
}

//get t1 build components from t1 stuff that is on the market

$query = "SELECT t1.typeName, t1.typeID, t2.materialTypeID, quantity
          FROM invTypes AS t1,
               invTypeMaterials AS t2,
               invBlueprintTypes AS t3,
               invTypes AS t4
          WHERE t1.typeID = t3.productTypeID
          AND t3.blueprintTypeID = t4.typeID
          AND t4.published = 1
          AND t3.techLevel = 1
          AND t1.typeID = t2.typeID
          AND t1.published = 1
          AND t1.marketGroupID IS NOT NULL
          AND t1.typeName NOT LIKE '%compressed%'
          ORDER BY t1.typeName";
$db->query($query);
$result = $db->result;

//accumulate prices and update price if necessary

while($line = mysql_fetch_assoc($result))
{
    if (!$priceList[$line["typeID"]])
        $priceList[$line["typeID"]] = "";
    if (!$priceList[$line["materialTypeID"]])
        $priceList[$line["materialTypeID"]] = "";
    
    $nameList[$line["typeID"]] = $line["typeName"];
    $buildList[$line["typeID"]][$line["materialTypeID"]] = $line["quantity"];
}

$priceList = updatePrices($priceList, $db);

foreach($buildList as $typeID => $details)
    foreach($details as $materialID => $quantity)
        $buildCost[$typeID] += $priceList[$materialID] * $quantity;

foreach($buildCost as $typeID => $price)
    $buildCost[$typeID] = $price - $priceList[$typeID];

arsort($buildCost);

echo "<table border=1>";
echo "<tr><th>Item Name</th><th>Profit per Piece</th><th>Profit per Hour</th></tr>";

foreach($buildCost as $typeID => $price)
{
    if ($price < 0)
        printf("<tr><td><b>%s</b></td><td><font color=red>%s ISK</font></td><td>&nbsp;</td></tr>", $nameList[$typeID], number_format($price));
    else
        printf("<tr><td><b>%s</b></td><td><font color=green>%s ISK</font></td><td>&nbsp;</td></tr>", $nameList[$typeID], number_format($price));
}

echo "</table>";
?>