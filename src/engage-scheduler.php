<?php
// collection of functions
require_once("info.php");
require_once("doValence.php");
require_once("engage.php");
require_once("BLE.php");

// Check if the script is run from the command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

$sharingOrgUnitIds = getSharedOrgUnitIds($toolProviderId);

foreach($sharingOrgUnitIds as $orgUnitId){
    syncEngageBLE($orgUnitId);
}

?>