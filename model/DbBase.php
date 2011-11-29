<?php
defined('kServer') or define('kServer', '127.0.0.1');
defined('kUserName') or define('kUserName', 'root');
defined('kPassword') or define('kPassword', 'pass123');
defined('kDatabaseName') or define('kDatabaseName', 'noteitdb');

if(class_exists('DbBase') != TRUE)
{
    class DbBase
    {
        /*
         *  Use this class as the base class if you need to connect to a database
         */
        private $db_con;

        protected function __construct()
        {
            $this->db_con = NULL;

            // Connect to the database
            $this->get_db_con();
        }

        public function __destruct()
        {
            $this->db_con = NULL;
        }

        public function get_db_con()
        {
            if ($this->db_con == NULL)
            {
                $this->db_con = new MySQLi(kServer, kUserName, kPassword, kDatabaseName);
                if ($this->db_con->connect_error)
                {
                    throw new Exception('Could not connect to Server: ' . $this->db_con->error);
                }
            }

            return $this->db_con;
        }
    }
}
?>
