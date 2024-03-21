<?php
require_once("info.php");
require_once("engage.php");
require_once("BLE.php");

// Checks user's browser, returns true if it is Safari
function isSafari() {
    return (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') && !strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome'));
}
// Use session_id that is passed as hidden form data for Safari browser
if (isSafari()) session_id($_POST["session_id"]);

session_start();

if($_SESSION['_basic_lti_context']['oauth_consumer_key'] == $lti_auth['key']){
    $orgUnitId = $_SESSION['_basic_lti_context']['context_id'];
    $userName = $_SESSION['_basic_lti_context']['ext_d2l_username'];
    
    if (isset($_POST['ebuOrganization']) && isset($_POST['ebuEvent'])){
        
        if (isSectionExist($orgUnitId, $_POST['ebuEvent'])){
            echo "The selected event is already linked.";
        }else{
            //create a section and get its Id
            $sectionId = createSection($orgUnitId, $_POST['ebuEvent']);
            echo "The selected event successfully linked.";
            //get engage users
            $engageUsers = getEventUsers($_POST['ebuEvent']);
            echo var_dump($engageUsers);
        }

    }
    elseif (isset($_GET['organizationId'])){
        $events_response = getEvents($_GET['organizationId']);
        echo json_encode($events_response);
    }else{
        $orgs_response = getOrganizationsByUsername($userName);
        echo json_encode($orgs_response);
    }

} else {
    echo 'Expired user session, please contact '.$supportEmail.' for support.';
}



?>