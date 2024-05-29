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

if (isSafari()) session_id($_REQUEST['session_id']);

session_start();

// Check for valid session
if ($_SESSION['_basic_lti_context']['oauth_consumer_key'] !== $lti_auth['key']) {
    echo 'session data: '.$_SESSION['_basic_lti_context']['oauth_consumer_key'].'  lti_auth: '.$lti_auth['key'];
    //die('Expired user session, please contact ' . $supportEmail . ' for support.');
    echo 'Expired user session, please contact ' . $supportEmail . ' for support.';
    exit;
}

$orgUnitId = $_SESSION['_basic_lti_context']['context_id'];
$userName = $_SESSION['_basic_lti_context']['ext_d2l_username'];
$ltiRole = substr(strrchr($_SESSION['_basic_lti_context']['roles'], ','), 1);

/**
 * Handle main sync button submission. Checks if the event is already linked
 * If not, creates a section in Brightspace site, enrolls event RSVPs into a section
 * If grade ogject is linked, then grades Attendance list 
 */
function handleMainSync($orgUnitId) {
    if (isSectionExist($orgUnitId, $_POST['ebuEvent'])) {
        echo "The selected event is already linked.";
    } else {
        $sectionId = createSection($orgUnitId, $_POST['ebuEvent'], $_POST['gradeItem'], $_POST['ebuOrganization']);
        echo "The selected event successfully linked.";

        $engageUsers = getEventUsers($_POST['ebuEvent']);
        enrollEngageEventUsers($orgUnitId, $sectionId, $engageUsers);

        if (!empty($_POST['gradeItem'])) {
            gradeEventAttendence($orgUnitId, $_POST['ebuEvent'], $_POST['gradeItem']);
        }
        updateSection($orgUnitId, $sectionId);
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
        echo updateSection($orgUnitId, $_POST['sectionId']);
    } else {
        unEnrollEngageUsers($orgUnitId, $_POST['sectionId']);
        deleteSection($orgUnitId, $_POST['sectionId']);
    }
}

/**
 * Handle initial organization list retrieval
 */
function handleInitialOrganizationList($userName, $ltiRole) {
    if (isset($ltiRole) && $ltiRole == 'Administrator'){
        $orgs_response = getAllOrganizations();
    } else {
        $orgs_response = getOrganizationsByUsername($userName);
    }
    echo json_encode($orgs_response);
}

/**
 * Handle pagination
 */
function handleTableData($orgUnitId, $ltiRole, $userName) {
    $tableData = getLinkedEvents($orgUnitId, $ltiRole, $userName);
    echo json_encode($tableData);
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
} elseif (isset($_GET['tablePrint'])){
    handleTableData($orgUnitId, $ltiRole, $userName);
} else {
    handleInitialOrganizationList($userName, $ltiRole);
}

?>