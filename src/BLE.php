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
    $formattedDateTime = $dateTime->format('Y-m-d H:i');
    return (string) $formattedDateTime;
}

//returns classlist with Intructor role only
function getClasslist ($orgUnitId){
    global $config, $instructor_role_id;
    $hasMore = true;
    $bookmark = '';
    $instructors = array();
    while ($hasMore){
        $response = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/enrollments/orgUnits/'.$orgUnitId.'/users/?roleId='.$instructor_role_id);
        if($response['response']->PagingInfo->HasMoreItems == false){
            $hasMore = false;
        }
        $bookmark = $response['response']->PagingInfo->Bookmark;
        foreach($response['response']->Items as $user){
            array_push($instructors, $user->User->Identifier);
        }
    }
    return $instructors;
}


//creates a new section in BLD (links engage event with an offering)
function createSection($orgUnitId, $eventId, $gradeId, $organizationId){
    global $config;
    $engageEvent = getEventById($eventId);
    $eventName = $engageEvent->name;
    $eventDate = dateToString($engageEvent->startsOn);
    $data = array(
        "Name"=> $eventName." (".$eventDate.")",
        "Code"=> "engage-".$organizationId."-".$eventId."-".$gradeId,
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
    $response = doValenceRequest('PUT', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/'.$sectionId, $data);
    return $response['response']->Description->Text;
}


//enrolls engage RSVP users into the offering and to specific section dedicated to engage event
function enrollEngageEventUsers($orgUnitId, $sectionId, $usersToEnroll) {
    global $config, $student_role_id;
    $instructors = getClasslist($orgUnitId);
    foreach($usersToEnroll as $userName){
        $userId = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/users/?userName='.$userName);
        if($userId['Code']==200){
            $parentData = array(
                "OrgUnitId"=> $orgUnitId,
                "UserId"=> $userId['response']->UserId,
                "RoleId"=> $student_role_id
            );
            $sectionData = array(
                "UserId"=> $userId['response']->UserId
            );
        }
        if (!in_array($userId['response']->UserId, $instructors)){
            $enrollToParent = doValenceRequest('POST', '/d2l/api/lp/'.$config['LP_Version'].'/enrollments/', $parentData);
            $enrollToSection = doValenceRequest('POST', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/'.$sectionId.'/enrollments/', $sectionData); 
        }
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

//returns Numeric and PassFail grade items for a given orgUnitId
function getGradeItems($orgUnitId){
    global $config;
    $response = doValenceRequest('GET', '/d2l/api/le/'.$config['LE_Version'].'/'.$orgUnitId.'/grades/');
    $result = array();
    foreach ($response['response'] as $each){ 
        if($each->GradeType=='Numeric' || $each->GradeType=='PassFail'){
            $result[] = array(
                "id"   => $each->Id,
                "name" => $each->Name
            );
        }
    }
    return  $result;
}

//returns gradeObject for given id
function getGradeItemById($orgUnitId, $gradeId){
    global $config;
    $response = doValenceRequest('GET', '/d2l/api/le/'.$config['LE_Version'].'/'.$orgUnitId.'/grades/'.$gradeId);
    return $response['response'];
}

// grades all users who attended the event
// for Numeric type grade, it grades Max
// for Pass/Fail type, it grades Pass
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
        $user = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/users/?userName='.$userName);
        if($user['Code']==200){
            doValenceRequest('PUT', '/d2l/api/le/'.$config['LE_Version'].'/'.$orgUnitId.'/grades/'.$gradeId.'/values/'.$user['response']->UserId, $data);
        }
    }
}


// returns BLE section (experience BU events) for privileged users
function getLinkedEvents($orgUnitId, $ltiRole, $userName){
    global $config;
    $linkedEvents = array();

    $userOrganizations = userOrganizations($userName);

    $response = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/');
    $sections = array_reverse($response['response']);
    foreach ($sections as $section) {
        if (strpos($section->Code, 'engage') !== false){
            $sectionCode = explode('-', $section->Code);
            $engageEvent = getEventById($sectionCode[2]);
            $gradeObject = getGradeItemById($orgUnitId, $sectionCode[3]);
            if ($ltiRole=='Administrator' || in_array((int)$sectionCode[1], $userOrganizations)){
                $event = array();
                $event['sectionId'] = $section->SectionId;
                $event['eventId'] = $sectionCode[2];
                $event['eventName'] = $engageEvent->name;
                $event['startDate'] = dateToString($engageEvent->startsOn);
                $event['endDate'] = dateToString($engageEvent->endsOn);
                $event['lastSync'] = $section->Description->Text;
                $event['organizationId'] = $sectionCode[1];
                if (!empty($sectionCode[3])){
                    $event['gradeId'] = $sectionCode[3];
                    $event['gradeObjectName'] = $gradeObject->Name;
                } else {
                    $event['gradeId'] = '';
                    $event['gradeObjectName'] = '';
                }
                
                
                $linkedEvents[] = $event;
            }
        }
    }
    return $linkedEvents;
}

//adds the orgUnitId to sharing list of the LTI tool
//that how scheduled sync will now which orgunits to check for
function shareWithOrgUnit($orgUnitId) {
    global $config, $toolProviderId;
    $data = array(
        "SharingOrgUnitId" => $orgUnitId,
        "ShareWithOrgUnit" => true,
        "ShareWithDescendants" => false
    );
    $response = doValenceRequest('POST', '/d2l/api/le/'.$config['LE_Version'].'/lti/tp/6606/'.$toolProviderId.'/sharing/', $data);
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
    $linkedEvents = getLinkedEvents($orgUnitId, 'Administrator', 'none');
    foreach($linkedEvents as $event){
        if (!isDate30DaysOrMoreInPast($event['endDate'])){
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