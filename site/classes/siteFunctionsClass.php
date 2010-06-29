<?php

    class siteFunctions
    {
        public $currentFunction;
        
        function __construct($functions)
        {
            $this->functions = $functions;
            $this->currentFunction = $_GET["func"];
        }
        
        function display()
        {
            $i = 1;
            foreach($this->functions as $funcShort => $funcExtended)
            {
                if ($funcExtended["visible"])
                {
                    if ($funcShort == $this->currentFunction)
                        echo "<b>".$funcExtended["description"]."</b>";
                    else
                        echo "<a href=".$PHP_SELF."?func=".$funcShort.">".$funcExtended["description"]."</a>";
                    if ($i < sizeof($this->functions))
                        echo " | ";
                    echo "\n";
                }
                $i++;
            }
        }
        
        function getFunctionFile()
        {
            return $this->functions[$this->currentFunction]["file"];
        }
    }
?>