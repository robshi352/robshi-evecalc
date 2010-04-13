<?php
    $functions = array("about" => "about", "ice" => "Ice Calculation", "pos" => "POS Refill Calculation");
    $function = $_GET["func"];
    
    $dbLink = mysql_connect("localhost", "root", "")
        or die("Keine Verbindung möglich: " . mysql_error());
    mysql_select_db("eve_online")
        or die("Auswahl der Datenbank fehlgeschlagen");
?>

<html>
    <head>
        <title>Misc. Eve Tools</title>
    </head>
    <body>
    <!-- MENU -->
        <?php
            $i = 0;
            foreach($functions as $key => $value)
            {
                echo "<a href=".$PHP_SELF."?func=".$key.">".$value."</a>";
                $i++;
                if ($i < sizeof($functions))
                {
                    echo " | ";
                }
            }
            echo "<br><br>";
        ?>
        
    <!--ABOUT-->
        <?php
            if ($function == "about")
            {
        ?>
            Developing Eve Tools since 2010.
        <?php
            }
        ?>
    
    <!--Function File-->
        <?php
            if ($function == "ice")
            {
                include("ice.php");
            }
           if ($function == "pos")
            {
                include("pos.php");
            }
        ?>
    
    </body>
</html>


<?php
    //FREE MYSQL CONNECTION
    mysql_close($dbLink);
?>