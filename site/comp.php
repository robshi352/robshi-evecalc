<?php

if(array_key_exists('HTTP_EVE_TRUSTED', $_SERVER))
    define('EVE_IGB', true);
else
    define('EVE_IGB', false);

require_once("classes/compCalcClass.php");

//echo '<a href="javascript:CCPEVE.showInfo(203)">linky</a>';


$IGB = strpos($_SERVER["HTTP_USER_AGENT"], "EVE-IGB");

$compCalc = new compCalc($IGB);
$compCalc->getFormValues();

echo "<div id=compForm>";
$compCalc->displayForm();
echo "</div>";

if ($compCalc->submitted)
{
    echo "<div id=compResult>";
    $compCalc->evaluateForm();
    $compCalc->displayResult();
    echo "</div>";
}

?>