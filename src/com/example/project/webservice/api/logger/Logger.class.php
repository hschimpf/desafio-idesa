<?php
    namespace com\example\project\webservice\api\logger;

    use Exception;

    final class Logger {
        /**
         * Singleton
         */
        private static $instance = null;

        /**
         * Session ID
         */
        private $session = 'anonymous';

        /**
         * Process instance
         */
        private $pinstance = null;

        /**
         * Remote IP Address
         */
        private $remoteip = null;

        /**
         * User agent
         */
        private $useragent = null;

        /**
          * Log file handle
          */
        private $logfile;

        private static $directory = null;
        private static $filename = null;

        /**
         * Disable public constructor, using Singleton
         */
        private function __construct() {
            // check for session instance
            if (isset($_SESSION[__CLASS__.'_session']))
                // load current session
                $this->session = $_SESSION[__CLASS__.'_session'];
            // get remote IP address
            $this->remoteip = substr(php_sapi_name(), 0, 3) == 'cli' ? php_sapi_name() : ($_SERVER['REMOTE_ADDR'] ?: ($_SERVER['HTTP_X_FORWARDED_FOR'] ?: $_SERVER['HTTP_CLIENT_IP']));
            // get user agent
            $this->useragent = substr(php_sapi_name(), 0, 3) == 'cli' ? php_sapi_name() : ($_SERVER['HTTP_USER_AGENT'] ?: 'unknown');
            // create a new process ID
            $this->pinstance = substr(md5(rand()), 10, 10);
            // set default directory and filename to logs/output.log
            if (self::$directory === null) self::$directory = 'logs';
            if (self::$filename === null) self::$filename = 'output.log';
            // check logs folder permissions
            if (file_exists(self::$directory) && !is_writable(self::$directory))
                throw new Exception('Logs directory is not writable. Check permissinos');
            // try to create output file
            if (!file_exists(self::$directory.'/'.self::$filename) &&
                !touch(self::$directory.'/'.self::$filename))
                throw new Exception('Log file could not be created');
            // check log file permissions
            if (file_exists(self::$directory.'/'.self::$filename) &&
                !is_writable(self::$directory.'/'.self::$filename))
                throw new Exception('Log file is not writable. Check permissinos');
            // open log file
            $this->logfile = fopen(self::$directory.'/'.self::$filename, 'a');
            // check file handle
            if ($this->logfile === false) throw new Exception('Log file could not be opened');
            // save process start time
            $this->startTime = explode(' ', microtime());
            $this->startTime = $this->startTime[0] + $this->startTime[1];
        }

        public static function config($configs) {
            // save log directory
            isset($configs['directory']) && self::$directory = $configs['directory'];
            // save log filename
            isset($configs['filename']) && self::$filename = $configs['filename'];
        }

        public function __destruct() {
            // close handle
            if ($this->logfile) fclose($this->logfile);
        }

        private static function getInstance() {
            // init singleton
            if (self::$instance === null) self::$instance = new Logger();
            // return singleton
            return self::$instance;
        }

        public function log($level, $namespace, $message, array $context = []) {
            // check if we need to ouput
            // if ($this->logLevels[$this->logLevelThreshold] < $this->logLevels[$level]) return;
            // build message
            $message = $this->message($level, $namespace, $message);
            // write message to log file
            $this->write($message);
        }

        private function write($message) {
            // check file handle
            if ($this->logfile == null) return;
            // write log line
            if (fwrite($this->logfile, $message.PHP_EOL) === false) throw new Exception('Log line could not be written');
        }

        private function message($level, $namespace, $message) {
            // default message format
            $format = '{date} {time} @{elapsed} [{level}] {namespace} {session}@{remoteip} ~ {useragent} #{pinstance} {message}';
            // fields and values
            $replacements = [
                'date'      => date('Y-m-d'),
                'time'      => date('H:i:s'),
                'elapsed'   => $this->getElapsedTime(),
                'level'     => $level,
                'namespace' => $namespace,
                'session'   => $this->session,
                'remoteip'  => $this->remoteip,
                'useragent' => $this->useragent,
                'pinstance' => $this->pinstance,
                'message'   => $message,
            ];
            // enclosure keys into {key}
            $replacements = $this->enclosureKeys($replacements);
            // replace data and return message
            return str_replace(array_keys($replacements), array_values($replacements), $format);
        }

        private function enclosureKeys($array, $begin = '{', $end = '}') {
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

        private function getElapsedTime() {
            // get current time
            $time = explode(' ', microtime());
            // return elapsed since start
            return str_pad(number_format($time[1] + $time[0] - $this->startTime, 3), 7, '0', STR_PAD_LEFT);
        }

        public static function newSession($sid = null) {
            // save session ID
            self::getInstance()->session =
                $_SESSION[__CLASS__.'_session'] =
                $sid != null ? $sid : 'anonymous';
        }

        public static function __callStatic($name, $arguments) {
            // get instance
            $instance = self::getInstance();
            // check if we need to output to console
            if (isset($arguments[2]) && $arguments[2] === true)
                // show message on current output
                echo $instance->message($name, $arguments[0], $arguments[1])."\n";
            // redirect message to log
            return $instance->log($name, $arguments[0], $arguments[1]);
        }
    }