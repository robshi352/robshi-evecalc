<?php

//$info = $_GET["info"];
//
echo $_SERVER["HTTP_EVE_CHARID"];
//
//echo "<html>
//        <header>
//            <title>Test</title>
//        </header>
//        
//        <body>";
//
//require_once("ale/factory.php");
//
//$config = parse_ini_file("tracker.ini", true);
//
////setup modes and styles for the menu
//
//try
//{   
//    $ale = AleFactory::getEVEOnline();
//    //set user credentials, third parameter $characterID is also possible;
//    $ale->setCredentials($config["user"]["userID"], $config["user"]["apiKey"]);
//    //all errors are handled by exceptions
//    //let's fetch characters first.
//    $account = $ale->account->Characters();
//    //you can traverse rowset element with attribute name="characters" as array
//    foreach ($account->result->characters as $character)
//    {
//        //this is how you can get attributes of element
//        $characterID = (string) $character->characterID;
//        //set characterID for CharacterSheet
//        $ale->setCharacterID($characterID);
//        if($character->name == $config["user"]["charName"])
//        {
//            break;
//        }
//    }
//}
//catch (Exception $e)
//{
//    echo $e->getMessage();
//}
//
//try
//{
//    $AssetList = $ale->corp->AssetList();
//}
//catch (Exception $e)
//{
//    echo $e->getMessage();
//}
//
//
//echo "  </body>
//      </html>";

?>