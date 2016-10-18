<?php
require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/log.php';

function productupdate_getupdatemessage($product_id, $majorversion, $minorversion, $patchversion, $build, $phase)
{
    $connection = connectdb();

    // if there are multiple updates available, select the most recent (assuming I always increase the version number)
    $query = $connection->prepare('SELECT message FROM ProductUpdate WHERE product=:product AND (major_version>:major OR minor_version>:minor OR patch_version>:patch OR build>:build OR phase>:phase) ORDER BY id DESC LIMIT 1');
    $query->bindParam(':product', $product_id, PDO::PARAM_INT);
    $query->bindParam(':major', $majorversion, PDO::PARAM_INT);
    $query->bindParam(':minor', $minorversion, PDO::PARAM_INT);
    $query->bindParam(':patch', $patchversion, PDO::PARAM_INT);
    $query->bindParam(':build', $build, PDO::PARAM_INT);
    $query->bindParam(':phase', $phase, PDO::PARAM_INT);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);

    unset($connection);

    if (!$result)
    {
        return NULL;
    }

    return $result['message'];
}

?>
