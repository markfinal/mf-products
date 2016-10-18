<?php
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/verifyclientdata.php';
require_once 'api/v1/feedback_table_queries.php';

function providefeedback()
{
    $json = verifyclientdata();

    // this has been fetched before... so a bit wasteful
    $session_data = licensesession_getdata_ifvalid($json['session']);

    feedback_addmessage($session_data['id'], $json['message']);

    $response = array();
    $response['errorcode'] = ERR_NONE;
    $response['errormessage'] = "Thank you for your feedback";

    header('Content-Type: application/json', true, 200);
    echo json_encode($response);
}
?>
