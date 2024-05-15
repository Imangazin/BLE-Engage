<?php
// collection of functions
require_once("info.php");
require_once("doValence.php");
require_once("engage.php");

// Check if the script is run from the command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

$sharingOrgUnitIds = getSharedOrgUnitIds(29);

foreach($sharingOrgUnitIds as $orgUnitId){
    syncEngageBLE($orgUnitId);
}

?>