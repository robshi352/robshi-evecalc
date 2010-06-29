<?php

require_once("classes/assetSpyClass.php");
require_once("classes/dbLinkClass.php");

$config = parse_ini_file("assetSpy.ini", true);

$assetSpy = new assetSpy($config);

$assetSpy->getAPIData();
$assetSpy->displayLocations();

?>