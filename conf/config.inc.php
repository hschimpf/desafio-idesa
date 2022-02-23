<?php
    // redirect parser
    foreach ($_SERVER as $key => $value)
        if (substr($key, 0, 9) == 'REDIRECT_') {
            $_SERVER[substr($key, 9, strlen($key))] = $value;
            unset($_SERVER[$key]);
        }

    // ordenamos $_FILES si recibimos
    if (isset($_FILES) && count($_FILES) > 0) {
        // sort each file
        foreach ($_FILES as $field => $field_data) {
            // sort $files array into format: [ { name: <string>, type: <string>, tmp_name: <string>, error: <int>, size: <int> }, ... ]
            if (gettype($field_data['name']) == 'array') {
                //
                $files_new = [];
                for ($i = 0; $i < count($field_data['name']); $i++)
                    foreach (array_keys($field_data) as $key)
                        $files_new[$i][$key] = $field_data[$key][$i];
                // replace $files with sorted array
                $_FILES[$field] = $files_new;
            } else
                // convertimos el field en array
                $_FILES[$field] = [ $field_data ];
        }
    }

    // develop flag
    define('DEVELOP', isset($_SERVER['DEVELOP']) && $_SERVER['DEVELOP'] === 'true'); unset($_SERVER['DEVELOP']);

    // Configuracion PHP
    error_reporting(E_ALL);
    ini_set('display_errors', DEVELOP);

    // PHP Configuration
    date_default_timezone_set('America/Asuncion');
    setlocale(LC_ALL, 'es-PY');

    // ruta base
    define('SERVICE_PATH', rtrim(dirname(__DIR__), '/'));

    // directorio para los include_ require_ en /
    set_include_path(SERVICE_PATH);

    // version
    define('VERSION', '2.1.0');

    // conexion con BBDD
    define('DB_HOST', isset($_SERVER['DB_HOST']) ? $_SERVER['DB_HOST'] : 'localhost'); unset($_SERVER['DB_HOST']);
    define('DB_PORT', isset($_SERVER['DB_PORT']) ? $_SERVER['DB_PORT'] : 3306); unset($_SERVER['DB_PORT']);
    define('DB_USER', isset($_SERVER['DB_USER']) ? $_SERVER['DB_USER'] : null); unset($_SERVER['DB_USER']);
    define('DB_PASS', isset($_SERVER['DB_PASS']) ? $_SERVER['DB_PASS'] : null); unset($_SERVER['DB_PASS']);
    define('DB_DDBB', isset($_SERVER['DB_DDBB']) ? $_SERVER['DB_DDBB'] : null); unset($_SERVER['DB_DDBB']);

    // conexion con WS externo
    define('WS_HOST',       isset($_SERVER['WS_HOST']) ? rtrim($_SERVER['WS_HOST'], '/') : null); unset($_SERVER['WS_HOST']);
    define('WS_HT_USER',    isset($_SERVER['WS_HT_USER']) ? $_SERVER['WS_HT_USER'] : null); unset($_SERVER['WS_HT_USER']);
    define('WS_HT_PASS',    isset($_SERVER['WS_HT_PASS']) ? $_SERVER['WS_HT_PASS'] : null); unset($_SERVER['WS_HT_PASS']);
    define('WS_USER',       isset($_SERVER['WS_USER']) ? $_SERVER['WS_USER'] : null); unset($_SERVER['WS_USER']);
    define('WS_PASS',       isset($_SERVER['WS_PASS']) ? $_SERVER['WS_PASS'] : null); unset($_SERVER['WS_PASS']);
    define('WS_COOKIES',    SERVICE_PATH.'/tmp/.webservice-session.cookies');

    //
    define('LOCK_FAILED', isset($_SERVER['LOCK_FAILED']) && $_SERVER['LOCK_FAILED'] === 'true'); unset($_SERVER['LOCK_FAILED']);

    //
    define('PASSWORD_STRENGTH', isset($_SERVER['PASSWORD_STRENGTH']) && $_SERVER['PASSWORD_STRENGTH'] === 'true'); unset($_SERVER['PASSWORD_STRENGTH']);

    //
    define('SECURITY_DB', SERVICE_PATH.'/conf/sqlite/security.db');

    //
    define('LOGS_DIR',      SERVICE_PATH.'/logs');
