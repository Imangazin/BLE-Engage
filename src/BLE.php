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
    return $response['response']->SectionId;
}

function isSectionExist($orgUnitId, $eventId){
    global $config;
    $response = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/');

    foreach ($response['response'] as $section) {
        // Check if search string exists in SectionId
        if ($section->Code == $eventId) {
            return true;
        }
    }
    return false;
}

function enrollEngageEventUsers($orgUnitId, $sectionId, $usersToEnroll) {
    global $config;
    foreach($usersToEnroll as $userName){
        $userId = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/users/?externalEmail='.$userName.'@localhost.local');
        if($userId['Code']==200){
            $parentData = array(
                "OrgUnitId"=> $orgUnitId,
                "UserId"=> $userId['response'][0]->UserId,
                "RoleId"=> 110
            );
            $sectionData = array(
                "UserId"=> $userId['response'][0]->UserId
            );
        }
        $enrollToParent = doValenceRequest('POST', '/d2l/api/lp/'.$config['LP_Version'].'/enrollments/', $parentData);
        $enrollToSection = doValenceRequest('POST', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/'.$sectionId.'/enrollments/', $sectionData); 

    }
}
?>