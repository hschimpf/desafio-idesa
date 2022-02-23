<?php
    namespace com\example\project\webservice\api\v2_0\endpoints;

    require_once __DIR__.'/../../AbstractAPIEndpoint.class.php';
    require_once __DIR__.'../../../connector/ExternalWS.class.php';

    use net\hdssolutions\api\performance\Performance;
    use net\hdssolutions\api\APIUtils;
    use net\hdssolutions\php\dbo\DB;
    use Exception;
    use PDO;

    use com\example\project\webservice\api\logger\Logger;
    use com\example\project\webservice\api\AbstractAPIEndpoint;
    use com\example\project\webservice\api\Utils;

    use com\example\project\webservice\api\connector\ExternalWS;

    final class DepartamentosEndpoint extends AbstractAPIEndpoint {

        public function get(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // get data from external WS
            $departamentos = ExternalWS::departamentos_get($verb);

            // check if $verb is specified
            if ($verb !== null) {
                // create object
                $object = $this->makeObject($departamentos->result, $data, $local);
                // return the object
                return $this->output([
                    'success'   => true,
                    'result'    => $object
                ], $local);
            }
            // build a list
            $objects = [];
            // foreach objects
            foreach ($departamentos->result as $departamento)
                // add object to list
                $objects[] = $this->makeObject($departamento, $data, $local);

            // return objects
            return $this->output([
                'success'   => true,
                'result'    => $objects
            ], $local);
        }

        protected final function makeObject(object $dbdata, object $data, bool $local) {
            $data->departamento = APIUtils::makeUID($dbdata->departamento_id);

            $object             = (object)[];
            if (DEVELOP || $local) $object->raw = (string)$dbdata->departamento_id;
            $object->href       = 'departamentos/'.$data->departamento;
            $object->name       = $dbdata->departamento_name;
            $object->qty        = (int)$dbdata->departamento_qty;

            return $object;
        }
    }
