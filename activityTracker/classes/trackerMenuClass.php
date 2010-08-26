<?php
/*Copyright 2010 @ masubious*/

class trackerMenu
{
    function trackerMenu($modes,  $latestEntry)
    {
        $this->modes = $modes;
        $this->currentLink = "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        $this->currentFile = "http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"];
        
        $this->currentMode = $this->modes["default"]["displayName"];
        
        if (in_array($_GET["mode"], array_keys($this->modes)))
            $this->currentMode = $_GET["mode"];
        
        if (($this->currentMode != $this->modes["default"]["displayName"]) &&
            ($this->modes[$this->currentMode]["displayParent"] == null) &&
            ($this->currentMode != "track"))
            $this->currentMode = $this->modes["default".$this->currentMode]["displayName"];
        
        //$this->showInfo = $_GET["info"];
        $this->latestEntry = $latestEntry;
    }
    
    function display()
    {
        echo '<div id="menu">';
        $firstrun = true;
        
        //display Main Menu
        foreach($this->modes as $modeName => $modeDetails)
        {
            if (($modeDetails["display"] == true) && ($modeDetails["displayParent"] == null))
            {
                if (!$firstrun)
                    echo " | ";
                $firstrun = false;
                if (($modeName != $this->currentMode) && ($modeName != $this->modes[$this->currentMode]["displayParent"]))
                    printf('<a href="%s?mode=%s">%s</a>', $this->currentFile,  $modeName, $modeDetails["displayName"]);
                else
                    printf('<em>%s</em>', $modeDetails["displayName"]);
            }
        }
        echo "<br>";
        
        $firstrun = true;
        
        //display Sub Menu
        foreach($this->modes as $modeName => $modeDetails)
        {
            if (($modeDetails["display"] == true) &&
                ($this->modes[$modeName]["displayParent"] != null) &&
                (($modeDetails["displayParent"] == $this->currentMode) ||
                 ($this->modes[$this->currentMode]["displayParent"] == $this->modes[$modeName]["displayParent"])))
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
        if (!$firstrun)
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
}

?>