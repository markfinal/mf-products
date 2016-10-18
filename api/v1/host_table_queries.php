<?php
require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/log.php';

function host_table_insert($MAC)
{
    $connection = connectdb();

    createTransaction($connection);

    $insert_mac_address = $connection->prepare('INSERT INTO Host (MAC) VALUES (:MAC)');
    $insert_mac_address->bindParam(':MAC', $MAC, PDO::PARAM_STR);
    try
    {
        $insert_mac_address->execute();
    }
    catch (PDOException $e)
    {
        if (MYSQL_ERRCODE_DUPLICATE_KEY === $e->errorInfo[1])
        {
            $response = array();
            $response['errormessage'] = 'The MAC address is already in use.';
            $response['errorcode'] = ERR_MAC_ADDRESS_ALREADY_INUSE;

            header('Content-Type: application/json', true, 409);
            echo json_encode($response);
            return;
        }
        throw $e;
    }
    $mac_address_id = intval($connection->lastInsertId());

    $connection->commit();
    storelog('Registered MAC address '.$MAC, $mac_address_id);

    $response = array();
    $response['mac_address_id'] = $mac_address_id;

    header('Content-Type: application/json', true, 201);
    echo json_encode($response);

    unset($connection);
}

function host_table_get_id($MAC, $num_user_machines, $max_machines)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT id,revoke_reason FROM Host WHERE MAC=:MAC');
    $query->bindParam(':MAC', $MAC, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    if (!$result)
    {
        // check that adding one more machine will not exceed the quota
        if ($num_user_machines + 1 > $max_machines)
        {
            $response = array();
            $response['errormessage'] = 'Quota of machines has been reached.';
            $response['errorcode'] = ERR_INSUFFICIENT_FREE_MAC_ADDRESSES;

            header('Content-Type: application/json', true, 412);
            echo json_encode($response);
            exit();
        }

        $response = array();
        $response['errormessage'] = 'The MAC address has not been registered.';
        $response['errorcode'] = ERR_MAC_ADDRESS_NOT_REGISTERED;

        header('Content-Type: application/json', true, 404);
        echo json_encode($response);
        exit();
    }
    else if (!is_null($result['revoke_reason']))
    {
        storelog('Host with MAC '.$MAC.' has been revoked because '.$result['revoke_reason']);
        $response = array();
        $response['errormessage'] = 'Host is unable to be licensed';
        $response['errorcode'] = ERR_MAC_ADDRESS_REFUSED;

        header('Content-Type: application/json', true, 403);
        echo json_encode($response);
        exit();
    }

    unset($connection);

    return $result['id'];
}
?>
