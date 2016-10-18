<?php

require_once 'api/v1/dbutils.php';
require_once 'api/v1/userhostmachine_table_queries.php';
require_once 'api/v1/log.php';

function authorisemachine($url)
{
    expireMachineAuthorisationLinks();

    $connection = connectdb();

    $message = '<html>';
    $message .= '<body>';

    $html_suffix = '</body>';
    $html_suffix .= '</html>';

    $find_existing_request = $connection->prepare('SELECT id,email,MAC,expired FROM UserHostMachineRequest WHERE url=:url');
    $find_existing_request->bindParam(':url', $url, PDO::PARAM_STR);
    $find_existing_request->execute();
    if (0 == $find_existing_request->rowCount())
    {
        $message .= '<p>Invalid machine authorisation.</p>';
        $message .= $html_suffix;

        header('Content-Type: text/html', true, 404);
        echo $message;
        exit();
    }

    $request = $find_existing_request->fetch(PDO::FETCH_ASSOC);
    if ($request['expired'] != 0)
    {
        $message .= '<p>Machine authorisation link has expired.</p>';
        $message .= $html_suffix;

        header('Content-Type: text/html', true, 404);
        echo $message;
        exit();
    }

    $fetch_user_id = $connection->prepare('SELECT id FROM User WHERE email=:email');
    $fetch_user_id->bindParam(':email', $request['email'], PDO::PARAM_STR);
    $fetch_user_id->execute();
    $user_id = $fetch_user_id->fetchColumn(0);

    $fetch_host_id = $connection->prepare('SELECT id FROM Host WHERE MAC=:MAC');
    $fetch_host_id->bindParam(':MAC', $request['MAC'], PDO::PARAM_STR);
    $fetch_host_id->execute();
    $host_id = $fetch_host_id->fetchColumn(0);

    createTransaction($connection);

    $insert_user_machine_association = $connection->prepare('INSERT INTO UserHostMachine (user,host) VALUES (:user,:host)');
    $insert_user_machine_association->bindParam(':user', $user_id, PDO::PARAM_INT);
    $insert_user_machine_association->bindParam(':host', $host_id, PDO::PARAM_INT);
    try
    {
        $insert_user_machine_association->execute();
    }
    catch (PDOException $e)
    {
        throw $e;
    }

    $connection->commit();

    // TODO: write some nice HTML
    $message .= '<p>Machine with MAC address '.$request['MAC'].' has been authorised for use for '.$request['email'].'</p>';
    $message .= $html_suffix;

    storelog("Machine has been authorised", $user_id, $host_id);

    header('Content-Type: text/html', true, 200);
    echo $message;

    // don't delete the requests immediately
    // TODO:
    expireSpecificMachineAuthorisationLink($connection,$request['id']);
    /*
    $delete_request = $connection->prepare("DELETE FROM UserHostMachineRequest WHERE Id=:id");
    $delete_request->bindParam(':id', $request['id'], PDO::PARAM_INT);
    try
    {
        $delete_request->execute();
    }
    catch (PDOException $e)
    {
        throw $e;
    }
    */

    unset($connection);
}
?>
