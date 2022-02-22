<?php
    namespace com\example\project\webservice\api\v1_0\endpoints;

    require_once __DIR__.'/../../AbstractAPIEndpoint.class.php';
    require_once __DIR__.'/../../connector/EndpointConnector.class.php';

    use net\hdssolutions\api\performance\Performance;
    use net\hdssolutions\api\APIUtils;
    use net\hdssolutions\php\dbo\DB;
    use com\example\project\webservice\api\logger\Logger;
    use Exception;
    use PDO;

    use com\example\project\webservice\api\AbstractAPIEndpoint;

    class LoginEndpoint extends AbstractAPIEndpoint {
        public function get(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // invalid request with verb
            if ($verb !== null) throw new Exception('Invalid request', 400);
            // move verb param to args
            array_unshift($args, $verb);
            // get user data
            $user = isset($_SESSION['WS_UID']) ? parent::users()->get($_SESSION['WS_UID'], $args, $data, true) : null;
            // validate user data
            if ($user !== null) $user = $user->result;
            // remove user pass
            unset($user->password);
            // validate local request
            if (!$local) unset($user->raw);
            // return login status
            return $this->output([
                'success'   => true,
                'logged'    => isset($_SESSION['WS_UID']),
                'result'    => $user
            ], $local);
        }

        public function post(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // validate data
            if (!isset($data->user) || !isset($data->pass)) {
                // force session close
                $this->sLayer()->logout();
                // return exception error
                throw new Exception('Usuario y/o contraseña incorrecta', 403);
            }

            //
            Logger::debug('POST/login', 'Finding user');
            // check login with email or username
            $user = parent::users()->get(null, [], (object)[
                // find user by email or by username based on @ presence
                strpos($data->user, '@') ? 'email' : 'username' => $data->user
            ], true);

            // check for errors
            if (!$user->success) return $this->output($user, $local);

            // verificamos si existe el usuario
            if (count($user->result) !== 1) {
                //
                Logger::debug('POST/login', 'User not found!');
                // cerramos la session
                $this->sLayer()->logout();
                // retornamos una excepcion
                throw new Exception('Usuario y/o contraseña incorrecta', 403);
            }

            // get user data
            $user = $user->result[0];

            // check locked user
            if (LOCK_FAILED && $user->retry >= 5) {
                //
                Logger::debug('POST/login', 'User locked!');
                // cerramos la session
                $this->sLayer()->logout();
                // retornamos una excepcion
                throw new Exception('El usuario '.$user->username.' ('.$user->name.') ha sido bloqueado por varios intentos fallidos de acceso', 403);
            }

            // verificamos si la contraseña es correcta
            if ($user->password !== strtolower($data->pass)) {
                //
                Logger::debug('POST/login', 'User password missmatch!');
                // check for lock failed logins
                if (LOCK_FAILED)
                    // actualizamos el intento fallido
                    parent::users()->put(substr($user->href, -10), [], (object)[
                        'retry' => $user->retry + 1
                    ], true);
                // cerramos la session
                $this->sLayer()->logout();
                // retornamos una excepcion
                throw new Exception('Usuario y/o contraseña incorrecta', 403);
            }

            //
            Logger::debug('POST/login', 'Reset user retries to zero');
            // unlock success login
            parent::users()->put(substr($user->href, -10), [], (object)[
                'retry' => 0
            ], true);

            //
            Logger::debug('POST/login', 'Saving user data to $_SESSION');
            // seteamos los datos de session
            $_SESSION['WS_UID'] = substr($user->href, -10);
            // init session
            $this->sLayer()->newToken();
            // save session
            Logger::newSession(substr(md5($user->href.session_id()), 0, 10));
            //
            Logger::debug('POST/login', 'New session inited #'.substr(md5($user->href.session_id()), 0, 10));
            // send token for the next request
            $this->sLayer()->sendToken();
            // return GET/login
            return $this->get(null, [], (object)[], $local);
        }

        public function delete(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // force full logout
            $this->sLayer()->logout();
            // always return true
            return $this->output([
                'success'   => true,
                'deleted'   => true
            ], $local);
        }

        protected final function makeObject(object $dbdata, object $data, bool $local) {
            // unused method
            throw new Exception('Unused method', 500);
        }
    }
