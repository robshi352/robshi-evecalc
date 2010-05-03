<?php

//details of the char with the rights to grab corp jobs
$userID = 1838466;
$apiKey = "fwC1wPrrDqa13d0nd3Q2mE7akMN6RzyDZbXXMJJzinuzLJod4wlBlshM0oKUrFHT";
$charName = "Gar Karath";

require_once("ale/factory.php");
require_once("classes/dbLink.php");

$db = new dbLink("localhost", "root", "", "eve_online");
$db->query("SELECT activityID FROM ramactivities WHERE activityName = 'Invention'");
$inventionID = db->result

class pecoTracker
{
    function pecoTracker($userID, $apiKey, $charName)
    {
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
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    
    function trackInvention
    {
        $corpJobs = $ale->corp->IndustryJobs();
            //print_r($corpJobs);
            $count = 0;
            $countSuccessfully = 0;
            $countCompleted = 0;
            echo "<u>INVENTION</u><br><br>\n";
            foreach ($corpJobs->result->jobs as $job)
            {
                if ($job->activityID == INVENTION)
                {
                    $count++;
                    echo $job->jobID.": ".$job->beginProductionTime." - ".$job->endProductionTime."<br>\n";
                    if ($job->completed)
                    {
                        $countCompleted++;
                        if ($job->completedStatus)
                            $countSuccessfully++;
                    }
                    
                }
            }
            echo "Total: ".$count."<br>\n";
            echo "Total Completed: ".$countCompleted."<br>\n";
            echo "Total Successful: ".$countSuccessfully."<br>\n";
    }
}
//and finally, we should handle exceptions


    }
}

//get ALE object


?>            