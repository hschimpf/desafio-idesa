<?php
    namespace com\example\project\webservice\api\connector;

    require_once __DIR__.'/parser/DataParser.class.php';
    use com\example\project\webservice\api\connector\parser\DataParser;

    require_once __DIR__.'/cache/Cache.class.php';
    use com\example\project\webservice\api\connector\cache\Cache;

    use net\hdssolutions\api\performance\Performance;
    use net\hdssolutions\php\net\Curl;
    use Exception;
    use DateTime;

    use com\example\project\webservice\api\logger\Logger;

    final class ExternalWS {

        public static function departamentos_get($verb) {
            // check for cached data
            if (Cache::get('departamentos_get'.$verb) === null)
                // request data to external WS and save to cache
                Cache::set('departamentos_get'.$verb, self::request('/visitante_lotes_mapa/departamentos/'));
            // get cached data
            $data = Cache::get('departamentos_get'.$verb);
            // parse results
            $departamentos = DataParser::parseDepartamentos($verb, $data->departamentos);
            // return result
            return (object)[
                'success'   => true,
                'result'    => $departamentos
            ];
        }

        private static function request($endpoint, $module = null, $data = null, $req_type = 'POST', $data_type = 'json') {
            //
            $curl = new Curl();
            $curl->setHttpAuth(WS_HT_USER, WS_HT_PASS);
            $curl->enableSslVerify(false);
            $curl->setCookiesJar(WS_COOKIES);
            //
            $req = null;

            // try cached data
            switch ($module) {
                case 'cached_endpoint':
                    //
                    $cache = Cache::fetch($module);
                    //
                    if ($cache !== null)
                        //
                        return $cache;
                    break;
            }

            // Request type
            switch ($req_type) {
                case 'GET':     $req = $curl->get   (WS_HOST.$endpoint, $data); break;
                case 'POST':    $req = $curl->post  (WS_HOST.$endpoint, $data, $data_type); break;
                case 'PUT':     $req = $curl->put   (WS_HOST.$endpoint, $data, $data_type); break;
                case 'DELETE':  $req = $curl->delete(WS_HOST.$endpoint, $data); break;
                default: break;
            }

            //
            Logger::info('EXTERNAL', 'Executing '.$req_type.' request to '.WS_HOST.$endpoint);
            Logger::debug('EXTERNAL', 'DATA: '.json_encode($data));

            //
            Performance::start('external');

            //
            $exec = $req->exec();

            //
            Logger::debug('EXTERNAL', 'Execution finished');

            // verificamos si fallo el request
            if (!$exec) {
                try {
                    // verificamos si es timeout
                    if ($req->getErrno() == CURLE_OPERATION_TIMEDOUT)
                        // generamos una excepcion
                        throw new Exception('Connection timeout', WS_TIMEOUT_ERROR);
                    if ($req->getErrno() == CURLE_COULDNT_CONNECT)
                        // generamos una excepcion
                        throw new Exception('Connection refused', WS_EXTERNAL_ERROR);
                    // generamos una excepcion con el mensaje de error
                    throw new Exception($req->getErrno().': '.$req->getError(), WS_EXTERNAL_ERROR);
                } catch (Exception $e) {
                    //
                    Logger::error('EXTERNAL', $e->getMessage());
                    //
                    throw $e;
                }
            }

            //
            Performance::end('external');

            // parse response
            $res = json_decode(trim($req->getResponse()));

            //
            Logger::debug('EXTERNAL', 'RESPONSE: '.$req->getResponse());

            // save to cache
            switch ($module) {
                case 'cached_endpoint':
                    //
                    Cache::save($module, $res);
                    break;
            }

            // check for errors
            if (isset($res->error) && strnel($res->error) > 0)
                //
                throw new Exception($res->error, WS_EXTERNAL_ERROR);

            // //
            // if (!isset($res->result) || count($res->result) == 0)
            //     //
            //     throw new Exception('Invalid data returned', WS_EXTERNAL_ERROR);

            // return response
            return $res;
        }
    }
