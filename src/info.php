<?php
// Lti credentials
$lti_auth = array('key'=>'', 'secret'=>'');
//Cookie location
$cookie_location = '';
//Support email adress
$support_email = '';

//Using username to look for user in Brightspace might return multiple users like melliott and melliott2
//it is safe to use email insted like melliott@brocku.ca will always return single result
$domain = '';

//Brightspace LTI tool provider id
$toolProviderId = 0;

//BLE api credentials
$config = array(
    'host' => '',
    'port' => 443,
    'scheme' => 'https',
    'appId' => '',
    'appKey' => '',
    'userId' => '',
    'userKey' => '',
    'LP_Version' =>'1.45',
    'LE_Version' => '1.74',
    'engageUrl' =>'',
    'engageToken' =>''
);
?>