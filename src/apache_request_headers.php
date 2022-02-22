<?php
    if (!function_exists('apache_request_headers')) {
        function apache_request_headers() {
            $arh = [];
            $regex = '/\AHTTP_/';
            // foreach $_SERVER
            foreach($_SERVER as $key => $val) {
                if (preg_match($regex, $key)) {
                    // remove regex from variable
                    $arh_key = preg_replace($regex, '', $key);
                    $rx_matches = [];
                    // convert underscore to dash and Camelcase the variable
                    $rx_matches = explode('_', $arh_key);
                    if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                        foreach ($rx_matches as $ak_key => $ak_val)
                            $rx_matches[$ak_key] = ucfirst($ak_val);
                        $arh_key = implode('-', $rx_matches);
                    }
                    $arh[$arh_key] = $val;
                }
            }
            // return parsed variables
            return $arh;
        }
    }