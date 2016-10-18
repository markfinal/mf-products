<?php
require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';

function product_getid($name)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT id FROM Product WHERE name=:name');
    $query->bindParam(':name', $name, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    if (!$result)
    {
        $response = array();
        $response['errormessage'] = 'No such product called \''.$name.'\'.';
        $response['errorcode'] = ERR_LICENSE_PRODUCT_UNKNOWN;

        header('Content-Type: application/json', true, 404);
        echo json_encode($response);
        exit();
    }

    unset($connection);

    return $result['id'];
}
?>
