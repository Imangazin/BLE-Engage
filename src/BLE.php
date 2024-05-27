<?php
// collection of functions
require_once("info.php");
require_once("doValence.php");
require_once("engage.php");

//converts given UTC time to EDT and  returns the formatted string.
function dateToString($date){
    date_default_timezone_set('America/New_York');
    $dateTime = new DateTime($date);
    $dateTime->setTimezone(new DateTimeZone('America/New_York'));
    $formattedDateTime = $dateTime->format('Y-m-d H:i A');
    return (string) $formattedDateTime;
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
        "Description"=> array ("Content"=>"","Type"=>"Text")
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

//update section, adding update logs into section description
function updateSection($orgUnitId, $sectionId){
    global $config;
    date_default_timezone_set('America/New_York');
    // Get the current date and time
    $currentDateTime = date('Y-m-d H:i:s');
    $sectionInfo = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/'.$sectionId);
    $data = array(
        "Name"=> $sectionInfo['response']->Name,
        "Code"=> $sectionInfo['response']->Code,
        "Description"=> array ("Content"=>$currentDateTime,"Type"=>"Text")
    );
    doValenceRequest('PUT', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/'.$sectionId, $data);
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

//checks each user if it also enrolled in other sections
//if not deletes from the offering, otherwise do nothing
function unEnrollEngageUsers($orgUnitId, $sectionId){
    global $config;
    $allSections = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/');
    $sectionToDelete = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/'.$sectionId);
    
    // all section enrollments except $sectionId, not included instructor role
    // since instructor roles will never show up in $userToEnroll array, we should be good 
    $allEnrollments = array();
    foreach ($allSections['response'] as $section) {
        if ($section->SectionId != $sectionId){
            $allEnrollments = array_merge($allEnrollments, $section->Enrollments);
        }
    }

    $usersToEnroll = $sectionToDelete['response']->Enrollments;
    foreach($usersToEnroll as $userId){
        if(!in_array($userId, $allEnrollments)){
            //it has response unlike other delete options, could be used for logging
            doValenceRequest('DELETE', '/d2l/api/lp/'.$config['LP_Version'].'/enrollments/orgUnits/'.$orgUnitId.'/users/'.$userId);
        }
    }

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
            $gradeObject = getGradeItemById($orgUnitId, $sectionCode[2]);

            $event['sectionId'] = $section->SectionId;
            $event['eventId'] = $sectionCode[1];
            $event['eventName'] = $engageEvent->name;
            $event['startDate'] = $engageEvent->startsOn;
            $event['endDate'] = $engageEvent->endsOn;
            $event['gradeId'] = $sectionCode[2];
            $event['gradeObjectName'] = $gradeObject->Name;
            $event['lastSync'] = $section->Description->Text;
            
            $linkedEvents[] = $event;
        }
    }
    return $linkedEvents;
}

// returns a row of table with BLE sections informations and delet action button. 
function printLinkedEvents($orgUnitId){
    $tablerows='';
    $linkedEvents = getLinkedEvents($orgUnitId);

    //paged sections, sefault set to 10 sections at a time
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $itemsPerPage = 10;
    $offset = ($page - 1) * $itemsPerPage;
    $pageSections = array_slice($linkedEvents, $offset, $itemsPerPage);

    //printing paged result
    foreach($pageSections as $event){
        $tablerows .= "<tr>
                        <td style='display:none;'>".$event['sectionId']."</td>
                        <td style='display:none;'>".$event['eventId']."</td>
                        <td>".$event['eventName']."</td>
                        <td>".dateToString($event['startDate'])."</td>
                        <td>".dateToString($event['endDate'])."</td>
                        <td style='display:none;'>".$event['gradeId']."</td>
                        <td>".$event['gradeObjectName']."</td>
                        <td>
                            <div class='action-container'>
                                <span style='font-size:14px; grid-column: 2;grid-row:1;'>Last updated on <br>".$event['lastSync']."</span>
                                <img src='img/loading.gif' alt='Loading...' class='loading-gif' style='display: none;'>
                                <div class='button-container'>
                                    <button type='button' class='btn btn-secondary btn-sm update-btn' onclick='updateEventById(this)'>Update</button>
                                    <button type='button' class='btn btn-red btn-sm delete-btn' data-bs-toggle='modal' data-bs-target='#deleteConfirmModal' onclick='setSessionId(this)'>Delete</button>
                                </div>
                            </div>
                        </td>
                    </tr>";
    }
    return $tablerows;
}

//adds the orgUnitId to sharing list of the LTI tool
//that how scheduled sync will now which orgunits to check for
function shareWithOrgUnit($orgUnitId) {
    global $config;
    $data = array(
        "SharingOrgUnitId" => $orgUnitId,
        "ShareWithOrgUnit" => true,
        "ShareWithDescendants" => false
    );
    $response = doValenceRequest('POST', '/d2l/api/le/'.$config['LE_Version'].'/lti/tp/6606/29/sharing/', $data);
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
            updateSection($orgUnitId, $event['sectionId']);
        }  
    }
}

?>