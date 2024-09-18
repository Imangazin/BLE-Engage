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


function userNameToUserId ($userNames){
    global $config;
    $userIds = array();
    foreach($userNames as $userName){
        $userId = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/users/?userName='.$userName);
        array_push($userIds, $userId['response']->UserId);
    }
    return $userIds;
}


function getSectionUsers($orgUnitId, $sectionId){
    global $config;
    $section = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/'.$sectionId);
    $sectionUsers = $sectionToDelete['response']->Enrollments;
    return $sectionUsers;
} 


//enrolls engage RSVP users into the offering and to specific section dedicated to engage event
function enrollEngageEventUsers($orgUnitId, $sectionId, $usersToEnroll) {
    global $config, $student_role_id;
    $instructors = getClasslist($orgUnitId);
    foreach($usersToEnroll as $userId){
        $parentData = array(
            "OrgUnitId"=> $orgUnitId,
            "UserId"=> $userId,
            "RoleId"=> $student_role_id
        );
        $sectionData = array(
            "UserId"=> $userId
        );
    
        if (!in_array($userId, $instructors)){
            $enrollToParent = doValenceRequest('POST', '/d2l/api/lp/'.$config['LP_Version'].'/enrollments/', $parentData);
            $enrollToSection = doValenceRequest('POST', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/'.$sectionId.'/enrollments/', $sectionData); 
        }
    }
}

//checks each user if it also enrolled in other sections
//if not deletes from the offering, otherwise do nothing
function unEnrollEngageUsers($orgUnitId, $sectionId, $usersToUnEnroll){
    global $config;
    $allSections = doValenceRequest('GET', '/d2l/api/lp/'.$config['LP_Version'].'/'.$orgUnitId.'/sections/');
    // all section enrollments except $sectionId, not included instructor role
    // since instructor roles will never show up in $userToEnroll array, we should be good 
    $allEnrollments = array();
    foreach ($allSections['response'] as $section) {
        if ($section->SectionId != $sectionId){
            $allEnrollments = array_merge($allEnrollments, $section->Enrollments);
        }
    }
    foreach($usersToUnEnroll as $userId){
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


function getGradedUsers($orgUnitId, $gradeId){
    global $config;
    $gradedUsers = array();
    $next = '/d2l/api/le/'.$config['LE_Version'].'/'.$orgUnitId.'/grades/'.$gradeId.'/values/?sort=lastname&pageSize=20&bookmark=&bookmarkUserId=';
    while ($next != null){
        $response = doValenceRequest('GET', $next);
        foreach($response['response']->Objects as $user){
            if (isset($user->GradeValue) && $user->GradeValue != null && isset($user->GradeValue->PointsNumerator) && $user->GradeValue->PointsNumerator != 0){
                array_push($gradedUsers, (int)$user->User->Identifier);
            }
        }
        $next = $response['response']->Next;
    }
    return $gradedUsers;
}

// grades all users who attended the event
// for Numeric type grade, it grades Max
// for Pass/Fail type, it grades Pass
function gradeEventAttendence($orgUnitId, $eventId, $gradeId, $eventAttendees, $action='grade'){
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
            $data['PointsNumerator'] = ($action=='grade') ? $gradeInfo['response']->MaxPoints : 0;
            break;
        case 'PassFail':
            $data['GradeObjectType'] = 2;
            $data['Pass'] = (action=='grade') ? true : false;
            break;
        default:
            //break out from the function
            return;
    }

    //$eventAttendees = getEventAttendees($eventId);
    foreach($eventAttendees as $userId){
        doValenceRequest('PUT', '/d2l/api/le/'.$config['LE_Version'].'/'.$orgUnitId.'/grades/'.$gradeId.'/values/'.$userId, $data);
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


function updateRsvp($orgUnitId, $sectionId, $eventId){
    //people in event system
    $eventRsvpList = getEventUsers($eventId);
    echo "RSVP:  \n";
    echo var_dum($eventRsvpList);
    $eventRsvpUserIds = userNameToUserId($eventRsvpList);
    //people in D2L
    $sectionRsvpList = getSectionUsers($orgUnitId, $sectionId);

    // echo "RSVP:  \n";
    // echo var_dum($eventRsvpUserIds);
    // echo "Section Users:  \n";
    // echo var_dum($sectionRsvpList);


    //find new enrollments
    $usersToEnroll = array_diff($eventRsvpUserIds, $sectionRsvpList);
    //find dropped users
    $usersToUnEnroll = array_diff($sectionRsvpList, $eventRsvpUserIds);

    enrollEngageEventUsers($orgUnitId, $sectionId, $usersToEnroll);
    unenrollEngageEventUsers($orgUnitId, $sectionId, $usersToUnEnroll);
}

function updateAttendance($orgUnitId, $eventId, $gradeId){
    //attended list from event system
    echo "Start";
    $eventAttendees = getEventAttendees($eventId);
    $eventAttendeeIds = userNameToUserId($eventAttendees);
    //graded list in d2l
    $gradedUserIds = getGradedUsers($orgUnitId, $gradeId);
    //find new attendies
    $usersToGrade = array_diff($eventAttendeeIds, $gradedUserIds);
    //find not attended users
    $usersToUnGrade = array_diff($gradedUserIds, $eventAttendeeIds);

    gradeEventAttendence($orgUnitId, $eventId, $gradeId, $usersToGrade);
    gradeEventAttendence($orgUnitId, $eventId, $gradeId, $usersToUnGrade, 'ungrade');

}


// syncs engage RSVP and attendance with BLE for linked events. Skippes events which ended 30 days ago.
function syncEngageBLE($orgUnitId){
    global $config;
    $linkedEvents = getLinkedEvents($orgUnitId, 'Administrator', 'none');
    foreach($linkedEvents as $event){
        if (!isDate30DaysOrMoreInPast($event['endDate'])){

            if (!empty($event['gradeId'])){
                updateAttendance($orgUnitId, $event['eventId'], $event['gradeId']);
            }

            updateRsvp($orgUnitId, $event["sectionId"], $event["eventId"]);
            
            updateSection($orgUnitId, $event['sectionId']);
        }  
    }
}

?>