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

    final class BatchesEndpoint extends AbstractAPIEndpoint {
        public function get(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // build SQL to select objects
            $select = '
                SELECT
                    batch_id,
                    '.APIUtils::makeFK('batch_auction').' AS batch_auction,
                    batch_auction AS batch_auction_raw,
                    batch_amount_start AS amount_start,
                    batch_amount_current AS amount_current,
                    '.APIUtils::makeFK('batch_auction', 'batch_id', 'batch_last_client', 'batch_last_bid').' AS batch_last_bid,
                    batch_active
                FROM auc_batches
                WHERE
                    batch_deleted IS NULL';
            // add filter
            if ($verb !== null)         $select .= ' AND '.AUC_BATCH_PK.' = :verb';
            if (isset($data->name))     $select .= ' AND UPPER(batch_name) LIKE UPPER(:name)';
            if (isset($data->auction))  $select .= ' AND '.APIUtils::makeFK('batch_auction').' = :auction';
            if (isset($data->active))   $select .= ' AND batch_active = :active';
            // prepare statement
            Performance::start('mysql');
            $pstmt = DB::getConnection($this->getTransaction())->prepare($select);
            // add filter values
            if ($verb !== null)         $pstmt->bindValue(':verb',      $verb);
            if (isset($data->name))     $pstmt->bindValue(':name',      Utils::stringRegex($data->name));
            if (isset($data->auction))  $pstmt->bindValue(':auction',   $data->auction);
            if (isset($data->active))   $pstmt->bindValue(':active',    Utils::active($data->active), PDO::PARAM_BOOL);
            // execute query
            $pstmt->execute();
            // check if $verb is specified
            if ($verb !== null) {
                // get object
                if (($dbdata = $pstmt->fetch(PDO::FETCH_OBJ)) === false)
                    // return not found
                    throw new Exception('Batch not found', 404);
                //
                Performance::end('mysql');
                // check if an endpoint is specified
                if (isset($args[0])) {
                    // save object ID on $data
                    $data->batch = $verb;
                    // check second verb
                    switch ($args[0]) {
                        case 'auction': array_shift($args);
                            // return GET/auctions/{id}
                            return parent::auctions()->get($dbdata->batch_auction, $args, $data);
                        // return no endpoint
                        default: throw new Exception('No Endpoint: GET/batches/{id}/'.$args[0], 400);
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
                $data->batch = $verb;
                // verificamos si se especifico un arg de mas
                // if (isset($args[1])) throw new Exception('Bad Request', 400);
                // verificamos que endpoint se solicita
                switch ($args[0]) {
                    case 'bids': array_shift($args);
                        // return POST/batches/{id}/bids
                        return parent::bids()->post(array_shift($args), $args, $data);
                    // return no endpoint
                    default: throw new Exception('No Endpoint: POST/batches/{id}/'.$args[0], 400);
                }
            } else {
                // verificamos si esta especificado un Endpoint de mas
                if (isset($args[0])) throw new Exception('Bad Request', 400);
                // check if all data is received
                if (!isset($data->auction) || strlen($data->auction) == 0)          throw new Exception('Batch auction not specified', 400);
                if (!isset($data->amount_start) || strlen($data->amount_start) == 0)throw new Exception('Batch amount_start not specified', 400);
                // load related data
                if (isset($data->auction))  $auction = parent::auctions()->get($data->auction, [], (object)[], true)->result;
                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;
                // load next ID
                $pstmt = DB::getConnection()->prepare('
                    SELECT COALESCE(MAX(batch_id) + 1, 1) AS raw
                    FROM auc_batches
                    WHERE
                        batch_auction = :auction');
                $pstmt->bindValue(':auction', $auction->raw);
                $pstmt->execute();
                $batch = $pstmt->fetch(PDO::FETCH_OBJ);
                // build INSERT SQL
                $pstmt = DB::getConnection()->prepare('
                    INSERT INTO auc_batches (
                        batch_id,
                        batch_auction,
                        batch_amount_start,
                        batch_amount_current,
                        batch_created,
                        batch_createdby,
                        batch_updatedby
                    ) VALUES (
                        :batch,
                        :auction,
                        :amount_start,
                        :amount_current,
                        NOW(),
                        :createdby,
                        :updatedby
                    )');
                // pass SQL values
                $pstmt->bindValue(':batch',     $batch->raw);
                $pstmt->bindValue(':auction',   $auction->raw);
                $pstmt->bindValue(':amount_start',      (int)$data->amount_start > 0 ? (int)$data->amount_start : 0, PDO::PARAM_INT);
                $pstmt->bindValue(':amount_current',    isset($data->amount_current) && (int)$data->amount_current > 0 ? (int)$data->amount_current : null, PDO::PARAM_INT);
                $pstmt->bindValue(':createdby', $user->raw);
                $pstmt->bindValue(':updatedby', $user->raw);
                // execute INSERT query
                $pstmt->execute();
                // return created Object
                return $this->get(APIUtils::makeUID($auction->raw, $batch->raw), [], (object)[], $local);
            }
        }

        public function put(string $verb = null, array $args = [], object $data = null, bool $local = false) {
            // check if object exists, it returns with 404 not found exception
            $this->get($verb, [], (object)[], true);
            // check if an endpoint is specified
            if (isset($args[0])) {
                // save object
                $data->batch = $verb;
                // check if the verb is specified
                if (!isset($args[1])) throw new Exception('Bad Request', 400);
                // check what endpoint is
                switch ($args[0]) {
                    case 'animals': array_shift($args);
                        // return PUT/batches/{id}/animals
                        return parent::batch_animals()->put(array_shift($args), $args, $data);
                    // return with error
                    default: throw new Exception('No Endpoint: PUT/batches/{id}/'.$args[0], 400);
                }
            } else {
                // check if changes exists
                if (count((array)$data) === 0 || (
                    !isset($data->auction) &&
                    !isset($data->amount_start) &&
                    !isset($data->amount_current) &&
                    !isset($data->last_bid) &&
                    !isset($data->active)
                    ))
                    // return without changes
                    return $this->output([ 'success' => true ], $local);
                // build the UPDATE query
                $update = '';
                // update of fields
                if (isset($data->auction))  $update .= ($update == '' ? '' : ', ').' batch_auction = :auction';
                if (isset($data->amount_start))     $update .= ($update == '' ? '' : ', ').' batch_amount_start = :amount_start';
                if (isset($data->amount_current))   $update .= ($update == '' ? '' : ', ').' batch_amount_current = :amount_current';
                if (isset($data->last_bid)) $update .= ($update == '' ? '' : ', ').' batch_last_client = :last_client';
                if (isset($data->last_bid)) $update .= ($update == '' ? '' : ', ').' batch_last_bid = :last_bid';
                if (isset($data->active))   $update .= ($update == '' ? '' : ', ').' batch_active = :active';
                // load related data
                if (isset($data->auction))  $auction = parent::auctions()->get($data->auction, [], (object)[], true)->result;
                if (isset($data->last_bid)) $last_bid = parent::bids()->get($data->last_bid, [], (object)[ 'expand' => 'bid.client' ], true)->result;
                // where id filter
                $update = '
                    UPDATE auc_batches SET
                        '.$update.',
                        batch_updatedby = :updatedby
                    WHERE '.AUC_BATCH_PK.' = :verb';
                // get current user data
                $user = parent::login()->get(null, [], (object)[], true)->result;
                // prepare the SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare($update);
                // set the field values
                if (isset($data->auction))          $pstmt->bindValue(':auction',       $auction->raw);
                if (isset($data->amount_start))     $pstmt->bindValue(':amount_start',  $data->amount_start);
                if (isset($data->amount_current))   $pstmt->bindValue(':amount_current',$data->amount_current);
                if (isset($data->last_bid))         $pstmt->bindValue(':last_client',   strlen($data->last_bid) > 0 ? $last_bid->client->raw : null, PDO::PARAM_INT);
                if (isset($data->last_bid))         $pstmt->bindValue(':last_bid',      strlen($data->last_bid) > 0 ? $last_bid->raw : null, PDO::PARAM_INT);
                if (isset($data->active))           $pstmt->bindValue(':active',        Utils::active($data->active), PDO::PARAM_BOOL);
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
                $data->batch = $verb;
                // check if the verb is specified
                if (!isset($args[1])) throw new Exception('Bad Request', 400);
                // check what endpoint is
                switch ($args[0]) {
                    // return with error
                    default: throw new Exception('No Endpoint: DELETE/batches/{id}/'.$args[0], 400);
                }
            } else {
                // build DELETE SQL
                $pstmt = DB::getConnection($this->getTransaction())->prepare('
                    UPDATE auc_batches
                    SET
                        batch_deleted = NOW(),
                        batch_deletedby = :deletedby
                    WHERE
                        '.AUC_BATCH_PK.' = :verb');
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
            $data->batch         = APIUtils::makeUID($dbdata->batch_auction_raw, $dbdata->batch_id);

            //
            $object             = (object)[];
            if (DEVELOP || $local) $object->raw = (double)$dbdata->batch_id;
            $object->href       = 'batches/'.$data->batch;
            $object->auction    = $dbdata->batch_auction !== null && Utils::expand('batch', 'auction', $data->expand ?? null) ?
                parent::auctions()->get($dbdata->batch_auction, [], (object)[ 'expand' => $data->expand ?? null ], true)->result :
                ($dbdata->batch_auction !== null ? 'auctions/'.$dbdata->batch_auction : null);
                if (!DEVELOP && !$local) unset($object->auction->raw);
            $object->amount_start       = (float)$dbdata->amount_start;
            $object->amount_current     = $dbdata->amount_current !== null ? (float)$dbdata->amount_current : null;
            $object->last_bid   = $dbdata->batch_last_bid !== null && Utils::expand('batch', 'last_bid', $data->expand ?? null) ?
                parent::bids()->get($dbdata->batch_last_bid, [], (object)[ 'expand' => $data->expand ?? null ], true)->result :
                ($dbdata->batch_last_bid !== null ? 'bids/'.$dbdata->batch_last_bid : null);
                if (!DEVELOP && !$local) unset($object->last_bid->raw);
            $object->bids       = Utils::expand('batch', 'bids', $data->expand ?? null) ?
                parent::bids()->get(null, [], (object)[ 'batch' => $data->batch, 'expand' => $data->expand ?? null ], true)->result :
                $object->href.'/bids';
                if (!DEVELOP && !$local) foreach ($object->bids as $bidIdx => $bid) unset($bid->raw);
            $object->active     = Utils::active($dbdata->batch_active);
            //
            return $object;
        }
    }
