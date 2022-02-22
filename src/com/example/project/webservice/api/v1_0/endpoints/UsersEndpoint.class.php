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

    class UsersEndpoint extends AbstractAPIEndpoint {
        public function get(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // build SQL to select objects
            $select = '
                SELECT
                    user_id,
                    user_name,
                    user_username,
                    LOWER(HEX(user_password)) AS user_password,
                    user_email,
                    user_retry,
                    user_type
                FROM adm_users
                WHERE
                    user_deleted IS NULL';
            // add filter
            if ($verb !== null)         $select .= ' AND '.ADM_USER_PK.' = :user';
            if (isset($data->name))     $select .= ' AND UPPER(user_name) LIKE UPPER(:name)';
            if (isset($data->username)) $select .= ' AND user_username = :username';
            if (isset($data->email))    $select .= ' AND user_email = :email';
            if (isset($data->type))     $select .= ' AND user_type IN ("'.implode('","', explode(',', $data->type)).'")';
            // prepare statement
            Performance::start('mysql');
            $pstmt = DB::getConnection($this->getTransaction())->prepare($select);
            // add filter values
            if ($verb !== null)         $pstmt->bindValue(':user',      $verb);
            if (isset($data->name))     $pstmt->bindValue(':name',      Utils::stringRegex($data->name));
            if (isset($data->username)) $pstmt->bindValue(':username',  $data->username);
            if (isset($data->email))    $pstmt->bindValue(':email',     $data->email);
            // execute query
            $pstmt->execute();
            // check if $verb is specified
            if ($verb !== null) {
                // get object
                if (($dbdata = $pstmt->fetch(PDO::FETCH_OBJ)) === false)
                    // return not found
                    throw new Exception('User not found', 404);
                //
                Performance::end('mysql');
                // check if an endpoint is specified
                if (isset($args[0])) {
                    // save object ID on $data
                    $data->user = $verb;
                    // check second verb
                    switch ($args[0]) {
                        // return no endpoint
                        default: throw new Exception('No Endpoint: GET/users/{id}/'.$args[0], 400);
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
                $data->user = $verb;
                // verificamos si se especifico un arg de mas
                if (isset($args[1])) throw new Exception('Bad Request', 400);
                // verificamos que endpoint se solicita
                switch ($args[0]) {
                    // return no endpoint
                    default: throw new Exception('No Endpoint: POST/users/{id}/'.$args[0], 400);
                }
            } else {
                // verificamos si esta especificado un Endpoint de mas
                if (isset($args[0])) throw new Exception('Bad Request', 400);
                // check if all data is received
                if (!isset($data->name) || strlen($data->name) == 0)            throw new Exception('User name not specified', 400);
                if (!isset($data->username) || strlen($data->username) == 0)    throw new Exception('User username not specified', 400);
                if (!isset($data->password) || strlen($data->password) == 0)    throw new Exception('User password not specified', 400);
                if (!isset($data->email) || strlen($data->email) == 0)          throw new Exception('User email not specified', 400);
                if (!isset($data->type) || strlen($data->type) == 0)            throw new Exception('User type not specified', 400);

                // special validations
                if (PASSWORD_STRENGTH && !Utils::checkPassword(base64_decode($data->password), $data))
                    throw new Exception('User password must be at least 8 character length, contain numbers letters and symbols and doesn\'t containt user information, consecutive characters and repetitions.', 400);
                if (count($this->get(null, [], (object)[ 'email' => $data->email ], true)->result) !== 0)
                    throw new Exception('User email already in use', 400);
                if (count($this->get(null, [], (object)[ 'username' => $data->username ], true)->result) !== 0)
                    throw new Exception('User username already in use', 400);

                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;

                // build INSERT SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare('
                    INSERT INTO adm_users (
                        user_name,
                        user_username,
                        user_password,
                        user_email,
                        user_type,
                        user_created,
                        user_createdby,
                        user_updatedby
                    ) VALUES (
                        :name,
                        :username,
                        UNHEX(MD5(:password)),
                        :email,
                        :type,
                        NOW(),
                        :createdby,
                        :updatedby
                    )');
                // pass SQL values
                $pstmt->bindValue(':name',      $data->name);
                $pstmt->bindValue(':username',  $data->username);
                $pstmt->bindValue(':password',  $data->password);
                $pstmt->bindValue(':email',     $data->email);
                $pstmt->bindValue(':type',      $data->type);
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
                $data->user = $verb;
                // check if the verb is specified
                if (!isset($args[1])) throw new Exception('Bad Request', 400);
                // check what endpoint is
                switch ($args[0]) {
                    // return with error
                    default: throw new Exception('No Endpoint: PUT/users/{id}/'.$args[0], 400);
                }
            } else {
                // check if changes exists
                if (count((array)$data) === 0 || (
                    !isset($data->name) &&
                    !isset($data->email) &&
                    !isset($data->password) &&
                    !isset($data->retry) &&
                    !isset($data->type)
                    ))
                    // return without changes
                    return $this->output([ 'success' => true ], $local);
                // special validations
                if (isset($data->password) && (strlen($data->password) == 0 || PASSWORD_STRENGTH && !Utils::checkPassword(base64_decode($data->password), $data)))
                    throw new Exception('User password must be at least 8 character length, contain numbers letters and symbols and doesn\'t containt user information, consecutive characters and repetitions.', 400);
                // build the UPDATE query
                $update = '';
                // update of fields
                if (isset($data->name))     $update .= ($update == '' ? '' : ', ').' user_name = :name';
                if (isset($data->email))    $update .= ($update == '' ? '' : ', ').' user_email = :email';
                if (isset($data->password)) $update .= ($update == '' ? '' : ', ').' user_password = UNHEX(MD5(:password))';
                if (isset($data->retry))    $update .= ($update == '' ? '' : ', ').' user_retry = :retry';
                if (isset($data->type))     $update .= ($update == '' ? '' : ', ').' user_type = :type';
                // where id filter
                $update = '
                    UPDATE adm_users SET
                        '.$update.',
                        user_updatedby = :updatedby
                    WHERE '.ADM_USER_PK.' = :verb';
                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;
                // prepare the SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare($update);
                // set the field values
                if (isset($data->name))     $pstmt->bindValue(':name',      $data->name);
                if (isset($data->email))    $pstmt->bindValue(':email',     $data->email);
                if (isset($data->password)) $pstmt->bindValue(':password',  $data->password);
                if (isset($data->retry))    $pstmt->bindValue(':retry',     (int)$data->retry > 0 ? (int)$data->retry : 0, PDO::PARAM_INT);
                if (isset($data->type))     $pstmt->bindValue(':type',      $data->type);
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
                $data->user = $verb;
                // check if the verb is specified
                if (!isset($args[1])) throw new Exception('Bad Request', 400);
                // check what endpoint is
                switch ($args[0]) {
                    // return with error
                    default: throw new Exception('No Endpoint: DELETE/users/{id}/'.$args[0], 400);
                }
            } else {
                // build DELETE SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare('
                    UPDATE adm_users
                    SET
                        user_deleted = NOW(),
                        user_deletedby = :deletedby
                    WHERE
                        '.ADM_USER_PK.' = :verb');
                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;
                // pass SQL values
                $pstmt->bindValue(':deletedby', $user->raw);
                $pstmt->bindValue(':verb',      $verb);
                // delete the object
                return $this->output([ 'success' => $pstmt->execute() ], $local);
            }
        }

        protected function makeObject(object $dbdata, object $data, bool $local) {
            $object             = (object)[];
            if (DEVELOP || $local) $object->raw = (double)$dbdata->user_id;
            $object->href       = 'users/'.APIUtils::makeUID($dbdata->user_id);
            $object->name       = $dbdata->user_name;
            $object->username   = $dbdata->user_username;
            if ($local) $object->password   = $dbdata->user_password;
            $object->email      = $dbdata->user_email;
            $object->retry      = (int)$dbdata->user_retry;
            $object->type       = $dbdata->user_type;
            //
            return $object;
        }
    }
