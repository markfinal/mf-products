<?php
require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';

function userhostmachine_table_get_id($userid, $hostid)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT id FROM UserHostMachine WHERE user=:user AND host=:host');
    $query->bindParam(':user', $userid, PDO::PARAM_INT);
    $query->bindParam(':host', $hostid, PDO::PARAM_INT);
    $query->execute();
    $user_machine_id = $query->fetchColumn(0);

    unset($connection);

    return $user_machine_id;
}

function userhostmachine_table_find_existing_request($email, $MAC)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT id,url,expired FROM UserHostMachineRequest WHERE email=:email AND MAC=:MAC');
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':MAC', $MAC, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);

    unset($connection);

    return $result;
}

function userhostmachine_table_getuserandhost($id)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT user,host FROM UserHostMachine WHERE id=:id');
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);

    unset($connection);

    return $result;
}

function userhostmachine_table_get_num_usermachines($userid)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT COUNT(id) FROM UserHostMachine WHERE user=:user');
    $query->bindParam(':user', $userid, PDO::PARAM_INT);
    $query->execute();
    $num_user_machines = $query->fetchColumn(0);

    unset($connection);

    return $num_user_machines;
}

function expireSpecificMachineAuthorisationLink($connection,$id)
{
    createTransaction($connection);
    $update_expired_requests = $connection->prepare('UPDATE UserHostMachineRequest SET expired=1 WHERE id=:id');
    $update_expired_requests->bindParam(':id', $id, PDO::PARAM_INT);
    $update_expired_requests->execute();
    $connection->commit();
}

function expireMachineAuthorisationLinks()
{
    $connection = connectdb();

    $interval = '24 HOUR';
    $find_expired_requests = $connection->prepare('SELECT id FROM UserHostMachineRequest WHERE created < NOW() - INTERVAL '.$interval.' AND expired=0');
    $find_expired_requests->execute();
    if ($find_expired_requests->rowCount() > 0)
    {
        createTransaction($connection);
        $update_expired_requests = $connection->prepare('UPDATE UserHostMachineRequest SET expired=1 WHERE id=:id');
        while ($row = $find_expired_requests->fetch(PDO::FETCH_ASSOC))
        {
            error_log('Request '.$row['id'].' has now expired');
            $update_expired_requests->bindParam(':id', $row['id'], PDO::PARAM_INT);
            $update_expired_requests->execute();
        }

        $connection->commit();
    }

    unset($connection);
}
?>
