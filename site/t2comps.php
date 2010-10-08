<?php

require_once("classes/compCalcClass.php");

$compCalc = new compCalc($db);
$compCalc->getFormValues();

echo '<div id="compForm">';
$compCalc->displayForm();
echo "</div>";

if ($compCalc->submitted)
{
    echo '<div id="compResult">';
    $compCalc->evaluateForm();
    $compCalc->displayResult();
    echo "</div>";
}

?>