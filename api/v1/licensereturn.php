<?php
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/verifyclientdata.php';

function licensereturn()
{
    $json = verifyclientdata();

    // this has been fetched before... so a bit wasteful
    $session_data = licensesession_getdata_ifvalid($json['session']);

    licensesession_end($session_data['id']);

    $response = array();
    $response['errorcode'] = ERR_NONE;
    $response['errormessage'] = 'License session returned.';

    header('Content-Type: application/json', true, 200);
    echo json_encode($response);
}
?>
