<?php

require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/log.php';

function registeruser()
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
    if (!array_key_exists('certificate', $_POST) || empty($_POST['certificate']))
    {
        $response = array();
        $response['errormessage'] = 'A certificate must be provided.';
        $response['errorcode'] = ERR_CERTIFICATE_NOT_SPECIFIED;

        header('Content-Type: application/json', true, 400);
        echo json_encode($response);
        return;
    }

    $connection = connectdb();

    createTransaction($connection);

    $insert_user = $connection->prepare('INSERT INTO User (email,certificate) VALUES (:email,:certificate)');
    $insert_user->bindParam(':email', $_POST['email'], PDO::PARAM_STR);
    $insert_user->bindParam(':certificate', $_POST['certificate'], PDO::PARAM_STR);
    try
    {
        $insert_user->execute();
    }
    catch (PDOException $e)
    {
        if (MYSQL_ERRCODE_DUPLICATE_KEY === $e->errorInfo[1])
        {
            $response = array();
            $response['errormessage'] = 'The email address has already been registered.';
            $response['errorcode'] = ERR_EMAIL_ALREADY_INUSE;

            header('Content-Type: application/json', true, 409);
            echo json_encode($response);
            return;
        }
        throw $e;
    }
    $userid = intval($connection->lastInsertId());
    $connection->commit();
    storelog('Registered email '.$_POST['email'], $userid);

    $response = array();
    $response['userid'] = $userid;

    header('Content-Type: application/json', true, 201);
    echo json_encode($response);

    unset($connection);
}
?>
