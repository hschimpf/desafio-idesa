<?php
    namespace com\example\project\webservice\api;

    use net\hdssolutions\php\rest\AbstractObjectOrientedRestAPI;
    use net\hdssolutions\php\dbo\DB;

    use net\hdssolutions\php\security\SecurityLayer;
    use com\example\project\webservice\api\logger\Logger;
    use Exception;

    abstract class AbstractWebserviceAPI extends AbstractObjectOrientedRestAPI {
        /**
         * Security Layer
         * @var SecurityLayer
         */
        private $sLayer = null;

        /**
         * Allowed endpoints without token
         * @var array Allowed Endpoints
         */
        private $allowed_endpoints = [
            'GET/login',
            'POST/login',
            'DELETE/login',
        ];

        /**
         * Received authorization token
         * @var token Authorization Token
         */
        private $auth_token = null;

        /**
         * [$transaction description]
         * @var null
         */
        private $transaction = null;

        protected final function _construct() {
            // init Security Layer
            $this->sLayer = new SecurityLayer();
            // enable endpoints withoutVerb
            $this->allowPutDeleteWithoutVerb([
                'DELETE/login'
            ]);
        }

        protected final function beforeExecute() {
            // get HEADERS
            $_HEADERS = apache_request_headers();
            // get token from HEADERS
            $this->auth_token = isset($_HEADERS['Authorization-Token']) && strlen($_HEADERS['Authorization-Token']) > 0 ? $_HEADERS['Authorization-Token'] : null;
            // log endpoint call
            Logger::debug('LOCAL', (string)$this);
            // check session access
            $this->checkSessionAccess();
        }

        protected final function afterExecute() {
            // send token if there is a session active
            if ($this->sLayer !== null && $this->sLayer->isLogged() && (string)$this === 'POST/login') $this->sLayer->sendToken();
            // log finish
            Logger::debug('LOCAL', 'Process finished');
        }

        public final function sLayer() {
            // return sLayer instance
            return $this->sLayer;
        }

        protected final function sendSessionID() {
            // verificamos si enviamos el session ID
            return $this->auth_token !== null &&
                // esta logueado
                $this->sLayer->isLogged() &&
                // solicita GET/login
                substr((string)$this, 0, 9) == 'GET/login' &&
                // token no es valido
                $this->sLayer->validateToken($this->auth_token);
        }

        public final function getTransaction() {
            // return transaction name
            return $this->transaction;
        }

        public final function startTransaction() {
            // check is a transaction exists
            if ($this->transaction !== null)
                // rollback current transaction
                $this->rollbackTransaction();
            // start a new transaction
            $this->transaction = md5(rand());
        }

        public final function commitTransaction() {
            // commit current transaction
            DB::commitTransaction($this->transaction);
            // reset transaction name
            $this->transaction = null;
        }

        public final function rollbackTransaction() {
            // rollback current transacction
            DB::rollbackTransaction($this->transaction);
            // reset transaction name
            $this->transaction = null;
        }

        private function checkSessionAccess() {
            // flag to false by default
            $allowed_endpoint = false;
            //
            Logger::debug('LOCAL', 'Checking endpoint session access');
            // build allowed endpoints flag
            foreach ($this->allowed_endpoints as $preg_allowed) if (substr($this, 0, strlen($preg_allowed)) == $preg_allowed) $allowed_endpoint = true;
            // check allowed endpoints flag + token validation
            if (
                    // ignoramos los endpoints permitidos sin token
                    !$allowed_endpoint && !isset($_SESSION['WS_UID']) /*(
                    // esta logueado y el token no coincide
                    $this->sLayer->isLogged() && !$this->sLayer->validateToken($this->auth_token) ||
                    // no esta logueado y especifica token o no es un endpoint no permitido
                    !$this->sLayer->isLogged() && ($this->auth_token !== null || !$allowed_endpoint))*/
                    //
                    || (
                        // se especifico token
                        $this->auth_token !== null &&
                        // esta logueado
                        $this->sLayer->isLogged() &&
                        // solicita GET/login
                        in_array(substr((string)$this, 0, 9), [ 'GET/login' ]) &&
                        // token no es valido
                        !$this->sLayer->validateToken($this->auth_token)
                    )
                ) {
                // cancelamos la session
                //$this->sLayer->logout();
                //
                Logger::warning('LOCAL', 'Access Denied');
                // salimos con una excepcion
                throw new Exception('Access Denied', 403);
            }
        }
    }
