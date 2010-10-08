<?php

require_once("classes/hubCheckClass.php");
require_once("classes/hubDisplayerClass.php");

$hub = new hubCheck();
$hubDisplayer = new hubDisplayer();

echo '<html>
        <header>
            <title>Hub Check</title>
            <link rel="stylesheet" href="css/hubCheck.css">
        </header>
        
        <body>';
echo '<div id="content">';

$hubDisplayer->displayHubForm($hub->getItemList());

if ($hub->formSubmitted())
    $hub->getHubData();

echo "</div>
      </body>
      </html>";

?>