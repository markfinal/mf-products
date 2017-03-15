<?php
require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/log.php';

function productupdate_getupdatemessage($product_id, $majorversion, $minorversion, $patchversion, $build, $phase)
{
    $connection = connectdb();

    // if there are multiple updates available, select the most recent (assuming I always increase the version number)
    // note that PDO does not allow repeating the same parameter name, so must be duplicated
    $query = $connection->prepare('SELECT link FROM ProductUpdate WHERE product=:product AND
        (
        (major_version>:major1) OR
        (major_version=:major2 AND minor_version>:minor1) OR
        (major_version=:major3 AND minor_version=:minor2 AND patch_version>:patch1) OR
        (major_version=:major4 AND minor_version=:minor3 AND patch_version=:patch2 AND build>:build1) OR
        (major_version=:major5 AND minor_version=:minor4 AND patch_version=:patch3 AND build=:build2 AND phase>:phase)
        )
        ORDER BY id DESC LIMIT 1');
    $query->bindParam(':product', $product_id, PDO::PARAM_INT);
    $query->bindParam(':major1', $majorversion, PDO::PARAM_INT);
    $query->bindParam(':major2', $majorversion, PDO::PARAM_INT);
    $query->bindParam(':major3', $majorversion, PDO::PARAM_INT);
    $query->bindParam(':major4', $majorversion, PDO::PARAM_INT);
    $query->bindParam(':major5', $majorversion, PDO::PARAM_INT);
    $query->bindParam(':minor1', $minorversion, PDO::PARAM_INT);
    $query->bindParam(':minor2', $minorversion, PDO::PARAM_INT);
    $query->bindParam(':minor3', $minorversion, PDO::PARAM_INT);
    $query->bindParam(':minor4', $minorversion, PDO::PARAM_INT);
    $query->bindParam(':patch1', $patchversion, PDO::PARAM_INT);
    $query->bindParam(':patch2', $patchversion, PDO::PARAM_INT);
    $query->bindParam(':patch3', $patchversion, PDO::PARAM_INT);
    $query->bindParam(':build1', $build, PDO::PARAM_INT);
    $query->bindParam(':build2', $build, PDO::PARAM_INT);
    $query->bindParam(':phase', $phase, PDO::PARAM_INT);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);

    unset($connection);

    if (!$result)
    {
        storelog('No newer version found against version: '.$majorversion.'.'.$minorversion.'.'.$patchversion.'.'.$build.'.'.$phase);
        return NULL;
    }

    storelog($result['link'].' is newer than version: '.$majorversion.'.'.$minorversion.'.'.$patchversion.'.'.$build.'.'.$phase);
    return $result['link'];
}

?>
