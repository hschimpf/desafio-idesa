<?php
    namespace com\example\project\webservice\api\v2_0;

    require_once __DIR__.'/../v1_0/WebserviceAPI.class.php';

    require_once __DIR__.'/endpoints/LoginEndpoint.class.php';
    require_once __DIR__.'/endpoints/UsersEndpoint.class.php';
    require_once __DIR__.'/endpoints/ClientsEndpoint.class.php';

    use com\example\project\webservice\api\v1_0\WebserviceAPI as WebserviceAPI_v1_0;

    use com\example\project\webservice\api\v2_0\endpoints\LoginEndpoint;
    use com\example\project\webservice\api\v2_0\endpoints\UsersEndpoint;
    use com\example\project\webservice\api\v2_0\endpoints\ClientsEndpoint;

    class WebserviceAPI extends WebserviceAPI_v1_0 {
        public function login() { return new LoginEndpoint($this); }
        public function users() { return new UsersEndpoint($this); }
        public function clients() { return new ClientsEndpoint($this); }
    }
