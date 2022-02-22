<?php
    namespace com\example\project\webservice\api\v1_0;

    require_once __DIR__.'/../AbstractWebserviceAPI.class.php';

    require_once __DIR__.'/endpoints/LoginEndpoint.class.php';
    require_once __DIR__.'/endpoints/UsersEndpoint.class.php';

    use com\example\project\webservice\api\AbstractWebserviceAPI;

    use com\example\project\webservice\api\v1_0\endpoints\LoginEndpoint;
    use com\example\project\webservice\api\v1_0\endpoints\UsersEndpoint;

    class WebserviceAPI extends AbstractWebserviceAPI {
        public function login() { return new LoginEndpoint($this); }
        public function users() { return new UsersEndpoint($this); }

        private function isCurrentUser($user) {
            //
            return isset($_SESSION) && isset($_SESSION['WS_UID']) && $_SESSION['WS_UID'] === $user;
        }
    }
