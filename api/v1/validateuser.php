<?php
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/user_table_queries.php';
require_once 'api/v1/host_table_queries.php';
require_once 'api/v1/userhostmachine_table_queries.php';
require_once 'api/v1/usertoken_table_queries.php';

function var_dump_error_log($object)
{
    ob_start();
    var_dump($object);
    $contents = ob_get_contents();
    ob_end_clean();
    error_log($contents);
}

// Validate a user can progress to acquire a license, given their email address, public key, and MAC address.
// Returns an expiring security token.
function validateuser()
{
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

    $userrow = user_table_get_id($_POST['email']);

    if (!array_key_exists('MAC', $_POST) || empty($_POST['MAC']))
    {
        $response = array();
        $response['errormessage'] = 'The MAC address of the computer must be provided.';
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

    // ensure that all MAC addresses are uppercase
    $MACaddress = strtoupper($_POST['MAC']);

    $num_user_machines = userhostmachine_table_get_num_usermachines($userrow['id']);
    $host_id = host_table_get_id($MACaddress, $num_user_machines, $userrow['maxmachines']);

    // as both user and host are confirmed registered, is there a mapping
    // to authorise using this host by this user?
    $user_machine_id = userhostmachine_table_get_id($userrow['id'], $host_id);
    if (0 == $user_machine_id)
    {
        $existing_request = userhostmachine_table_find_existing_request($_POST['email'], $_POST['MAC']);
        $response = array();
        if (0 == $existing_request['id'])
        {
            $response['errormessage'] = 'The MAC address is not associated with the user.';
            $response['errorcode'] = ERR_MAC_ADDRESS_NOT_ASSOCIATED_WITH_USER;
            header('Content-Type: application/json', true, 404);
        }
        else
        {
            $response['errormessage'] = 'The MAC address has not yet been associated with the user, but an email has been sent.';
            $response['errorcode'] = ERR_MAC_ADDRESS_NOT_ASSOCIATED_WITH_USER_BUT_SENT;
            header('Content-Type: application/json', true, 404);
        }
        echo json_encode($response);
        exit();
    }

    usertoken_createnew($_POST['email'], $_POST['MAC'], $userrow['certificate'], $user_machine_id);
}
?>
