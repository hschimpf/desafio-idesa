<?php
    namespace com\example\project\webservice\api\connector\parser;

    use net\hdssolutions\api\APIUtils;

    use DateTime;
    use Exception;

    final class DataParser {

        public static function parseDepartamentos($verb, $raw_data) {
            $departamentos = [];
            foreach ($raw_data as $departamento_raw) {
                // clear raw data
                $departamento = (object)[
                    'departamento_id'       => $departamento_raw->DEPNDEP,
                    'departamento_name'     => $departamento_raw->DEPDESC,
                    'departamento_qty'      => $departamento_raw->CANTIDAD,
                ];
                // check if verb was specified
                if ($verb !== null && APIUtils::makeUID($departamento->departamento_id) == $verb)
                    // return only specified object
                    return $departamento;

                // add example to list
                $departamentos[] = $departamento;
            }

            // if verb was specified and we get here
            if ($verb !== null)
                // we didnt find specified verb on external data
                throw new Exception('Departamento not found', 404);

            // return list
            return $departamentos;
        }

    }
