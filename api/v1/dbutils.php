<?php
require_once 'api/v1/log.php';

function connectdb()
{
    $path = realpath(__DIR__.'/../../private/sqlpasswd');
    $password = explode("\n", file_get_contents($path));

    $connection = new PDO('mysql:host=localhost;dbname=markfina_entitlements;charset=utf8', 'markfina_php', $password[0]);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $connection;
}

function createTransaction($connection)
{
    if (!$connection->beginTransaction())
    {
        $token = storelog('Could not start a transaction');
        $response = array();
	    $response['errorcode'] = ERR_SERVER_ERROR;
        $response['errortoken'] = $token;

        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
        exit();
    }
}
?>
