<?php
require_once("info.php");

//checks if provided date in UTC is in the past
function isEventDateInPast($datetime){
    date_default_timezone_set('UTC');    
    $currentDateTime = new DateTime();
    $providedDateTime = new DateTime($datetime);
    return $providedDateTime < $currentDateTime;
}

//Engage API calls (GET only), returns php array
function experienceBUcall($url){
    global $config;

    $ch = curl_init($config['engageUrl'] . $url);
    $headers = array (
        'Accept: application/json',
        'X-Engage-Api-Key: ' . $config['engageToken']
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // Close the cURL session
    curl_close($ch);
    return json_decode($response);
}


//returns list of organizations where user holds a position
function getOrganizationsByUsername($userName){
    $result = array();
    $isMore = true;
    $skip = 0;
    $take = 20;
    while($isMore){
        $response = experienceBUcall('/v3.0/organizations/positionholder/?userId.username=' . $userName . '&take=' . $take . '&skip=' . $skip);
        foreach ($response->items as $each){
            $orgs = experienceBUcall('/v3.0/organizations/organization/?ids=' . $each->organizationId);  
            $result[] = array(
                "id"   => $each->organizationId,
                "name" => $orgs->items[0]->name
            );
        }
        $skip = $skip + $take;
        if ($skip > $response->totalItems) $isMore = false;
    }
    return $result;
}

//returns list of events for given organization
function getEvents($organizationId){
    $result = array();
    $isMore = true;
    $skip = 0;
    $take = 20;
    while($isMore){
        $response = experienceBUcall('/v3.0/events/event/?organizationId='. $organizationId . '&take=' . $take . '&skip=' . $skip);
        foreach ($response->items as $each){  
            if (!isEventDateInPast($each->endsOn)){
                $result[] = array(
                    "id"   => $each->id,
                    "name" => $each->name
                );
            }
        }
        $skip = $skip + $take;
        if ($skip > $response->totalItems) $isMore = false;
    }
    return $result;
}

//get event rsvp's
function getEventUsers($eventId){
    $result = array();
    $isMore = true;
    $skip = 0;
    $take = 20;
    while($isMore){
        $response = experienceBUcall('/v3.0/events/event/'. $eventId . '&take=' . $take . '&skip=' . $skip);
        foreach ($response->items as $each){  
            array_push($result, $each->userId->username);
        }
        $skip = $skip + $take;
        if ($skip > $response->totalItems) $isMore = false;
    }
    return $result;
}
?>