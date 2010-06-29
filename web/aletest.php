<?php

//details of the char with the rights to grab corp jobs
$userID = 2236980;
$apiKey = "4eUnTgqh5lwcXW9nGhUSQ2aUDhUrYJlY9bqGrcNGl2VFCUtEUrYbviFxmcHml9uv";
$charName = "Nicole Maher";

require_once("ale/factory.php");
//require_once("classes/dbLink.php");

//$db = new dbLink("localhost", "root", "", "eve_online");

try
{   
    $ale = AleFactory::getEVEOnline();
    //set user credentials, third parameter $characterID is also possible;
    $ale->setCredentials($userID, $apiKey);
    //all errors are handled by exceptions
    //let's fetch characters first.
    $account = $ale->account->Characters();
    //you can traverse rowset element with attribute name="characters" as array
    foreach ($account->result->characters as $character)
    {
        //this is how you can get attributes of element
        $characterID = (string) $character->characterID;
        //set characterID for CharacterSheet
        $ale->setCharacterID($characterID);
        //$characterSheet = $ale->char->CharacterSheet();
        if($character->name == $charName)
        {
            break;
        }
    }
    
    $assets = $ale->corp->assetList();
    
    foreach($assets->result->assets as $asset)
    {
        echo "<u>".$asset->locationID."</u><br>";
        if ($asset->contents)
            foreach($asset->contents as $content)
            {
                //echo "<pre>";
                echo $content->typeID.": ".$content->quantity."<br>";
                //echo "</pre><br>";
            }
    }
    
}
catch (Exception $e)
{
    echo $e->getMessage();
}

?>            