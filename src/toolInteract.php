<?php
require_once("info.php");
require_once("engage.php");
require_once("BLE.php");

/**
 * Checks if the user's browser is Safari
 *
 * @return bool
 */
function isSafari() {
    return (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') && !strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome'));
}
// Use session_id that is passed as hidden form data for Safari browser
if (isSafari()) session_id($_POST["session_id"]);

session_start();

// Check for valid session
if ($_SESSION['_basic_lti_context']['oauth_consumer_key'] !== $lti_auth['key']) {
    echo 'Expired user session, please contact ' . $supportEmail . ' for support.';
    exit;
}

$orgUnitId = $_SESSION['_basic_lti_context']['context_id'];
$userName = $_SESSION['_basic_lti_context']['ext_d2l_username'];

/**
 * Handle main sync button submission. Checks if the event is already linked
 * If not, creates a section in Brightspace site, enrolls event RSVPs into a section
 * If grade ogject is linked, then grades Attendance list 
 */
function handleMainSync($orgUnitId) {
    if (isSectionExist($orgUnitId, $_POST['ebuEvent'])) {
        echo "The selected event is already linked.";
    } else {
        $sectionId = createSection($orgUnitId, $_POST['ebuEvent'], $_POST['gradeItem']);
        echo "The selected event successfully linked.";

        $engageUsers = getEventUsers($_POST['ebuEvent']);
        enrollEngageEventUsers($orgUnitId, $sectionId, $engageUsers);

        if (!empty($_POST['gradeItem'])) {
            gradeEventAttendence($orgUnitId, $_POST['ebuEvent'], $_POST['gradeItem']);
        }
    }
}

/**
 * Handle organizaton selection, responds with a list of events for selected organization
 */
function handleOrganizationSelection() {
    $events_response = getEvents($_GET['organizationId']);
    echo json_encode($events_response);
}

/**
 * Handle organizaton selection, responds with a list of events for selected organization
 */
function handleGradeItemSelection($orgUnitId) {
    $gradesList = getGradeItems($orgUnitId);
    echo json_encode($gradesList);
}

/**
 * Handles Update and Delete requests from the action list
 */
function handleSectionUpdateOrDelete($orgUnitId) {
    if (isset($_GET['updateEvent'])) {
        $engageUsers = getEventUsers($_POST['eventId']);
        enrollEngageEventUsers($orgUnitId, $_POST['sectionId'], $engageUsers);

        if (!empty($_POST['gradeId'])) {
            gradeEventAttendence($orgUnitId, $_POST['eventId'], $_POST['gradeId']);
        }
        echo "Event successfully updated";
    } else {
        deleteSection($orgUnitId, $_POST['sectionId']);
    }
}

/**
 * Handle initial organization list retrieval
 */
function handleInitialOrganizationList($userName) {
    $orgs_response = getOrganizationsByUsername($userName);
    echo json_encode($orgs_response);
}

// Route conditions to handlers
$routes = [
    'mainSync' => 'handleMainSync',
    'organizationSelection' => 'handleOrganizationSelection',
    'gradeItemSelection' => 'handleGradeItemSelection',
    'sectionUpdateOrDelete' => 'handleSectionUpdateOrDelete',
    'initialOrganizationList' => 'handleInitialOrganizationList',
];

// Determine which route to take
if (isset($_POST['ebuOrganization']) && isset($_POST['ebuEvent'])) {
    $route = 'mainSync';
} elseif (isset($_GET['organizationId'])) {
    $route = 'organizationSelection';
} elseif (isset($_GET['gradeSyncEnabled'])) {
    $route = 'gradeItemSelection';
} elseif (isset($_POST['sectionId'])) {
    $route = 'sectionUpdateOrDelete';
} else {
    $route = 'initialOrganizationList';
}

// Execute the handler for the determined route
if (isset($routes[$route])) {
    // Pass necessary variables to the handler
    call_user_func($routes[$route], $orgUnitId, $userName);
} else {
    echo 'No valid route found.';
}


// // Verify LTI authentication
// if($_SESSION['_basic_lti_context']['oauth_consumer_key'] == $lti_auth['key']){
//     $orgUnitId = $_SESSION['_basic_lti_context']['context_id'];
//     $userName = $_SESSION['_basic_lti_context']['ext_d2l_username'];
    
//     // Handle main sync button submission
//     if (isset($_POST['ebuOrganization']) && isset($_POST['ebuEvent'])){
//         //check if the event is already linked
//         if (isSectionExist($orgUnitId, $_POST['ebuEvent'])){
//             echo "The selected event is already linked.";
//         }else{
//             //create a section and get its Id
//             $sectionId = createSection($orgUnitId, $_POST['ebuEvent'], $_POST['gradeItem']);
//             echo "The selected event successfully linked.";
//             //get engage users
//             $engageUsers = getEventUsers($_POST['ebuEvent']);
//             enrollEngageEventUsers($orgUnitId, $sectionId, $engageUsers);
//             //if the event linked to the grade item, then grade this section users with according to attendance in engage
//             if (!empty($_POST['gradeItem'])){
//                 gradeEventAttendence($orgUnitId, $_POST['ebuEvent'], $_POST['gradeItem']);
//             }
//         }

//     }// Handle organization selection
//     elseif (isset($_GET['organizationId'])){
//         $events_response = getEvents($_GET['organizationId']);
//         echo json_encode($events_response);
//     }// Handle grade item selection
//     elseif (isset($_GET['gradeSyncEnabled'])){
//         $gradesList = getGradeItems($orgUnitId);
//         echo json_encode($gradesList);
//     }// Handle section update or deletion
//     elseif(isset($_POST['sectionId'])){
//         if(isset($_GET['updateEvent'])){
//             $engageUsers = getEventUsers($_POST['eventId']);
//             enrollEngageEventUsers($orgUnitId, $_POST['sectionId'], $engageUsers);
//             //if the event linked to the grade item, then grade this section users with according to attendance in engage
//             if (!empty($_POST['gradeId'])){
//                 gradeEventAttendence($orgUnitId, $_POST['eventId'], $_POST['gradeId']);
//             }
//             echo "Event successfully updated";
//         }else{
//             deleteSection($orgUnitId, $_POST['sectionId']);
//         }
        
//     }// Handle initial organization list retrieval
//     else{
//         $orgs_response = getOrganizationsByUsername($userName);
//         echo json_encode($orgs_response);
//     }
// } else {
//     echo 'Expired user session, please contact '.$supportEmail.' for support.';
// }
?>