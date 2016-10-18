<?php
require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/log.php';
require_once 'api/v1/user_table_queries.php';

function licensesession_create($license_id, $lic_type_name, $user_id, $product_name, $product_version, $os)
{
    $connection = connectdb();

    $session_token = md5(uniqid($license_id.$lic_type_name.$product_name, true));

    createTransaction($connection);

    $query = $connection->prepare('INSERT INTO LicenseSession (license,session_token,product_version,operating_system) VALUES (:license_id,:session_token,:product_version,:os)');
    $query->bindParam(':license_id', $license_id, PDO::PARAM_INT);
    $query->bindParam(':session_token', $session_token, PDO::PARAM_STR);
    $query->bindParam(':product_version', $product_version, PDO::PARAM_STR);
    $query->bindParam(':os', $os, PDO::PARAM_STR);
    $query->execute();
    $session_id = $connection->lastInsertId();

    $certificate = user_table_getcertificate($user_id);

    $public_res = openssl_pkey_get_public($certificate);

    // Note: this padding type must match that in the C++
    $padding = OPENSSL_PKCS1_OAEP_PADDING;
    //$padding = OPENSSL_PKCS1_PADDING;
    if (!openssl_public_encrypt($session_token, $encrypted, $public_res, $padding))
    {
        error_log(openssl_error_string());
    }
    openssl_free_key($public_res);

    // after encryption, commit to DB
    $connection->commit();

    unset($connection);

    storelog('Created session token \''.$session_token.'\' for '.$product_name.' v'.$product_version.' on '.$os, $user_id, NULL, $session_id);

    return $encrypted;
}

function licensesession_getdata_ifvalid($session)
{
    $connection = connectdb();
    $query = $connection->prepare('SELECT * FROM LicenseSession WHERE session_token=:session_token AND ended IS NULL');
    $query->bindParam(':session_token', $session, PDO::PARAM_STR);
    $query->execute();
    if ($query->rowCount() == 0)
    {
        return NULL;
    }
    else
    {
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result;
    }
}

function licensesession_end($session_id)
{
    $connection = connectdb();

    createTransaction($connection);
    $query = $connection->prepare('UPDATE LicenseSession SET ended=NOW() WHERE id=:id');
    $query->bindParam(':id', $session_id, PDO::PARAM_INT);
    $query->execute();

    $connection->commit();

    unset($connection);

    storelog('Ended session', NULL, NULL, $session_id);
}
?>
