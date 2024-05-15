<?php
// collection of functions
require_once("info.php");
require_once("doValence.php");
require_once("engage.php");

function shareWithOrgUnit($orgUnitId) {
    global $config;
    $data = array(
        "SharingOrgUnitId" => $orgUnitId,
        "ShareWithOrgUnit" => true,
        "ShareWithDescendants" => false
    );
    $response = doValenceRequest('POST', '/d2l/api/le/'.$config['LE_Version'].'/lti/tp/6606/29/sharing/', $data);
}

//creates a new section in BLD (links engage event with an offering)
function createSection($orgUnitId, $eventId, $gradeId){
    global $config;
    $engageEvent = getEventById($eventId);
    $eventName = $engageEvent->name;
    $eventDate = dateToString($engageEvent->startsOn);
    $data = array(
        "Name"=> $eventName." (".$eventDate.")",
        "Code"=> "engage-".$eventId."-".$gradeId,
        "Description"=> array ("Content"=>"","Type"=>"Html")
    );
    $response = doValenceRequest('POST', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/', $data); 
    return $response['response']->SectionId;
}

//deletes BLE section (unlinks engave event from the offering)
function deleteSection($orgUnitId, $sectionId){
    global $config;
    $response = doValenceRequest('DELETE', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/'.$sectionId); 
}

//returns if a section exist in BLE (engage event is already linked if exist)
function isSectionExist($orgUnitId, $eventId){
    global $config;
    $response = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/');

    foreach ($response['response'] as $section) {
        // Check if search string exists in SectionCode
        if (strpos($section->Code, $eventId)!==false) {
            return true;
        }
    }
    return false;
}

//enrolls engage RSVP users into the offering and to specific section dedicated to engage event
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

// returns a row of table with BLE sections informations and delet action button. 
function printLinkedEvents($orgUnitId){
    global $config;
    $tablerows='';
    $response = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/');
    $sections = array_reverse($response['response']);
    foreach ($sections as $section) {
        if (strpos($section->Code, 'engage') !== false) {
            $sectionId = $section->SectionId;
            $sectionCode = explode('-', $section->Code);
            $event = getEventById($sectionCode[1]);
            $gradeId = $sectionCode[2];
            $gradeObject = getGradeItemById($orgUnitId, $gradeId);
            $tablerows .= "<tr>
                            <td style='display:none;'>".$sectionId."</td>
                            <td style='display:none;'>".$sectionCode[1]."</td>
                            <td>".$event->name."</td>
                            <td>".dateToString($event->startsOn)."</td>
                            <td style='display:none;'>".$gradeId."</td>
                            <td>".$gradeObject->Name."</td>
                            <td>
                                <button type='button' class='btn btn-secondary btn-sm' onclick='updateEventById(this)'>Update</button>
                                <button type='button' class='btn btn-red btn-sm deleteButton' data-bs-toggle='modal' data-bs-target='#deleteConfirmModal' onclick='setSessionId(this)'>Delete</button>
                            </td>
                            </tr>";
        }
    }
    return $tablerows;
}

//converts given UTC time to EDT and  returns the formatted string.
function dateToString($date){
    date_default_timezone_set('America/New_York');
    $dateTime = new DateTime($date);
    $dateTime->setTimezone(new DateTimeZone('America/New_York'));
    $formattedDateTime = $dateTime->format('Y-m-d H:i A');
    return (string) $formattedDateTime;
}


//returns all the grade items  for a given orgUnitId
function getGradeItems($orgUnitId){
    global $config;
    $response = doValenceRequest('GET', '/d2l/api/le/'.$config['LE_Version'].'/'.$orgUnitId.'/grades/');
    $result = array();
    foreach ($response['response'] as $each){
        $orgs = experienceBUcall('/v3.0/organizations/organization/?ids=' . $each->organizationId);  
        $result[] = array(
            "id"   => $each->Id,
            "name" => $each->Name
        );
    }
    return  $result;
}

function getGradeItemById($orgUnitId, $gradeId){
    global $config;
    $response = doValenceRequest('GET', '/d2l/api/le/'.$config['LE_Version'].'/'.$orgUnitId.'/grades/'.$gradeId);
    return $response['response'];
}

// grades user with value 1 for numeric type item
function gradeEventAttendence($orgUnitId, $eventId, $gradeId){
    global $config;
    $data = array(
        "Comments"=> array ("Content"=>"","Type"=>"Html"),
        "PrivateComments"=> array ("Content"=>"","Type"=>"Html")
    );

    $gradeInfo = doValenceRequest('GET', '/d2l/api/le/'.$config['LE_Version'].'/'.$orgUnitId.'/grades/'.$gradeId);
    $gradeType = $gradeInfo['response']->GradeType;

    switch ($gradeType) {
        case 'Numeric':
            $data['GradeObjectType'] = 1;
            $data['PointsNumerator'] = $gradeInfo['response']->MaxPoints;
            break;
        case 'PassFail':
            $data['GradeObjectType'] = 2;
            $data['Pass'] = true;
            break;
        default:
            //break out from the function
            return;
    }

    $eventAttendees = getEventAttendees($eventId);
    foreach($eventAttendees as $userName){
        $user = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/users/?externalEmail='.$userName.'@localhost.local');
        if($user['Code']==200){
            doValenceRequest('PUT', '/d2l/api/le/'.$config['LE_Version'].'/'.$orgUnitId.'/grades/'.$gradeId.'/values/'.$user['response'][0]->UserId, $data);
        }
    }
}


// returns linked event infos 
function getLinkedEvents($orgUnitId){
    global $config;
    $linkedEvents = array();
    $response = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/');
    $sections = array_reverse($response['response']);
    foreach ($sections as $section) {
        if (strpos($section->Code, 'engage') !== false) {
            $event = array();
            $sectionCode = explode('-', $section->Code);
            $engageEvent = getEventById($sectionCode[1]);
        
            $event['sectionId'] = $section->SectionId;
            $event['eventId'] = $sectionCode[1];
            $event['eventName'] = $engageEvent->name;
            $event['startDate'] = $engageEvent->startsOn;
            $event['endDate'] = $engageEvent->endsOn;
            $event['gradeId'] = $sectionCode[2];
            $linkedEvents[] = $event;
        }
    }
    return $linkedEvents;
}

//Returns orgUnitId type 3 that usign the tool provider
function getSharedOrgUnitIds($ltiToolProviderId){
    global $config;
    $sharedOrgUnitIds = array();
    $response =  doValenceRequest('GET', '/d2l/api/le/'.$config['LE_Version'].'/lti/tp/6606/'.$ltiToolProviderId.'/sharing/');
    foreach($response['response'] as $each){
        if ($each->SharingOrgUnitId!=6606){
            $sharedOrgUnitIds[] = $each->SharingOrgUnitId;
        }
    }
    return $sharedOrgUnitIds;
}

// syncs engage RSVP and attendance with BLE for linked events. Skippes events which ended 30 days ago.
function syncEngageBLE($orgUnitId){
    global $config;
    $linkedEvents = getLinkedEvents($orgUnitId);
    foreach($linkedEvents as $event){
        if (isDate30DaysOrMoreInPast($event['endDate'])){
            $eventRsvps = getEventUsers($event['eventId']);
            enrollEngageEventUsers($orgUnitId, $event['sectionId'], $eventRsvps);
            if (!empty($event['gradeId'])){
                gradeEventAttendence($orgUnitId, $event['eventId'], $event['gradeId']);
            }
        }  
    }
}

?>