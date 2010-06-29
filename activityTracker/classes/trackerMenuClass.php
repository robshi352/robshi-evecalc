<?php
/*Copyright 2010 @ masubious*/

class trackerMenu
{
    function trackerMenu($modes,  $latestEntry)
    {
        $this->styles = $styles;
        $this->modes = $modes;
        $this->currentLink = "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        $this->currentFile = "http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"];
        
        $this->currentMode = $this->modes["default"];

        
        if (in_array($_GET["mode"], array_keys($this->modes)))
            $this->currentMode = $_GET["mode"];
            
        $this->showInfo = $_GET["info"];
        $this->latestEntry = $latestEntry;
    }
    
    function display()
    {
        echo '<div id="menu">';
        $firstrun = true;
        
        foreach($this->modes as $modeName => $modeDetails)
        {
            if ($modeDetails["show"] == true)
            {
                if (!$firstrun)
                    echo " | ";
                $firstrun = false;
                if ($modeName != $this->currentMode)
                    printf('<a href="%s?mode=%s">%s</a>', $this->currentFile,  $modeName, $modeDetails["displayName"]);
                else
                    printf('<em>%s</em>', $modeDetails["displayName"]);
            }
        }
        echo "<br>";
        echo "<em>Latest Entry: </em>";
        //echo date("F jS, H:i", $this->latestEntry);
        printf("%s Hours ago", (int)((time() - $this->latestEntry) / 3600));
        echo "</div>";
    }
    
    function getCurrentMode()
    {
        return $this->currentMode;
    }
    
    function getCurrentStyle()
    {
        return $this->currentStyle;
    }
    
    function showInfo()
    {
        return ($this->showInfo == "true");
    }
}

?>