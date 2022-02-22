<?php
    namespace com\example\project\webservice\api\v2_0\endpoints;

    require_once __DIR__.'/UsersEndpoint.class.php';

    use net\hdssolutions\api\performance\Performance;
    use net\hdssolutions\api\APIUtils;
    use net\hdssolutions\php\dbo\DB;
    use Exception;
    use PDO;

    use com\example\project\webservice\api\logger\Logger;
    use com\example\project\webservice\api\v1_0\endpoints\UsersEndpoint;
    use com\example\project\webservice\api\Utils;

    final class ClientsEndpoint extends UsersEndpoint {

        public function get(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // build SQL to select objects
            $select = '
                SELECT
                    user_id,
                    client_firstname,
                    client_lastname,
                    client_documentno,
                    user_name,
                    user_username,
                    LOWER(HEX(user_password)) AS user_password,
                    user_email,
                    user_type,
                    user_status,
                    client_address,
                    client_phone,
                    '.APIUtils::makeFK('client_nationality').' AS client_nationality,
                    user_retry,
                    user_status,
                    user_created,
                    user_updated,
                    client_active
                FROM adm_clients
                JOIN adm_users ON client_id = user_id
                WHERE
                    user_deleted IS NULL';
            // add filter
            if ($verb !== null)             $select .= ' AND '.SYS_CLIENT_PK.' = :verb';
            if (isset($data->firstname))    $select .= ' AND UPPER(user_firstname) LIKE UPPER(:firstname)';
            if (isset($data->lastname))     $select .= ' AND UPPER(user_lastname) LIKE UPPER(:lastname)';
            if (isset($data->documentno))   $select .= ' AND client_documentno = :documentno';
            if (isset($data->name))         $select .= ' AND UPPER(user_name) LIKE UPPER(:name)';
            if (isset($data->username))     $select .= ' AND user_username = :username';
            if (isset($data->email))        $select .= ' AND user_email = :email';
            if (isset($data->nationality))  $select .= ' AND '.APIUtils::makeFK('client_nationality').' = :nationality';
            if (isset($data->status))       $select .= ' AND user_status IN ("'.implode('","', explode(',', $data->status)).'")';
            if (isset($data->active))       $select .= ' AND client_active = :active';
            // prepare statement
            Performance::start('mysql');
            $pstmt = DB::getConnection($this->getTransaction())->prepare($select);
            // add filter values
            if ($verb !== null)             $pstmt->bindValue(':verb',          $verb);
            if (isset($data->company))      $pstmt->bindValue(':company',       $data->company);
            if (isset($data->firstname))    $pstmt->bindValue(':firstname',     Utils::stringRegex($data->firstname));
            if (isset($data->lastname))     $pstmt->bindValue(':lastname',      Utils::stringRegex($data->lastname));
            if (isset($data->documentno))   $pstmt->bindValue(':documentno',    $data->documentno);
            if (isset($data->name))         $pstmt->bindValue(':name',          Utils::stringRegex($data->name));
            if (isset($data->username))     $pstmt->bindValue(':username',      $data->username);
            if (isset($data->email))        $pstmt->bindValue(':email',         $data->email);
            if (isset($data->nationality))  $pstmt->bindValue(':nationality',   $data->nationality);
            if (isset($data->active))       $pstmt->bindValue(':active',        Utils::active($data->active), PDO::PARAM_BOOL);
            // execute query
            $pstmt->execute();
            // check if $verb is specified
            if ($verb !== null) {
                // get object
                if (($dbdata = $pstmt->fetch(PDO::FETCH_OBJ)) === false)
                    // return not found
                    throw new Exception('Client not found', 404);
                //
                Performance::end('mysql');
                // check if an endpoint is specified
                if (isset($args[0])) {
                    // save object ID on $data
                    $data->client = $verb;
                    // check second verb
                    switch ($args[0]) {
                        case 'nationality': array_shift($args);
                            // return GET/countries/{id}
                            return parent::countries()->get($dbdata->client_nationality, $args, $data);
                        case 'bids': array_shift($args);
                            // return GET/clients/{id}/bids/{id}
                            return parent::bids()->get(array_shift($args), $args, $data);
                        // return no endpoint
                        default: throw new Exception('No Endpoint: GET/clients/{id}/'.$args[0], 400);
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
                $data->client = $verb;
                // verificamos si se especifico un arg de mas
                if (isset($args[1])) throw new Exception('Bad Request', 400);
                // verificamos que endpoint se solicita
                switch ($args[0]) {
                    // return no endpoint
                    default: throw new Exception('No Endpoint: POST/clients/{id}/'.$args[0], 400);
                }
            } else {
                // verificamos si esta especificado un Endpoint de mas
                if (isset($args[0])) throw new Exception('Bad Request', 400);
                // check if all data is received
                if (!isset($data->firstname) || strlen($data->firstname) == 0)      throw new Exception('Client firstname not specified', 400);
                if (!isset($data->lastname) || strlen($data->lastname) == 0)        throw new Exception('Client lastname not specified', 400);
                if (!isset($data->documentno) || strlen($data->documentno) == 0)    throw new Exception('Client documentno not specified', 400);
                if (!isset($data->address) || strlen($data->address) == 0)          throw new Exception('Client address not specified', 400);
                if (!isset($data->phone) || strlen($data->phone) == 0)              throw new Exception('Client phone not specified', 400);
                if (!isset($data->nationality) || strlen($data->nationality) == 0)  throw new Exception('Client Nationality not specified', 400);
                // default/fixed values
                $data->type = 'client';
                $data->name = $data->lastname.', '.$data->firstname;
                // special validations
                if (count($this->get(null, [], (object)[ 'documentno' => $data->documentno ], true)->result) !== 0)
                    throw new Exception('Client documentno already in use', 400);
                // init transaction
                $this->startTransaction();
                //
                Logger::debug('POST/clients', 'Creating base user');
                // create base_user
                $base_user = parent::users()->post(null, [], $data, true)->result;
                // load related data
                if (isset($data->nationality))  $nationality = parent::countries()->get($data->nationality, [], (object)[], true)->result;
                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;
                //
                Logger::debug('POST/clients', 'Creating client with user data '.json_encode($base_user));
                // build INSERT SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare('
                    INSERT INTO adm_clients (
                        client_id,
                        client_firstname,
                        client_lastname,
                        client_documentno,
                        client_address,
                        client_phone,
                        client_nationality
                    ) VALUES (
                        :client,
                        :firstname,
                        :lastname,
                        :documentno,
                        :address,
                        :phone,
                        :nationality
                    )');
                // pass SQL values
                $pstmt->bindValue(':client',        $base_user->raw);
                $pstmt->bindValue(':firstname',     $data->firstname);
                $pstmt->bindValue(':lastname',      $data->lastname);
                $pstmt->bindValue(':documentno',    $data->documentno);
                $pstmt->bindValue(':address',       $data->address);
                $pstmt->bindValue(':phone',         $data->phone);
                $pstmt->bindValue(':nationality',   $nationality->raw);
                // execute INSERT query
                $pstmt->execute();
                // commit transaction
                $this->commitTransaction();
                // return created Object
                return $this->get(APIUtils::makeUID($base_user->raw), [], (object)[], $local);
            }
        }

        public function put(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // check if object exists, it returns with 404 not found exception
            $this->get($verb, [], (object)[], true);
            // check if an endpoint is specified
            if (isset($args[0])) {
                // save object
                $data->client = $verb;
                // check if the verb is specified
                if (!isset($args[1])) throw new Exception('Bad Request', 400);
                // check what endpoint is
                switch ($args[0]) {
                    // return with error
                    default: throw new Exception('No Endpoint: PUT/clients/{id}/'.$args[0], 400);
                }
            } else {
                // update base user
                parent::users()->put($verb, $args, $data, true);
                // check if changes exists
                if (count((array)$data) === 0 || (
                    !isset($data->firstname) &&
                    !isset($data->lastname) &&
                    !isset($data->documentno) &&
                    !isset($data->address) &&
                    !isset($data->phone) &&
                    !isset($data->nationality) &&
                    !isset($data->active)
                    ))
                    // return without changes
                    return $this->output([ 'success' => true ], $local);
                // build the UPDATE query
                $update = '';
                // update of fields
                if (isset($data->firstname))    $update .= ($update == '' ? '' : ', ').' client_firstname = :firstname';
                if (isset($data->lastname))     $update .= ($update == '' ? '' : ', ').' client_lastname = :lastname';
                if (isset($data->documentno))   $update .= ($update == '' ? '' : ', ').' client_documentno = :documentno';
                if (isset($data->address))      $update .= ($update == '' ? '' : ', ').' client_address = :address';
                if (isset($data->phone))        $update .= ($update == '' ? '' : ', ').' client_phone = :phone';
                if (isset($data->nationality))  $update .= ($update == '' ? '' : ', ').' client_nationality = :nationality';
                if (isset($data->active))       $update .= ($update == '' ? '' : ', ').' client_active = :active';
                // load related data
                if (isset($data->nationality))  $nationality = parent::countries()->get($data->nationality, [], (object)[], true)->result;
                // where id filter
                $update = '
                    UPDATE adm_clients SET
                        '.$update.',
                        client_updatedby = :updatedby
                    WHERE '.SYS_CLIENT_PK.' = :verb';
                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;
                // prepare the SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare($update);
                // set the field values
                if (isset($data->firstname))    $pstmt->bindValue(':firstname',     $data->firstname);
                if (isset($data->lastname))     $pstmt->bindValue(':lastname',      $data->lastname);
                if (isset($data->documentno))   $pstmt->bindValue(':documentno',    $data->documentno);
                if (isset($data->address))      $pstmt->bindValue(':address',       $data->address);
                if (isset($data->phone))        $pstmt->bindValue(':phone',         $data->phone);
                if (isset($data->nationality))  $pstmt->bindValue(':nationality',   $nationality->raw);
                if (isset($data->active))       $pstmt->bindValue(':active',        Utils::active($data->active), PDO::PARAM_BOOL);
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
                $data->client = $verb;
                // check if the verb is specified
                if (!isset($args[1])) throw new Exception('Bad Request', 400);
                // check what endpoint is
                switch ($args[0]) {
                    // return with error
                    default: throw new Exception('No Endpoint: DELETE/clients/{id}/'.$args[0], 400);
                }
            } else {
                // build DELETE SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare('
                    UPDATE adm_clients
                    SET
                        client_deleted = NOW(),
                        client_deletedby = :deletedby
                    WHERE
                        '.SYS_CLIENT_PK.' = :verb');
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
            $data->client       = APIUtils::makeUID($dbdata->user_id);
            // fetch data from parent
            $object             = parent::makeObject($dbdata, $data, $local);
            // replace href
            $object->href       = 'clients/'.$data->client;
            // add local data
            $object->firstname  = $dbdata->client_firstname;
            $object->lastname   = $dbdata->client_lastname;
            $object->documentno = (int)$dbdata->client_documentno;
            $object->address    = $dbdata->client_address;
            $object->phone      = $dbdata->client_phone;
            $object->nationality    = $dbdata->client_nationality !== null && Utils::expand('client', 'nationality', $data->expand ?? null) ?
                parent::countries()->get($dbdata->client_nationality, [], (object)[ 'expand' => $data->expand ?? null ], true)->result :
                ($dbdata->client_nationality !== null ? 'countries/'.$dbdata->client_nationality : null);
                if (!DEVELOP && !$local) unset($object->nationality->raw);
            $object->bids       = Utils::expand('client', 'bids', $data->expand ?? null) ?
                parent::bids()->get(null, [], (object)[ 'client' => $data->client, 'expand' => $data->expand ?? null ], true)->result :
                $object->href.'/bids';
                if (!DEVELOP && !$local) foreach ($object->bids as $bidIdx => $bid) unset($bid->raw);
            $object->status     = $dbdata->user_status;
            $object->created    = $dbdata->user_created;
            $object->updated    = $dbdata->user_updated;
            //
            return $object;
        }

    }
