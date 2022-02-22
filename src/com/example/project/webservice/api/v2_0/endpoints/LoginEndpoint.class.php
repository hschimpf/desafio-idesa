<?php
    namespace com\example\project\webservice\api\v2_0\endpoints;

    require_once __DIR__.'/../../v1_0/endpoints/LoginEndpoint.class.php';

    use Exception;
    use PDO;

    use com\example\project\webservice\api\v1_0\endpoints\LoginEndpoint as LoginEndpoint_v1_0;

    class LoginEndpoint extends LoginEndpoint_v1_0 {

        public function post(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // no logged user by default
            $login = null;
            try {
                // execute POST/login to validate credencials
                // this will execute v2.0 GET/users, so User.status attribute will be present
                $login = parent::post($verb, $args, $data, true);

                // new in v2.0:
                // check if user.status is active
                if (!$login->success || $login->result->status !== 'active') {
                    // force to close session (even if credentials were valid)
                    $this->sLayer()->logout();
                    // return user inactive message
                    throw new Exception('User is not active', 403);
                }

                // return login response
                return $this->output([
                    'success'   => $login->success,
                    'logged'    => $login->logged,
                    'result'    => $login->result,
                ], $local);
            } catch (Exception $e) {
                // close session on error
                $this->sLayer()->logout();
                // redirect Exception
                throw $e;
            }
        }

    }
