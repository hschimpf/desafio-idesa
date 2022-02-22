<?php
    namespace com\example\project\webservice\api;

    use SendGrid;
    use SendGrid\Mail\Mail as SendGrid_Mail;
    use SendGrid\Mail\Attachment as SendGrid_Attachment;

    use net\hdssolutions\api\performance\Performance;
    use com\example\project\webservice\api\logger\Logger;

    use Exception;

    final class Utils {
        public static function checkPassword($password, $data) {
            // check length
            if (strlen($password) < 8)
                return false;
            // check numbers and letters
            if ((preg_match('(\pL)u', $password) + preg_match('(\pN)u', $password) + preg_match('([^\pL\pN])u', $password)) < 2)
                return false;
            // check special chars
            if (!preg_match('/[^a-zA-Z0-9\s]/', $password))
                return false;
            // check with user data
            $data_check = isset($data->firstname) ? "($data->firstname)" : '';
            $data_check .= isset($data->lastname) ? ($data_check != '' ? '|' : '') . "($data->lastname)" : '';
            $data_check .= isset($data->email) ? ($data_check != '' ? '|' : '') . "($data->email)" : '';
            if (preg_match("/($data_check)/i", $password))
                return false;
            // check extra
            if (preg_match('/(.)\\1{3}/i', $password))
                return false;
            if(preg_match('/1234|2345|3456|4567|5678|6789|9876|8765|7654|6543|5432|4321/',$password))
                return false;
            return true;
        }

        public static function active($flag) {
            return
                $flag === true ||
                $flag == 'true' ||
                $flag == 1 ||
                ord($flag) == 1 ||
                strlen($flag) == 0;
        }

        public static function stringRegex($string) {
            return str_replace('*', '%', strpos($string, '*') !== false ? $string : "*{$string}*");
        }

        public static function expand($source, $field, $expand = null) {
            // verificamos si se especifico expand, sino lo obtenemos desde el request
            $expand = $expand !== null ? $expand : '';
            // recorremos los campos a expandir
            foreach (explode(',', $expand) as $expandable) {
                // separamos source.field
                list($sExpandable, $fExpandable) = array_pad(explode('.', $expandable, 2), 2, null);
                // verificamos si no tenemos field
                if ($fExpandable == null || strlen($fExpandable) == 0) {
                    // invertimos las variables
                    $fExpandable = $sExpandable;
                    // almacenamos $source
                    $sExpandable = $source;
                }
                // retornamos si es el campo
                if ($sExpandable == $source && ($fExpandable == $field || $fExpandable == '*'))
                    // retornamos true
                    return true;
            }
            // retornamos false
            return false;
        }

        public static function pagination($data, $max = 500) {
            // validate max
            if (isset($data->count) && $data->count > $max) throw new Exception('Maximun value for count is '.$max, 400);
            // get count param
            $count = isset($data->count) ? $data->count : 250;
            // get offset (page * limit)
            $offset = isset($data->page) ? $data->page * $count : 0;
            // return SQL LIMIT
            return " LIMIT $offset, $count";
        }

        public static function paginationStats($data, $total) {
            // return total of pages
            return $total > (isset($data->count) ? $data->count : 250) ? floor($total / (isset($data->count) ? $data->count : 250)) : 1;
        }

        public static function speedBumper($expire = 300, $file = null, $ip = null) {
            // destination file
            if ($file === null) $file = SERVICE_PATH.'/tmp/speed-bumper.json';
            // source IP
            if ($ip === null) $ip =
                // save cli
                substr(php_sapi_name(), 0, 3) == 'cli' ? php_sapi_name() :
                // save IP
                ($_SERVER['HTTP_X_WEBSERVICE_CONNECTOR'] ?? $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CLIENT_IP']);

            // open speed bumper data
            $bumper = file_exists(SERVICE_PATH.'/tmp/speed-bumper.json') ?
                json_decode(file_get_contents(SERVICE_PATH.'/tmp/speed-bumper.json')) :
                (object)[];

            // get/save current IP into bumper
            if (!isset($bumper->$ip)) $bumper->$ip = (object)[ 'times' => [] ];
            // remove expired requests
            foreach ($bumper->$ip->times as $timeIdx => $time)
                // expire after 5min (default)
                if (time() - $time >= $expire) unset($bumper->$ip->times[$timeIdx]);
            // reorder array
            $bumper->$ip->times = array_values($bumper->$ip->times);
            // add current time
            $bumper->$ip->times[] = time();
            // save json
            file_put_contents(SERVICE_PATH.'/tmp/speed-bumper.json', json_encode($bumper));

            // get time to sleep
            $ms = 0; foreach ($bumper->$ip->times as $timeIdx => $time)
                // first request count as 0 extra time
                $ms += pow($timeIdx > 0 ? $timeIdx + 1 : $timeIdx, 3);

            // return speedbumper seconds
            return [ $ip, $ms ];
        }

        public static function parseTitle($html_file) {
            // validate file
            if (!file_exists($html_file)) return null;
            // get <title> tag
            preg_match('/<title[^>]*?>[\s\S]*?<\/title>/im', file_get_contents($html_file), $title);
            // return title
            return strip_tags(isset($title[0]) ? $title[0] : null);
        }

        public static function parseImages($html_file) {
            // validate file
            if (!file_exists($html_file)) return [];
            // get all <img> tags
            preg_match_all('/<img[^>]+/i', file_get_contents($html_file), $imgs);
            // check if we found
            if (isset($imgs[0])) {
                //
                $imgs = $imgs[0];
                // foreach <img>
                foreach ($imgs as $imgIdx => $img) {
                    // get <img> src attr
                    preg_match_all('/(src)=("[^"]*")/i', $img, $data);
                    // save <img> data
                    $imgs[$imgIdx] = [
                        'img'   => $img.'>',
                        'src'   => isset($data[2]) ? str_replace('"', '', $data[2][0]) : null,
                        'cid'   => null
                    ];
                    // check if we found src
                    if (isset($data[2])) {
                        // save cid
                        $imgs[$imgIdx]['cid'] = 'imgembed'.$imgIdx;
                        // replace src with cid
                        $imgs[$imgIdx]['embed'] = str_replace($imgs[$imgIdx]['src'], 'cid:'.$imgs[$imgIdx]['cid'], $imgs[$imgIdx]['img']);
                    }
                }
            }
            // return imgs array
            return $imgs;
        }

        public static function reduce($array, $parent = null) {
            // force array
            $array = is_object($array) ? json_decode(json_encode($array), true) : $array;
            //
            $output = [];
            // foreach array elements
            foreach ($array as $key => $element) {
                // check if element is array
                if (is_array($element) || is_object($element)) {
                    // reduce element
                    $temp = self::reduce($element, $parent !== null ? $parent.".$key" : $key);
                    // append elements to current array
                    $output = array_merge($output, $temp);
                } else
                    // copy element to output
                    $output[$parent !== null ? $parent.".$key" : $key] = $element;
            }
            //
            return $output;
        }

        public static function enclosureKeys($array, $begin = '{', $end = '}') {
            // foreach array
            foreach ($array as $oldKey => $value) {
                // enclosure key
                $newKey = $begin.$oldKey.$end;
                // add new key on array
                $array[$newKey] = $value;
                // remove old key
                unset($array[$oldKey]);
            }
            //
            return $array;
        }

        public static function sendmail(string $email_file, string $to_email, string $to_name, array $data, bool $output = false) {
            //
            Logger::debug('Utils::sendemail', 'Using SendGrid Key: '.substr(SENDGRID_KEY, 0, 10).'...'.substr(SENDGRID_KEY, strlen(SENDGRID_KEY) - 10), $output);
            Performance::start('sendgrid');
            // connect to SendGrid
            $sendgrid = new SendGrid(SENDGRID_KEY);
            // create mail
            $email = new SendGrid_Mail();
            $email->setFrom(SENDGRID_FROM_EMAIL, SENDGRID_FROM_NAME);
            $email->addTo($to_email, $to_name);
            $email->addBcc('no-reply@example.com', 'Example Project');

            // get title
            $title = self::parseTitle(SERVICE_PATH.'/data/mail/'.$email_file.'.html');
            // get HTML mail
            $html = file_get_contents(SERVICE_PATH.'/data/mail/'.$email_file.'.html');
            // get plain mail
            $plain = file_get_contents(SERVICE_PATH.'/data/mail/'.$email_file.'.plain');

            // parse images
            $imgs = self::parseImages(SERVICE_PATH.'/data/mail/'.$email_file.'.html');
            // foreach images
            foreach ($imgs as $img) {
                // replace img with embedd in HTML
                $html = str_replace($img['img'], $img['embed'], $html);
                // create attachment
                $attachment = new SendGrid_Attachment();
                $attachment->setContent(base64_encode(file_get_contents(SERVICE_PATH.'/data/mail/'.$img['src'])));
                $attachment->setType(mime_content_type(SERVICE_PATH.'/data/mail/'.$img['src']));
                $attachment->setContentID($img['cid']);
                $attachment->setDisposition('inline');
                $attachment->setFilename(basename($img['src']));
                // add attachment into email
                $email->addAttachment($attachment);
            }

            // parse title mail
            $email->setSubject(self::parse($title, $data));
            // parse HTML mail
            $email->addContent('text/html', self::parse($html, $data));
            // parse plain mail
            $email->addContent('text/plain', self::parse($plain, $data));

            try {
                //
                Logger::debug('Utils::sendemail', 'Sending email "'.$email_file.'" to '.$to_name.' ('.$to_email.')', $output);
                // send email
                $sent = $sendgrid->send($email);
                //
                Performance::end('sendgrid');
                // save response to log
                Logger::debug('Utils::sendemail', 'SendGrid response: '.$sent->statusCode().': '.($sent->statusCode() !== 202 ? $sent->body() : 'Success'), $output);
            } catch (Exception $e) {
                // save error to debug
                Logger::debug('Utils::sendemail', 'Failed to send email: '.$e->getMessage(), $output);
            }
        }

        private static function parse($contents, array $data = []) {
            // enclosure keys into {key}
            $data = self::enclosureKeys($data);
            // foreach data
            foreach ($data as $key => $value) {
                // check if is an array
                if (is_array($value)) {
                    // find contents between {key} and {/key}
                    preg_match_all('/({'.str_replace(['{','.','}'], ['','\.',''], $key).'})(.+?)({\/'.str_replace(['{','.','}'], ['','\.',''], $key).'})/s', $contents, $matches);
                    // foreach every match
                    foreach ($matches[0] as $matchIdx => $match) {
                        // get content to iterate and reemplace
                        $content = $matches[2][$matchIdx];
                        // foreach value
                        $replaced = [];
                        foreach ($value as $value_data)
                            // replace content
                            $replaced[] = self::parse($content, $value_data);
                        // replace match with replaced objects
                        $contents = str_replace($match, implode('', $replaced), $contents);
                    }
                } else
                    // replace in contentes
                    $contents = str_replace($key, $value, $contents);
            }
            // return parsed contents
            return $contents;
        }
    }
