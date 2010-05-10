<?php

class trackerMenu
{
    public $currentMode;
    public $currentStyle;
    
    function trackerMenu($modes, $styles)
    {
        $this->styles = $styles;
        $this->modes = $modes;
    }
    
    function display()
    {
        $i = 2;
        foreach($this->modes as $modeName => $displayName)
        {
            if ($modeName != "default")
            {
                if ($modeName != $this->currentMode)
                    echo "<a href=".$PHP_SELF."?mode=".$modeName."&style=".$this->currentStyle.">".$displayName."</a>";
                else
                    echo "<b>".$displayName."</b>";
    
                if ($i < sizeof($this->modes))
                    echo " | ";
            }
              $i++;
        }
        echo "<br>";
        $i = 2;
        foreach($this->styles as $styleName => $displayName)
        {
            if ($styleName != "default")
            {
                if ($styleName != $this->currentStyle)
                    echo "<a href=".$PHP_SELF."?mode=".$this->currentMode."&style=".$styleName.">".$displayName."</a>";
                else
                    echo "<b>".$displayName."</b>";
    
                if ($i < sizeof($this->styles))
                    echo " | ";
            }
            $i++;
        }
    }
    
    function getCurrent()
    {
        if (in_array($_GET["mode"], array_keys($this->modes)))
            $this->currentMode = $_GET["mode"];
        else
            $this->currentMode = $this->modes["default"];

        if (in_array($_GET["style"], array_keys($this->styles)))
            $this->currentStyle = $_GET["style"];
        else
            $this->currentStyle = $this->styles["default"];
    }
}

?>