<?php

    require_once("classes/dbLinkClass.php");
    require_once("classes/marketGroupConverterClass.php");

    $config = parse_ini_file("eveTools.ini", true);
    $db = new dbLink($config["db"]["host"], $config["db"]["user"], $config["db"]["password"], $config["db"]["database"]);
    
    $converter = new marketGroupConverter();
    
    $converter->createTable();
    $converter->setMarketGroups();
    $converter->convert();
    echo "done<br><br>";

    $query = "  SELECT t3.marketGroupName AS Name,
                       COUNT(*)-1 AS level,
                       ROUND((t1.rgt - t1.lft - 1) / 2) AS offspring
                FROM invNestedMarketGroups AS t1, invNestedMarketGroups AS t2, invMarketGroups AS t3
                WHERE t1.lft BETWEEN t2.lft AND t2.rgt
                AND t1.marketGroupID = t3.marketGroupID
                GROUP BY t1.lft
                ORDER BY t1.lft";
    $db->query($query);
    
    while($line = mysql_fetch_assoc($db->result))
    {
        //if (($line["offspring"] > 0) && ($line["level"] > 0))
        //    echo "+";

        for($i=0;$i<$line["level"]; $i++)
        {
            echo "---";
        }
        echo $line["Name"];
        echo "<br>";
    }

?>