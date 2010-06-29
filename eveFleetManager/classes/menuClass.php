<?php
class menu
{

    function __construct($functions, $fleetInProgress, $currentRole)
    {
        foreach($functions as $funcShort => $funcExtended)
        {
            $this->functions[$funcShort] = $funcExtended["description"];
            $this->funcVisibility[$funcShort] = $funcExtended["inProgress"];
            $this->security[$funcShort] = $funcExtended["security"];
        }
        $this->currentRole = $currentRole;
        $this->fleetInProgress = $fleetInProgress;
        
        //retrieve current function
        if (in_array($_GET["func"], array_keys($this->functions)) &&
            ($this->funcVisibility[$_GET["func"]] == $fleetInProgress) &&
            ($this->security[$_GET["func"]] <= $currentRole))
            $this->currentFunction = $_GET["func"];
    }
    
    function display()
    {
        echo "<div id=\"menu\">";
        if ($this->fleetInProgress)
            $active = "_active";

        $imageName = $this->getRandomImage();
        
        echo "<div id=\"shipImage\">";
        echo sprintf("<img src=\"%s\" title=\"%s\">", "images/ships/".$imageName.$active.".png", "EFM. Your Fleet Management Tool");
        echo "</div>";
        
        echo "<div id=\"logoMenuContainer\">";

        echo "<div id=\"logo\">";
        echo sprintf("<img src=\"%s\" title=\"%s\">", "images/efm.png", "EFM. Your Fleet Management Tool");
        echo "</div>";
        
        echo "<div id=\"textMenu\">";
        $firstRun = true;
        
        foreach($this->functions as $funcShort => $funcName)
        {
            if (($this->fleetInProgress == $this->funcVisibility[$funcShort]) && ($this->currentRole >= $this->security[$funcShort]))
            {
                if (!$firstRun)
                    echo " | ";
                if ($funcShort == $this->currentFunction)
                    echo sprintf("<em>%s</em>", $funcName);
                else
                    echo "<a href=".$PHP_SELF."?func=".$funcShort.">".$funcName."</a>";
                $firstRun = false;
            }
        }
        echo "</div>"; // textMenu
        echo "</div>"; // logoMenuContainer
        echo "</div>"; // menu
    }
    
    function getRandomImage()
    {
        $handle = opendir("images/ships/");
        if ($handle)
        {
            $fileArray = array();
            while (false !== ($file = readdir($handle)))
            {
                $file = str_replace("_active", "", $file);
                $file = str_replace(".png", "", $file);
                
                if (!in_array($file, $fileArray) && ($file != ".") && ($file != ".."))
                    $fileArray[] = $file;
            }
            return $fileArray[rand(0, sizeof($fileArray) - 1)];
        }
    }
    
    function getCurrentFunction()
    {
        return $this->currentFunction;
    }
}
?>