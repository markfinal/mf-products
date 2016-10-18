<?php
require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/log.php';

function user_table_get_id($email)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT id,certificate,maxmachines,revoke_reason FROM User WHERE email=:email');
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    if (!$result)
    {
        $response = array();
        $response['errormessage'] = 'The email address has not been registered.';
        $response['errorcode'] = ERR_EMAIL_UNREGISTERED;

        header('Content-Type: application/json', true, 404);
        echo json_encode($response);
        exit();
    }
    else if (!is_null($result['revoke_reason']))
    {
        storelog('User with email '.$email.' has been revoked because '.$result['revoke_reason']);
        $response = array();
        $response['errormessage'] = 'User is unable to be licensed';
        $response['errorcode'] = ERR_EMAIL_REFUSED;

        header('Content-Type: application/json', true, 403);
        echo json_encode($response);
        exit();
    }

    unset($connection);

    return $result;
}

function user_table_getcertificate($id)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT certificate FROM User WHERE id=:id');
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);

    unset($connection);

    return $result['certificate'];
}
?>
