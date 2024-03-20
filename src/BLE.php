<?php
// collection of functions
require_once("info.php");
require_once("doValence.php");

function shareWithOrgUnit($orgUnitId) {
    global $config;
    $data = array(
        "SharingOrgUnitId" => $orgUnitId,
        "ShareWithOrgUnit" => true,
        "ShareWithDescendants" => false
    );
    $response = doValenceRequest('POST', '/d2l/api/le/'.$config['LE_Version'].'/lti/tp/'.$orgUnitId.'/29/sharing/', $data);
    echo json_encode($response['response']);
}

?>