<?php
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/usertoken_table_queries.php';
require_once 'api/v1/license_table_queries.php';
require_once 'api/v1/licensesession_table_queries.php';
require_once 'api/v1/opensslutils.php';
require_once 'api/v1/product_table_queries.php';

// ensure that the token passed in the JSON can be found in the database
// and that the signature of the JSON data can be verified by the public key for the user
function verifyrequest()
{
    if (!array_key_exists('json', $_POST) || empty($_POST['json']))
    {
        $response = array();
        $response['errormessage'] = 'No license request data was provided';
        $response['errorcode'] = ERR_LICENSE_REQUEST_DATA_NOT_SPECIFIED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        exit();
    }
    if (!array_key_exists('sig', $_POST) || empty($_POST['sig']))
    {
        $response = array();
        $response['errormessage'] = 'No license request signature was provided.';
        $response['errorcode'] = ERR_LICENSE_REQUEST_SIG_NOT_SPECIFIED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        exit();
    }

    $raw_json = base64_decode($_POST['json']);
    $json = json_decode($raw_json, true);

    $token_data = usertoken_getdata_ifvalid($json['token']);
    if (NULL == $token_data)
    {
        $response = array();
        $response['errormessage'] = 'Access token is invalid.';
        $response['errorcode'] = ERR_LICENSE_ACCESS_TOKEN_INVALID;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        exit();
    }

    $user_and_host = userhostmachine_table_getuserandhost($token_data['userhost']);

    $certificate = user_table_getcertificate($user_and_host['user']);

    $sigb64 = $_POST['sig'];
    $sig = base64_decode($sigb64);

    $verified = verify_client_request($raw_json, $sig, $certificate);
    if (1 == $verified)
    {
        return $json;
    }

    if (0 == $verified)
    {
        storelog('License request data could not be verified by user certificate: '.openssl_error_string());
        $response = array();
        $response['errormessage'] = 'Cannot verify license request for user.';
        $response['errorcode'] = ERR_LICENSE_REQUEST_DATA_NOT_VERIFIED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        exit();
    }
    else
    {
        storelog('OpenSSL error verifying license request data: '.openssl_error_string());
        $response = array();
        $response['errormessage'] = 'Cannot verify license request for user.';
        $response['errorcode'] = ERR_SERVER_ERROR;

        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
        exit();
    }
}

function licenserequest()
{
    $json = verifyrequest();

    $token_data = usertoken_getdata_ifvalid($json['token']);
    $user_and_host = userhostmachine_table_getuserandhost($token_data['userhost']);
    $product_id = product_getid($json['productname']);

    // is any license for user on this product available?
    // do not check validity
    $license_available = license_hasproductlicense($user_and_host['user'], $product_id);
    if (is_null($license_available))
    {
        // user has never used this product before - grant them a trial
        // we'll be able to upgrade this to a purchased copy later
        license_granttrial($user_and_host['user'], $product_id);
    }

    // now check validity
    $license_valid = license_validate($user_and_host['user'], $product_id);
    if (is_null($license_valid))
    {
        $response = array();
        $response['errormessage'] = 'License has expired.';
        $response['errorcode'] = ERR_LICENSE_EXPIRED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        exit();
    }

    $lic_type = licensetype_gettypename($license_valid['type']);

    // create a new session
    $session = licensesession_create($license_valid['id'], $lic_type, $user_and_host['user'], $json['productname'], $json['productversion'], $json['operatingsystem']);

    $response = array();
    $response['session'] = base64_encode($session);
    $response['length'] = strlen($session);
    $response['typename'] = $lic_type;
    $response['typeid'] = $license_valid['type'];
    $response['productname'] = $json['productname'];

    // remaining days is the last value returned from license_validate
    $values = array_values($license_valid);
    $response['remainingdays'] = array_slice($values, -1, 1)[0];

    header('Content-Type: application/json', true, 201);
    echo json_encode($response);
}
?>
