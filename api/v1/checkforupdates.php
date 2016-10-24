<?php
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/verifyclientdata.php';
require_once 'api/v1/productupdate_table_queries.php';

function checkforupdates()
{
    $json = verifyclientdata();

    $product_id = product_getid($json['productname']);
    $update_link = productupdate_getupdatemessage(
        $product_id,
        $json['product_majorversion'],
        $json['product_minorversion'],
        $json['product_patchversion'],
        $json['product_build'],
        $json['product_phase']);

    $response = array();
    if (is_null($update_link))
    {
        $response['errorcode'] = ERR_PRODUCTUPDATE_ALREADY_UP_TO_DATE;
        $response['errormessage'] = 'There are no updates available.';
    }
    else
    {
        $response['errorcode'] = ERR_NONE;
        $response['errormessage'] = $update_link;
    }

    header('Content-Type: application/json', true, 200);
    echo json_encode($response);
}
?>
