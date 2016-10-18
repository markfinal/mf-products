<?php
require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/log.php';
require_once 'api/v1/licensetype_table_queries.php';

function license_hasproductlicense($user_id,$product_id)
{
    $connection = connectdb();

    // TODO: check that the product name is correct

    $query = $connection->prepare('SELECT id FROM License WHERE user=:userid AND product=:product_id');
    $query->bindParam(':userid', $user_id, PDO::PARAM_INT);
    $query->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $query->execute();
    $results = $query->fetch(PDO::FETCH_ASSOC);

    unset($connection);

    if (!$results)
    {
        return NULL;
    }
    return $results;
}

function license_validate($user_id,$product_id)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT id,type,TIMESTAMPDIFF(DAY,NOW(),TIMESTAMPADD(DAY,duration_days,created)) FROM License WHERE user=:userid AND product=:product_id AND NOW() <= TIMESTAMPADD(DAY,duration_days,created)');
    $query->bindParam(':userid', $user_id, PDO::PARAM_INT);
    $query->bindParam(':product_id', $product_id, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetch(PDO::FETCH_ASSOC);

    unset($connection);

    if (!$results)
    {
        return NULL;
    }
    return $results;
}

function license_granttrial($user_id,$product_id)
{
    $trial_license_data = licensetype_getdata('Trial');

    $connection = connectdb();

    createTransaction($connection);

    $query = $connection->prepare('INSERT INTO License (user,type,duration_days,product) VALUES (:user_id,:type,:duration,:product_id)');
    $query->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $query->bindParam(':type', $trial_license_data['id'], PDO::PARAM_INT);
    $query->bindParam(':duration', $trial_license_data['duration_days'], PDO::PARAM_INT);
    $query->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $query->execute();

    $new_license_id = $connection->lastInsertId();

    $connection->commit();

    unset($connection);

    return $new_license_id;
}

function license_getuserid($id)
{
    $connection = connectdb();

    $query = $connection->prepare('SELECT user FROM License WHERE id=:id');
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();
    $results = $query->fetch(PDO::FETCH_ASSOC);

    unset($connection);

    if (!$results)
    {
        return NULL;
    }
    return $results['user'];
}
?>
