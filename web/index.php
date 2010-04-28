<?php

    class siteFunctions
    {
        function siteFunctions($functions)
        {
            $this->functions = $functions;
        }
        
        function printFunctions()
        {
            $i = 0;
            foreach($this->functions as $key => $value)
            {
                echo "<a href=".$PHP_SELF."?func=".$key.">".$value[0]."</a>";
                $i++;
                if ($i < sizeof($this->functions))
                {
                    echo " | ";
                }
            }
        }
        
        function getFile($currentFunction)
        {
            return $this->functions[$currentFunction][1];
        }
    }
    
    class dbLink
    {
        function dbLink($host, $user, $pass, $table)
        {
            $this->link = mysql_connect($host, $user, $pass)
                          or die("Keine Verbindung möglich: " . mysql_error());
            mysql_select_db($table)
            or die("Auswahl der Datenbank fehlgeschlagen");
        }
        
        function query($query)
        {
            $this->result = mysql_query($query)
                            or die($query."\n".mysql_error());
        }
    }
    
    $functions = array("about" => array("about", "about.php"),
                       "ice" => array("Ice Calculation", "ice.php"),
                       "pos" => array("POS Refill Calculation", "pos.php"),
                       "station" => array("Station Locator", "station.php"),
                       "agent" => array("Agent Locator", "agent.php"));
    
    $db = new dbLink("localhost", "root", "", "eve_online");
    $siteFunctions = new siteFunctions($functions);
    $currentFunction = $_GET["func"];
?>

<html>
    <head>
        <title>Misc. Eve Tools</title>
    </head>
    <body>
    <!-- MENU -->
        <?php
            $siteFunctions->printFunctions();
            echo "<br><br>";
            if ($siteFunctions->getFile($currentFunction) != "")
            {
                include($siteFunctions->getFile($currentFunction));                
            }
        ?>
    
    </body>
</html>


<?php
    //FREE MYSQL CONNECTION
    mysql_close($db->link);
?>