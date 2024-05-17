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


// Determine which route to take
if (isset($_POST['ebuOrganization']) && isset($_POST['ebuEvent'])) {
    handleMainSync($orgUnitId);
} elseif (isset($_GET['organizationId'])) {
    handleOrganizationSelection();
} elseif (isset($_GET['gradeSyncEnabled'])) {
    handleGradeItemSelection($orgUnitId);
} elseif (isset($_POST['sectionId'])) {
    handleSectionUpdateOrDelete($orgUnitId);
} else {
    handleInitialOrganizationList($userName);
}

?>