<?php
function clear_openssl_errors()
{
    // because there seems to be a backlog of error messages...
    while ($msg = openssl_error_string())
    {
    }
}

function verify_client_request($plaintext, $signature, $certificate)
{
    clear_openssl_errors();

    $public_res = openssl_pkey_get_public($certificate);
    if (!$public_res)
    {
        return -1;
    }
    $result = openssl_verify($plaintext, $signature, $public_res, OPENSSL_ALGO_SHA1);
    openssl_free_key($public_res);
    return $result;
}
?>
