<?php
require_once 'api/v1/dbutils.php';
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/log.php';
require_once 'api/v1/userhostmachine_table_queries.php';

function usertoken_deleteexisting($userhost_id)
{
    $connection = connectdb();

    createTransaction($connection);

    $query = $connection->prepare('DELETE FROM AccessToken WHERE userhost=:userhost_id');
    $query->bindParam(':userhost_id', $userhost_id, PDO::PARAM_INT);
    $query->execute();

    $num_deleted = $query->rowCount();
    if ($num_deleted > 0)
    {
        storelog('There were '.$num_deleted.' user tokens deleted for the current user');
    }

    $connection->commit();

    unset($connection);
}

function usertoken_createnew($email,$MAC,$certificate,$userhost_id)
{
    // if a token for this userhost pair already exists, then the user has not
    // used it to acquire a license - delete it, and give out a new token
    usertoken_deleteexisting($userhost_id);

    // the user is now authorised to use software on this machine
    // return a token allowing access to licensing code
    // only the owner of the private key will be able to extract the token
    $token = md5(uniqid($email.$MAC, true));
 
    $connection = connectdb();

    createTransaction($connection);

    $query = $connection->prepare('INSERT INTO AccessToken (token,userhost) VALUES (:token,:userhost_id)');
    $query->bindParam(':token', $token, PDO::PARAM_STR);
    $query->bindParam(':userhost_id', $userhost_id, PDO::PARAM_INT);
    $query->execute();
    $token_id = $connection->lastInsertId();

    $public_res = openssl_pkey_get_public($certificate);

    // Note: this padding type must match that in the C++
    $padding = OPENSSL_PKCS1_OAEP_PADDING;
    //$padding = OPENSSL_PKCS1_PADDING;
    if (!openssl_public_encrypt($token, $encrypted_token, $public_res, $padding))
    {
        error_log(openssl_error_string());
    }
    openssl_free_key($public_res);

    // after the OpenSSL code, commit to the DB
    $connection->commit();

    $response = array();
    $response['accesstoken'] = base64_encode($encrypted_token);
    $response['length'] = strlen($encrypted_token);

    header('Content-Type: application/json', true, 200);
    echo json_encode($response);

    $user_and_host = userhostmachine_table_getuserandhost($userhost_id);
    storelog('Access token '.$token_id.' has been sent', $user_and_host['user'], $user_and_host['host']);

    unset($connection);
}

function usertoken_getdata_ifvalid($token)
{
    $connection = connectdb();
    $query = $connection->prepare('SELECT id,userhost FROM AccessToken WHERE token=:token');
    $query->bindParam(':token', $token, PDO::PARAM_STR);
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
?>
