<?php
    function dd($object) {
        // replace header
        if (substr(php_sapi_name(), 0, 3) !== 'cli') header('Content-Type: application/json');
        // dump object and die
        die(json_encode($object)."\n");
    }