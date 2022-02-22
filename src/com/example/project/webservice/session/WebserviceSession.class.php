<?php
    namespace com\example\project\webservice\session;

    use SessionHandlerInterface;

    final class WebserviceSession implements SessionHandlerInterface {
        /**
         * Singleton instance
         * @var WebserviceSession
         */
        private static $instance = null;

        /**
         * [$path description]
         * @var [type]
         */
        private $path;

        /**
         * [setSession description]
         * @param [type] $name [description]
         */
        public static function setSession($name) {
            //
            //$name = base64_decode($name);
            //
            session_id($name);
        }

        /**
         * [getInstance description]
         * @param  [type] $path [description]
         * @return [type]       [description]
         */
        public static function getInstance($path) {
            //
            if (self::$instance === null)
                //
                self::$instance = new WebserviceSession($path);
            //
            return self::$instance;
        }

        /**
         * Disable public constructor for Singleton
         * @param [type] $path [description]
         */
        private function __construct($path) {
            //
            $this->path = $path;
        }

        /**
         * [open description]
         * @param  [type] $path [description]
         * @param  [type] $sid  [description]
         * @return [type]       [description]
         */
        public function open($path, $sid) {
            // check if session files dir exists
            if (!is_dir($this->path.'/sessions'))
                // create session files dir
                mkdir($this->path.'/sessions', 0777);
            //
            if (!file_exists($this->path.'/sessions/ws_'.$sid.'.session'))
                //
                touch($this->path.'/sessions/ws_'.$sid.'.session');
            //
            return true;
        }

        /**
         * [close description]
         * @return [type] [description]
         */
        public function close() {
            // return apiErrorHandler
            return apiErrorHandler();
        }

        public function read($sid) {
            //
            if (!file_exists($this->path.'/sessions/ws_'.$sid.'.session'))
                //
                $this->open(null, $sid);
            //
            return file_get_contents($this->path.'/sessions/ws_'.$sid.'.session');
        }

        public function write($sid, $data) {
            //
            return file_put_contents($this->path.'/sessions/ws_'.$sid.'.session', $data) === true;
        }

        public function destroy($sid) {
            //
            if (file_exists($this->path.'/sessions/ws_'.$sid.'.session'))
                //
                unlink($this->path.'/sessions/ws_'.$sid.'.session');
            //
            return true;
        }

        public function gc($ttl) {
            //
            foreach (glob($this->path.'/sessions/ws_*') as $file)
                //
                if (filemtime($file) + $ttl < time() && file_exists($file))
                    //
                    unlink($file);
            //
            return true;
        }
    }