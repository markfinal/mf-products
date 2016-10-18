<?php

require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/log.php';
require_once 'api/v1/host_table_queries.php';

function registermacaddress()
{
    if (!array_key_exists('MAC', $_POST) || empty($_POST['MAC']))
    {
        $response = array();
        $response['errormessage'] = 'A MAC address must be provided.';
        $response['errorcode'] = ERR_MAC_ADDRESS_NOT_SPECIFIED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        return;
    }
    if (!filter_var($_POST['MAC'], FILTER_VALIDATE_MAC))
    {
        $response = array();
        $response['errormessage'] = 'The MAC address used an incorrect format.';
        $response['errorcode'] = ERR_MAC_INCORRECT_FORMAT;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        return;
    }
    if (!array_key_exists('email', $_POST) || empty($_POST['email']))
    {
        $response = array();
        $response['errormessage'] = 'An email address must be provided.';
        $response['errorcode'] = ERR_EMAIL_NOT_SPECIFIED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        return;
    }
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
    {
        $response = array();
        $response['errormessage'] = 'The email address used an incorrect format.';
        $response['errorcode'] = ERR_EMAIL_INCORRECT_FORMAT;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        return;
    }
    if (!array_key_exists('sig', $_POST) || empty($_POST['sig']))
    {
        $response = array();
        $response['errormessage'] = 'Signature to verify the user was not specified.';
        $response['errorcode'] = ERR_MAC_ADDRESS_SIG_NOT_SPECIFIED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        return;
    }

    // TODO: are MAC addresses delivered in the same format from all OSs?
    // i.e. will a dual boot machine show the same?
    $MACaddress = $_POST['MAC'];

    $user_data = user_table_get_id($_POST['email']);

    $certificate = $user_data['certificate'];

    $sigb64 = $_POST['sig'];
    $sig = base64_decode($sigb64);

    $verified = verify_client_request($MACaddress, $sig, $certificate);
    if (1 == $verified)
    {
        host_table_insert($MACaddress);
        return;
    }

    if (0 == $verified)
    {
        storelog('MAC address could not be verified by user certificate: '.openssl_error_string());
        $response = array();
        $response['errormessage'] = 'Cannot verify MAC address by user.';
        $response['errorcode'] = ERR_MAC_ADDRESS_NOT_VERIFIED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        exit();
    }
    else
    {
        storelog('OpenSSL error while adding MAC address: '.openssl_error_string());
        $response = array();
        $response['errormessage'] = 'Cannot verify MAC address by user.';
        $response['errorcode'] = ERR_SERVER_ERROR;

        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
        exit();
    }
}
?>
