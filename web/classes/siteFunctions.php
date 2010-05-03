<?php

    class siteFunctions
    {
        function __construct($functions)
        {
            $this->functions = $functions;
        }
        
        function printFunctions()
        {
            $i = 1;
            foreach($this->functions as $key => $value)
            {
                echo "<a href=".$_SERVER['PHP_SELF']."?func=".$key.">".$value[0]."</a>";
                if ($i < sizeof($this->functions))
                {
                    echo " | ";
                }
                echo "\n";
                $i++;
            }
        }
        
        function getFile($currentFunction)
        {
            return $this->functions[$currentFunction][1];
        }
    }
?>