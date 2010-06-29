<?php

    class siteFunctions
    {
        public $currentFunction;
        
        function __construct($functions)
        {
            $this->functions = $functions;
        }
        
        function display()
        {
            $i = 1;
            foreach($this->functions as $funcName => $funcValue)
            {
                if ($funcName == $this->currentFunction)
                    echo "<b>".$funcValue[0]."</b>";
                else
                    echo "<a href=".$_SERVER['PHP_SELF']."?func=".$funcName.">".$funcValue[0]."</a>";
                if ($i < sizeof($this->functions))
                {
                    echo " | ";
                }
                echo "\n";
                $i++;
            }
        }
        
        function getFunctionFile()
        {
            return $this->functions[$this->currentFunction][1];
        }
        
        function current()
        {
            $this->currentFunction = $_GET["func"];
        }
    }
?>