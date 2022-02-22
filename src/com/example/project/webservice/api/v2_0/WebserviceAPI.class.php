<?php
    namespace com\example\project\webservice\api\v2_0;

    require_once __DIR__.'/../v1_0/WebserviceAPI.class.php';

    require_once __DIR__.'/endpoints/UsersEndpoint.class.php';

    use com\example\project\webservice\api\v1_0\WebserviceAPI as WebserviceAPI_v1_0;

    use com\example\project\webservice\api\v2_0\endpoints\UsersEndpoint;

    class WebserviceAPI extends WebserviceAPI_v1_0 {
        public function users() { return new UsersEndpoint($this); }
    }
