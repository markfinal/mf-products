<?php
require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';

function licensetype_getdata($name)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT id,duration_days FROM LicenseType WHERE name=:name');
    $query->bindParam(':name', $name, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    if (!$result)
    {
        $response = array();
        $response['errormessage'] = 'No such license type called \''.$name.'\'.';
        $response['errorcode'] = ERR_LICENSE_TYPE_UNKNOWN;

        header('Content-Type: application/json', true, 404);
        echo json_encode($response);
        exit();
    }

    unset($connection);

    return $result;
}

function licensetype_gettypename($id)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT name FROM LicenseType WHERE id=:id');
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    if (!$result)
    {
        $token = storelog('Cannot identify licence type with id '.$id);
        $response = array();
        $response['errorcode'] = ERR_SERVER_ERROR;
        $response['errortoken'] = $token;

        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
        exit();
    }

    unset($connection);

    return $result['name'];
}
?>
