<?php
    namespace com\example\project\webservice\api\connector\cache;

    use com\example\project\webservice\api\logger\Logger;
    use Exception;

    final class Cache {
        /**
         * [$cache description]
         * @var array
         */
        private static $cache = [];

        public static function get($identifier) {
            //
            if (!isset(self::$cache[$identifier])) return null;
            //
            return self::$cache[$identifier];
        }

        public static function set($identifier, $data) {
            //
            self::$cache[$identifier] = $data;
        }

        public static function fetch($module) {
            //
            Logger::info('LOCAL', "Finding cache for /$module");
            // 15min cache
            if (file_exists(SERVICE_PATH."/tmp/$module.cache")) {
                //
                if (time() - filemtime(SERVICE_PATH."/tmp/$module.cache") <= 60 * 15) {
                    //
                    $cache = json_decode(file_get_contents(SERVICE_PATH."/tmp/$module.cache"));
                    //
                    if ($cache !== false) {
                        // return cache
                        Logger::info('LOCAL', 'Cache found, returning cached data');
                        //
                        return $cache;
                    }
                    //
                    Logger::warning('LOCAL', 'Cache parse failed, fetching from WS');
                    //
                    unlink(SERVICE_PATH."/tmp/$module.cache");
                } else {
                    //
                    Logger::warning('LOCAL', 'Cache expired, fetching from WS');
                    //
                    unlink(SERVICE_PATH."/tmp/$module.cache");
                }
            } else
                //
                Logger::warning('LOCAL', 'Cache not found, fetching from WS');
            //
            return null;
        }

        public static function save($module, $data) {
            //
            if (!file_exists(SERVICE_PATH."/tmp/$module.cache") || file_exists(SERVICE_PATH."/tmp/$module.cache") && time() - filemtime(SERVICE_PATH."/tmp/$module.cache") >= 60 * 20) {
                //
                Logger::info('LOCAL', 'Saving data to cache');
                //
                file_put_contents(SERVICE_PATH."/tmp/$module.cache", json_encode($data));
            }
        }
    }