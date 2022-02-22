<?php
    // cargamos las configuraciones
    require_once '../inc/oo-rest-api/conf/config.inc.php';
    require_once '../conf/config.inc.php';

    // local functions
    require_once 'src/apache_request_headers.php';
    require_once 'src/obj_slice.func.php';
    require_once 'src/str_trim.func.php';
    require_once 'src/dd.func.php';

    // librerias de la libreria ObjectOriented Rest API
    require_once 'inc/oo-rest-api/net/hdssolutions/api/APIUtils.php';
    require_once 'inc/oo-rest-api/net/hdssolutions/api/performance/Performance.class.php';
    require_once 'inc/oo-rest-api/net/hdssolutions/php/rest/AbstractObjectOrientedRestAPI.class.php';
    require_once 'inc/oo-rest-api/net/hdssolutions/php/rest/endpoint/AbstractObjectOrientedEndpoint.class.php';

    // cargamos las liberias
    require_once 'inc/security-layer/net/hdssolutions/php/security/SecurityLayer.class.php';
    require_once 'inc/php-curl-class/net/hdssolutions/php/net/Curl.class.php';
    require_once 'inc/db-class/net/hdssolutions/php/dbo/DB.php';

    // cargamos las constantes
    require_once 'src/com/example/project/constants.php';

    //
    require_once 'src/com/example/project/webservice/api/Utils.class.php';

    // cargamos el Logger
    require_once 'src/com/example/project/webservice/api/logger/Logger.class.php';

    // cargamos los API class
    require_once 'src/com/example/project/webservice/api/v1_0/WebserviceAPI.class.php';

    // cargamos el Session handler
    require_once 'src/com/example/project/webservice/session/WebserviceSession.class.php';

    use net\hdssolutions\php\dbo\DB;
    use net\hdssolutions\api\APIUtils;
    use com\example\project\webservice\api\v1_0\WebserviceAPI as WS_1_0;
    use com\example\project\webservice\session\WebserviceSession;
    use com\example\project\webservice\api\logger\Logger;

    // redirect errors to API handler
    set_error_handler(function($severity, $message, $file, $line, array $context) {
        // suppresed error with @-operator
        if (error_reporting() === 0) return false;
        // redirect to API error handler
        WS_2_0::error_handler($severity, $message, $file, $line, $context);
    });

    // seteamos los datos de conexion
    DB::setParams(DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_DDBB);

    // set Logger params
    Logger::config([
        'directory' => LOGS_DIR,
        'filename'  => 'webservice.log',
    ]);

    // get requested API version
    $requestedVersion = APIUtils::requestedVersion();
    switch ($requestedVersion) {
        case null: // execute last version by default (null)
        case 1.0: WS_1_0::init();
            break;

        default: throw new Exception('Invalid requested API Version: v'.$requestedVersion, 400);
    }
