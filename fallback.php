<?php
require_once 'api/v1/errorcodes.php';
require_once 'api/v1/log.php';

// TODO: is this shutdown function needed now that there is an exception handler?
register_shutdown_function( "fatal_handler" );
function fatal_handler()
{
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();

    if (!is_null($error))
    {
        $errno   = $error['type'];
        $errfile = $error['file'];
        $errline = $error['line'];
        $errstr  = $error['message'];
        $errmsg = "Error no $errno: $errstr in $errfile at line $errline";
        $token = storelog($errmsg);

        // avoids errors like 'unknown variable: message' from the xdebug wrapper
        if (!startsWith($errfile, 'xdebug'))
        {
            $response = array();
            $response['errorcode'] = ERR_SERVER_ERROR;
            $response['errortoken'] = $token;

            header('Content-Type: application/json', true, 500);
            echo json_encode($response);
            exit();
        }
    }
}

// note that in PHP 7.0 this also copes with Errors, such as uncaught errors
set_exception_handler('exception_handler');
function exception_handler(Throwable $e)
{
    $token = storelog($e);
    $response = array();
    $response['errorcode'] = ERR_SERVER_ERROR;
    $response['errortoken'] = $token;

    header('Content-Type: application/json', true, 500);
    echo json_encode($response);
    exit();
}

try
{
    require_once 'api/v1/send_email.php';
    require_once 'api/v1/validateuser.php';
    require_once 'api/v1/registeruser.php';
    require_once 'api/v1/registermacaddress.php';
    require_once 'api/v1/associatemachinewithuser.php';
    require_once 'api/v1/stringutils.php';
    require_once 'api/v1/dynamicurl.php';
    require_once 'api/v1/licenserequest.php';
    require_once 'api/v1/licensereturn.php';
    require_once 'api/v1/checkforupdates.php';
    require_once 'api/v1/providefeedback.php';

    switch ($_SERVER['REQUEST_URI'])
    {
        case '/api/v1/validateuser':
            validateuser();
            break;

        case '/api/v1/registeruser':
            registeruser();
            break;

        case '/api/v1/registermacaddress':
            registermacaddress();
            break;

        case '/api/v1/associatemachinewithuser':
            associatemachinewithuser();
            break;

        case '/api/v1/licenserequest':
            licenserequest();
            break;

        case '/api/v1/licensereturn':
            licensereturn();
            break;

        case '/api/v1/checkforupdate':
            checkforupdates();
            break;

        case '/api/v1/feedback':
            providefeedback();
            break;

        default:
            {
                if (!isdynamicurl($_SERVER['REQUEST_URI']))
                {
                    $response = array();
                    $response['errormessage'] = 'Unrecognized path: '.$_SERVER['REQUEST_URI'].'.';

                    header($_SERVER['SERVER_PROTOCOL'].' 404 not found', true, 404);
                    header('Content-Type: application/json', true);
                    echo json_encode($response);
                }
            }
    }
}
catch (PDOException $e)
{
    $token = storelog($e);
    $response = array();
    $response['errorcode'] = ERR_MYSQL_ERROR;
    $response['errortoken'] = $token;

    header('Content-Type: application/json', true, 500);
    echo json_encode($response);
}
catch (\ParseError $e)
{
    $token = storelog($e);
    $response = array();
    $response['errorcode'] = ERR_SERVER_ERROR;
    $response['errortoken'] = $token;

    header('Content-Type: application/json', true, 500);
    echo json_encode($response);
}
catch (Exception $e)
{
    $token = storelog($e);
    $response = array();
    $response['errorcode'] = ERR_SERVER_ERROR;
    $response['errortoken'] = $token;

    header('Content-Type: application/json', true, 500);
    echo json_encode($response);
}
?>
