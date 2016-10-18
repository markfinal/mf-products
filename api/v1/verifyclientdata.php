<?php
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/log.php';
require_once 'api/v1/opensslutils.php';
require_once 'api/v1/licensesession_table_queries.php';
require_once 'api/v1/license_table_queries.php';
require_once 'api/v1/user_table_queries.php';

// ensure that the session passed in the JSON can be found in the database
// and that the signature of the JSON data can be verified by the public key for the user
function verifyclientdata()
{
    if (!array_key_exists('json', $_POST) || empty($_POST['json']))
    {
        storelog('No JSON passed as client data');
        $response = array();
        $response['errormessage'] = 'No client data was provided.';
        $response['errorcode'] = ERR_CLIENT_DATA_NOT_SPECIFIED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        exit();
    }
    if (!array_key_exists('sig', $_POST) || empty($_POST['sig']))
    {
        storelog('No signature of the JSON was passed by the client');
        $response = array();
        $response['errormessage'] = 'No client signature was provided.';
        $response['errorcode'] = ERR_CLIENT_SIG_NOT_SPECIFIED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        exit();
    }

    $raw_json = base64_decode($_POST['json']);
    $json = json_decode($raw_json, true);

    $session_data = licensesession_getdata_ifvalid($json['session']);
    if (NULL == $session_data)
    {
        storelog('License session \''.$json['session'].'\' was not found in the database');
        $response = array();
        $response['errormessage'] = 'Session token is invalid.';
        $response['errorcode'] = ERR_CLIENT_SESSION_TOKEN_INVALID;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        exit();
    }

    $user_id = license_getuserid($session_data['license']);
    $certificate = user_table_getcertificate($user_id);

    $sigb64 = $_POST['sig'];
    $sig = base64_decode($sigb64);

    $verified = verify_client_request($raw_json, $sig, $certificate);
    if (1 == $verified)
    {
        return $json;
    }
    else if (0 == $verified)
    {
        storelog('Data could not be verified by user certificate: '.openssl_error_string());
        $response = array();
        $response['errormessage'] = 'Cannot verify user.';
        $response['errorcode'] = ERR_CLIENT_DATA_NOT_VERIFIED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        exit();
    }
    else
    {
        storelog('OpenSSL error verifying data: '.openssl_error_string());
        $response = array();
        $response['errormessage'] = 'Cannot verify user.';
        $response['errorcode'] = ERR_SERVER_ERROR;

        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
        exit();
    }
}
?>
