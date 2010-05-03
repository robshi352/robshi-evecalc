<?php

    class dbLink
    {
        function __construct($host, $user, $pass, $table)
        {
            $this->link = mysql_connect($host, $user, $pass)
                          or die("No connection possible: " . mysql_error());
            mysql_select_db($table)
            or die("Could not select database");
        }
        
        function query($query)
        {
            $this->result = mysql_query($query)
                            or die("<br>".mysql_error()."<br><i>".$query."</i><br>\n");
        }
        
        function __destruct()
        {
            mysql_close($this->link);
        }
    }
?>