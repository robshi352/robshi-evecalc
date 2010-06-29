<?php

    class dbLink
    {
        function __construct($host, $user, $pass, $database)
        {
            $this->link = mysql_connect($host, $user, $pass)
                or die("No connection possible: " . mysql_error());
            if ($this->link)
                mysql_select_db($database)
                    or die("Could not select database");
            else
                echo "Could not select database";
        }
        
        function query($query)
        {
            $this->result = mysql_query($query)
                            or die("<br>".mysql_error()."<br><pre><i>".$query."</i></pre><br>\n");
        }
        
        function __destruct()
        {
            mysql_close($this->link);
        }
    }
?>