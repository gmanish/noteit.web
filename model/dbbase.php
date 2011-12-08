<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../config/config.php');
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
            global $config;
            if ($this->db_con == NULL)
            {
                $this->db_con = new MySQLi(
                	$config['MYSQL_SERVER'], 
                	$config['MYSQL_USER'], 
                	$config['MYSQL_PASSWD'], 
                	$config['MYSQL_DB']);
					
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
