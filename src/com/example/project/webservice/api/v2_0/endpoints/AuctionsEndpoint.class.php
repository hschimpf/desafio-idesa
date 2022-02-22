<?php
    namespace com\example\project\webservice\api\v2_0\endpoints;

    require_once __DIR__.'/../../AbstractAPIEndpoint.class.php';

    use net\hdssolutions\api\performance\Performance;
    use net\hdssolutions\api\APIUtils;
    use net\hdssolutions\php\dbo\DB;
    use Exception;
    use PDO;

    use com\example\project\webservice\api\logger\Logger;
    use com\example\project\webservice\api\AbstractAPIEndpoint;
    use com\example\project\webservice\api\Utils;

    final class AuctionsEndpoint extends AbstractAPIEndpoint {

        public function get(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // build SQL to select objects
            $select = '
                SELECT
                    auction_id,
                    auction_name,
                    auction_starts,
                    auction_ends,
                    auction_active
                FROM auc_auctions
                WHERE
                    auction_deleted IS NULL';
            // add filter
            if ($verb !== null)         $select .= ' AND '.AUC_AUCTION_PK.' = :verb';
            if (isset($data->name))     $select .= ' AND UPPER(auction_name) LIKE UPPER(:name)';
            if (isset($data->starts))   $select .= ' AND auction_starts = :starts';
            if (isset($data->{'starts>'}))  $select .= ' AND CONCAT(DATE(auction_starts), " 00:00:00") >= :startsgt';
            if (isset($data->{'starts<'}))  $select .= ' AND CONCAT(DATE(auction_starts), " 00:00:00") <= :startslt';
            if (isset($data->ends))     $select .= ' AND auction_ends = :ends';
            if (isset($data->{'ends>'}))  $select .= ' AND CONCAT(DATE(auction_ends), " 23:59:59") >= :endsgt';
            if (isset($data->{'ends<'}))  $select .= ' AND CONCAT(DATE(auction_ends), " 23:59:59") <= :endslt';
            if (isset($data->active))   $select .= ' AND auction_active = :active';
            // prepare statement
            Performance::start('mysql');
            $pstmt = DB::getConnection($this->getTransaction())->prepare($select.' ORDER BY auction_starts');
            // add filter values
            if ($verb !== null)         $pstmt->bindValue(':verb',      $verb);
            if (isset($data->name))     $pstmt->bindValue(':name',      Utils::stringRegex($data->name));
            if (isset($data->starts))   $pstmt->bindValue(':starts',    $data->starts);
            if (isset($data->{'starts>'}))  $pstmt->bindValue(':startsgt',  $data->{'starts>'});
            if (isset($data->{'starts<'}))  $pstmt->bindValue(':startslt',  $data->{'starts<'});
            if (isset($data->ends))     $pstmt->bindValue(':ends',      $data->ends);
            if (isset($data->{'ends>'}))    $pstmt->bindValue(':endsgt',    $data->{'ends>'});
            if (isset($data->{'ends<'}))    $pstmt->bindValue(':endslt',    $data->{'ends<'});
            if (isset($data->active))   $pstmt->bindValue(':active',    Utils::active($data->active), PDO::PARAM_BOOL);
            // execute query
            $pstmt->execute();
            // check if $verb is specified
            if ($verb !== null) {
                // get object
                if (($dbdata = $pstmt->fetch(PDO::FETCH_OBJ)) === false)
                    // return not found
                    throw new Exception('Auction not found', 404);
                //
                Performance::end('mysql');
                // check if an endpoint is specified
                if (isset($args[0])) {
                    // save object ID on $data
                    $data->auction = $verb;
                    // check second verb
                    switch ($args[0]) {
                        case 'batches': array_shift($args);
                            // return GET/auctions/{id}/batches/{id}
                            return parent::batches()->get(array_shift($args), $args, $data);
                        // return no endpoint
                        default: throw new Exception('No Endpoint: GET/auctions/{id}/'.$args[0], 400);
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
                $data->auction = $verb;
                // verificamos si se especifico un arg de mas
                // if (isset($args[1])) throw new Exception('Bad Request', 400);
                // verificamos que endpoint se solicita
                switch ($args[0]) {
                    case 'batches': array_shift($args);
                        // return POST/auctions/{id}/batches
                        return parent::batches()->post(array_shift($args), $args, $data);
                    // return no endpoint
                    default: throw new Exception('No Endpoint: POST/auctions/{id}/'.$args[0], 400);
                }
            } else {
                // verificamos si esta especificado un Endpoint de mas
                if (isset($args[0])) throw new Exception('Bad Request', 400);
                // check if all data is received
                if (!isset($data->name) || strlen($data->name) == 0)        throw new Exception('Auction name not specified', 400);
                if (!isset($data->starts) || strlen($data->starts) == 0)    throw new Exception('Auction starts not specified', 400);
                if (!isset($data->ends) || strlen($data->ends) == 0)        throw new Exception('Auction ends not specified', 400);
                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;
                // build INSERT SQL
                $pstmt = DB::getConnection()->prepare('
                    INSERT INTO auc_auctions (
                        auction_name,
                        auction_starts,
                        auction_ends,
                        auction_created,
                        auction_createdby,
                        auction_updatedby
                    ) VALUES (
                        :name,
                        :starts,
                        :ends,
                        NOW(),
                        :createdby,
                        :updatedby
                    )');
                // pass SQL values
                $pstmt->bindValue(':name',      $data->name);
                $pstmt->bindValue(':starts',    $data->starts);
                $pstmt->bindValue(':ends',      $data->ends);
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
                $data->auction = $verb;
                // check if the verb is specified
                if (!isset($args[1])) throw new Exception('Bad Request', 400);
                // check what endpoint is
                switch ($args[0]) {
                    case 'batches': array_shift($args);
                        // return PUT/auctions/{id}/batches
                        return parent::batches()->put(array_shift($args), $args, $data);
                    // return with error
                    default: throw new Exception('No Endpoint: PUT/auctions/{id}/'.$args[0], 400);
                }
            } else {
                // check if changes exists
                if (count((array)$data) === 0 || (
                    !isset($data->name) &&
                    !isset($data->starts) &&
                    !isset($data->ends) &&
                    !isset($data->active)
                    ))
                    // return without changes
                    return $this->output([ 'success' => true ], $local);
                // build the UPDATE query
                $update = '';
                // update of fields
                if (isset($data->name))     $update .= ($update == '' ? '' : ', ').' auction_name = :name';
                if (isset($data->starts))   $update .= ($update == '' ? '' : ', ').' auction_starts = :starts';
                if (isset($data->ends))     $update .= ($update == '' ? '' : ', ').' auction_ends = :ends';
                if (isset($data->active))   $update .= ($update == '' ? '' : ', ').' auction_active = :active';
                // where id filter
                $update = '
                    UPDATE auc_auctions SET
                        '.$update.',
                        auction_updatedby = :updatedby
                    WHERE '.AUC_AUCTION_PK.' = :verb';
                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;
                // prepare the SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare($update);
                // set the field values
                if (isset($data->name))     $pstmt->bindValue(':name',      $data->name);
                if (isset($data->starts))   $pstmt->bindValue(':starts',    $data->starts);
                if (isset($data->ends))     $pstmt->bindValue(':ends',      $data->ends);
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
                $data->auction = $verb;
                // check if the verb is specified
                if (!isset($args[1])) throw new Exception('Bad Request', 400);
                // check what endpoint is
                switch ($args[0]) {
                    // return with error
                    default: throw new Exception('No Endpoint: DELETE/auctions/{id}/'.$args[0], 400);
                }
            } else {
                // build DELETE SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare('
                    UPDATE auc_auctions
                    SET
                        auction_deleted = NOW(),
                        auction_deletedby = :deletedby
                    WHERE
                        '.AUC_AUCTION_PK.' = :verb');
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
            $data->auction      = APIUtils::makeUID($dbdata->auction_id);
            //
            $object             = (object)[];
            if (DEVELOP || $local) $object->raw = (double)$dbdata->auction_id;
            $object->href       = 'auctions/'.$data->auction;
            $object->name       = $dbdata->auction_name;
            $object->starts     = $dbdata->auction_starts;
            $object->ends       = $dbdata->auction_ends;
            $object->batches    = Utils::expand('auction', 'batches', $data->expand ?? null) ?
                parent::batches()->get(null, [], (object)[ 'auction' => $data->auction, 'expand' => $data->expand ?? null ], true)->result :
                $object->href.'/batches';
                if (!DEVELOP && !$local) foreach ($object->batches as $batchIdx => $batch) unset($batch->raw);
            $object->active     = Utils::active($dbdata->auction_active);
            //
            return $object;
        }

    }
