<?php

require_once 'api/v1/dbutils.php';
require_once 'api/v1/send_email.php';
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/authorisemachine.php';
require_once 'api/v1/userhostmachine_table_queries.php';

function associatemachinewithuser()
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

    expireMachineAuthorisationLinks();

    $connection = connectdb();

    $request = userhostmachine_table_find_existing_request($_POST['email'], $MACaddress);
    if (0 == $request['id'])
    {
        createTransaction($connection);

        $url = '/api/v1/authorisemachine/';
        $url .= md5(uniqid($_POST['email'].$MACaddress, true));

        $insert_new_request = $connection->prepare('INSERT INTO UserHostMachineRequest (email,MAC,url) VALUES (:email,:MAC,:url)');
        $insert_new_request->bindParam(':email', $_POST['email'], PDO::PARAM_STR);
        $insert_new_request->bindParam(':MAC', $MACaddress, PDO::PARAM_STR);
        $insert_new_request->bindParam(':url', $url, PDO::PARAM_STR);
        $insert_new_request->execute();

        $connection->commit();

        header('Content-Type: application/json', true, 201);
    }
    else
    {
        $expired = $request['expired'];
        if (0 != $expired)
        {
            $response = array();
            $response['errorcode'] = ERR_AUTH_MACHINE_LINK_EXPIRED;
            $response['errormessage'] = 'Machine authorisation has expired.';

            header('Content-Type: application/json', true, 404);
            echo json_encode($response);
            return;
        }

        // this is like re-sending the email
        $url = $request['url'];
        header('Content-Type: application/json', true, 200);
    }

    $full_url = $_SERVER['REQUEST_SCHEME'];
    $full_url .= '://';
    $full_url .= $_SERVER['HTTP_HOST'];
    $full_url .= $url;

    $email_message = '<html>';
    $email_message .= '<body>';
    $email_message .= '<p>You have received this message because your email address is being used to license some software. If you did not expect to receive this email, please delete this message.</p>';
    $email_message .= '<p>To authorise the machine with the MAC address '.$MACaddress.', click <a href=\''.$full_url.'\'>here</a>. This link will expire after 24 hours.</p>';
    $email_message .= '<p>Once your machine has been authorised, please return to the software and continue to follow the licensing instructions.</p>';
    $email_message .= '<p>Thank you for licensing the software.</p>';
    $email_message .= '<p>Do not reply to this email. This email account is not monitored.</p>';
    $email_message .= '</body>';
    $email_message .= '</html>';

    send_email($_POST['email'], 'Software licensing machine authorisation request', $email_message);

    unset($connection);

    $response = array();
    $response['errormessage'] = 'The MAC address has not yet been associated with the user, but an email has been sent.';
    $response['errorcode'] = ERR_MAC_ADDRESS_NOT_ASSOCIATED_WITH_USER_BUT_SENT;

    header('Content-Type: application/json', true, 404);
    echo json_encode($response);
}
?>
