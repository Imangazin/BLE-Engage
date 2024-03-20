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
    $response = doValenceRequest('POST', '/d2l/api/le/'.$config['LE_Version'].'/lti/tp/6606/29/sharing/', $data);
}

function createSection($orgUnitId, $eventId){
    global $config;
    $data = array(
        "Name"=> $eventId,
        "Code"=> $eventId,
        "Description"=> array ("Content"=>"","Type"=>"Html")
    );
    $response = doValenceRequest('POST', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/', $data); 
    echo $response['Code'];
}




?>