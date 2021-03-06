<?php
    use net\hdssolutions\api\APIUtils;

    // ERROR CODES
    define('WS_NO_ERROR',				  0);
    define('WS_INVALID_ENDPOINT_ERROR',   1);
    define('WS_INVALID_DATA_ERROR',       2);
    define('WS_TIMEOUT_ERROR',            4);
    define('WS_SMTP_ERROR',               8);
    define('WS_SMS_ERROR',               16);
    define('WS_UNKNOWN_ERROR',           32);
    define('WS_BAD_JSON_DATA',           64);
    define('WS_DATABASE_ERROR',         128);
    define('WS_ACCESS_DENIED',          256);
    define('WS_EXTERNAL_ERROR',         512);

    /**
     * Primary keys definitions
     */
    // ADM_*
    define('ADM_USER_PK',       APIUtils::makeFK('user_id'));

    // SYS_*
    define('SYS_CLIENT_PK',     APIUtils::makeFK('client_id'));

    // DAT_*
    define('DAT_COUNTRY_PK',    APIUtils::makeFK('country_id'));

    // AUC_*
    define('AUC_AUCTION_PK',    APIUtils::makeFK('auction_id'));
    define('AUC_BATCH_PK',      APIUtils::makeFK('batch_auction', 'batch_id'));
