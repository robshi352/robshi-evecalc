<?php

class hubDisplayer
{
    function __construct()
    {
        
    }
    
    function displayHubForm($itemList)
    {
        echo '<div id="hubForm">';
        echo "<em>Select items to sell</em><br>";
        
        printf('<form method="POST" action="http://%s">', $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"])
        ;
        echo '<div style="visibility:hidden;width:0px; height:0px;"><input type="submit" value="calculate" name="submit"></div>';
                    
        for($i=0; $i < $itemList["formCount"]; $i++)
        {
            printf('<select name="hubItem%s">', $i);
            
            echo '<option value="0">select one</option>';
            //echo '<option value="0"></option>';
            
            foreach($itemList["items"] as $typeID => $typeName)
            {
                if($typeID == $itemList["selected"][$i])
                    printf('<option value="%s" selected>%s</option>', $typeID, $typeName);
                else
                    printf('<option value="%s">%s</option>', $typeID, $typeName);
            }
            echo "</select><br>";
            //echo "<input type=text size=4 name=buildCount".$i." value=".$this->buildCount[$i]."><br>";
        }
        echo '<input type="text" size="3" name="formCountAdd">';
        printf('<input type="hidden" name="formCount" value="%s">', $itemList["formCount"]);
        
        echo '<input type="submit" name="add" value="Add more Rows"><br>';
        echo '<input type="submit" value="check" name="submit">';
        echo '<input type="submit" name="reset" value="reset">';
        echo "</form>";
        
        echo "</div>";
    }
}

?>