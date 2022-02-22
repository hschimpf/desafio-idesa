<?php
    namespace com\example\project\webservice\api\v1_0\endpoints;

    require_once __DIR__.'/../../AbstractAPIEndpoint.class.php';

    use net\hdssolutions\api\performance\Performance;
    use net\hdssolutions\api\APIUtils;
    use net\hdssolutions\php\dbo\DB;
    use Exception;
    use PDO;

    use com\example\project\webservice\api\logger\Logger;
    use com\example\project\webservice\api\AbstractAPIEndpoint;
    use com\example\project\webservice\api\Utils;

    final class CountriesEndpoint extends AbstractAPIEndpoint {

        public function get(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // build SQL to select objects
            $select = '
                SELECT
                    country_id,
                    country_name,
                    country_active
                FROM dat_countries
                WHERE
                    country_deleted IS NULL';
            // add filter
            if ($verb !== null)         $select .= ' AND '.DAT_COUNTRY_PK.' = :verb';
            if (isset($data->name))     $select .= ' AND UPPER(country_name) LIKE UPPER(:name)';
            if (isset($data->active))   $select .= ' AND country_active = :active';
            // prepare statement
            Performance::start('mysql');
            $pstmt = DB::getConnection($this->getTransaction())->prepare($select);
            // add filter values
            if ($verb !== null)         $pstmt->bindValue(':verb',      $verb);
            if (isset($data->name))     $pstmt->bindValue(':name',      Utils::stringRegex($data->name));
            if (isset($data->active))   $pstmt->bindValue(':active',    Utils::active($data->active), PDO::PARAM_BOOL);
            // execute query
            $pstmt->execute();
            // check if $verb is specified
            if ($verb !== null) {
                // get object
                if (($dbdata = $pstmt->fetch(PDO::FETCH_OBJ)) === false)
                    // return not found
                    throw new Exception('Country not found', 404);
                //
                Performance::end('mysql');
                // check if an endpoint is specified
                if (isset($args[0])) {
                    // save object ID on $data
                    $data->country = $verb;
                    // check second verb
                    switch ($args[0]) {
                        case 'cities': array_shift($args);
                            // return GET/countries/{id}/cities/{id}
                            return parent::cities()->get(array_shift($args), $args, $data);
                        // return no endpoint
                        default: throw new Exception('No Endpoint: GET/countries/{id}/'.$args[0], 400);
                    }
                }
                // create object
                $object = $this->makeObject($dbdata, $data, $local);
                // return the object
                return $this->output([
                    'success'   => true,
                    'result'    => $object
                ], $local);
            }
            // build a list
            $objects = [];
            // foreach objects
            while (($dbdata = $pstmt->fetch(PDO::FETCH_OBJ)) !== false) {
                //
                Performance::end('mysql');
                // add object to list
                $objects[] = $this->makeObject($dbdata, $data, $local);
            }
            // return login status
            return $this->output([
                'success'   => true,
                'result'    => $objects
            ], $local);
        }

        public function post(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // check if verb is specified
            if ($verb !== null) {
                // check if object exists
                $this->get($verb, [], (object)[], true);
                // save object ID
                $data->country = $verb;
                // verificamos si se especifico un arg de mas
                if (isset($args[1])) throw new Exception('Bad Request', 400);
                // verificamos que endpoint se solicita
                switch ($args[0]) {
                    case 'cities': array_shift($args);
                        // return POST/countries/{id}/cities
                        return parent::cities()->post(array_shift($args), $args, $data);
                    // return no endpoint
                    default: throw new Exception('No Endpoint: POST/countries/{id}/'.$args[0], 400);
                }
            } else {
                // verificamos si esta especificado un Endpoint de mas
                if (isset($args[0])) throw new Exception('Bad Request', 400);
                // check if all data is received
                if (!isset($data->name) || strlen($data->name) == 0)    throw new Exception('Country name not specified', 400);
                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;
                // build INSERT SQL
                $pstmt = DB::getConnection()->prepare('
                    INSERT INTO dat_countries (
                        country_name,
                        country_created,
                        country_createdby,
                        country_updatedby
                    ) VALUES (
                        :name,
                        NOW(),
                        :createdby,
                        :updatedby
                    )');
                // pass SQL values
                $pstmt->bindValue(':name',      $data->name);
                $pstmt->bindValue(':createdby', $user->raw);
                $pstmt->bindValue(':updatedby', $user->raw);
                // execute INSERT query
                $pstmt->execute();
                // return created Object
                return $this->get(APIUtils::makeUID(DB::getConnection($this->getTransaction())->lastInsertId()), [], (object)[], $local);
            }
        }

        public function put(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // check if object exists, it returns with 404 not found exception
            $this->get($verb, [], (object)[], true);
            // check if an endpoint is specified
            if (isset($args[0])) {
                // save object
                $data->country = $verb;
                // check if the verb is specified
                if (!isset($args[1])) throw new Exception('Bad Request', 400);
                // check what endpoint is
                switch ($args[0]) {
                    case 'cities': array_shift($args);
                        // return PUT/countries/{id}/cities
                        return parent::cities()->put(array_shift($args), $args, $data);
                    // return with error
                    default: throw new Exception('No Endpoint: PUT/countries/{id}/'.$args[0], 400);
                }
            } else {
                // check if changes exists
                if (count((array)$data) === 0 || (
                    !isset($data->name) &&
                    !isset($data->active)
                    ))
                    // return without changes
                    return $this->output([ 'success' => true ], $local);
                // build the UPDATE query
                $update = '';
                // update of fields
                if (isset($data->name))     $update .= ($update == '' ? '' : ', ').' country_name = :name';
                if (isset($data->active))   $update .= ($update == '' ? '' : ', ').' country_active = :active';
                // where id filter
                $update = '
                    UPDATE dat_countries SET
                        '.$update.',
                        country_updatedby = :updatedby
                    WHERE '.DAT_COUNTRY_PK.' = :verb';
                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;
                // prepare the SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare($update);
                // set the field values
                if (isset($data->name))     $pstmt->bindValue(':name',      $data->name);
                if (isset($data->active))   $pstmt->bindValue(':active',    Utils::active($data->active), PDO::PARAM_BOOL);
                // set the query id filter
                $pstmt->bindValue(':updatedby', isset($user->raw) ? $user->raw : 0);
                $pstmt->bindValue(':verb',      $verb);
                // execute the query
                return $this->output([ 'success' => $pstmt->execute() ], $local);
            }
        }

        public function delete(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // check if object exists, it returns with 404 not found exception
            $this->get($verb, [], (object)[], true)->result;
            // check if an endpoint is specified
            if (isset($args[0])) {
                // save object
                $data->country = $verb;
                // check if the verb is specified
                if (!isset($args[1])) throw new Exception('Bad Request', 400);
                // check what endpoint is
                switch ($args[0]) {
                    // return with error
                    default: throw new Exception('No Endpoint: DELETE/countries/{id}/'.$args[0], 400);
                }
            } else {
                // build DELETE SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare('
                    UPDATE dat_countries
                    SET
                        country_deleted = NOW(),
                        country_deletedby = :deletedby
                    WHERE
                        '.DAT_COUNTRY_PK.' = :verb');
                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;
                // pass SQL values
                $pstmt->bindValue(':deletedby', $user->raw);
                $pstmt->bindValue(':verb',      $verb);
                // delete the object
                return $this->output([ 'success' => $pstmt->execute() ], $local);
            }
        }

        protected final function makeObject(object $dbdata, object $data, bool $local) {
            $data->country      = APIUtils::makeUID($dbdata->country_id);
            //
            $object             = (object)[];
            if (DEVELOP || $local) $object->raw = (double)$dbdata->country_id;
            $object->href       = 'countries/'.$data->country;
            $object->name       = $dbdata->country_name;
            $object->active     = Utils::active($dbdata->country_active);
            //
            return $object;
        }

    }
