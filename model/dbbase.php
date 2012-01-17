<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../config/config.php');
if(class_exists('DbBase') != TRUE)
{
    class DbBase
    {
        /*
         *  Use this class as the base class if you need to connect to a database
         */
        private $db_con = NULL;
		private $ref_count = 0;
		
        protected function __construct() {
            
            // Connect to the database
            $this->connect_to_database();
			$this->add_ref();
        }
		
        public function __destruct() {
			
			$this->release();
        }

		public function add_ref() {
			$this->ref_count++;
		}
		
		public function release() {

        	$this->ref_count -=1;
        	
        	if ($this->db_con && $this->ref_count == 0) {
	            //NI::TRACE_ALWAYS("Closing Database Connection", "", "");
            	$this->db_con->close();
				$this->db_con = NULL;
			}
		}

		private function connect_to_database() {
            global $config;
            
            if ($this->db_con == NULL) {
	            //NI::TRACE_ALWAYS("Creating Database Connection", "", "");
                $this->db_con = new MySQLi(
                	$config['MYSQL_SERVER'], 
                	$config['MYSQL_USER'], 
                	$config['MYSQL_PASSWD'], 
                	$config['MYSQL_DB']);
					
                if ($this->db_con->connect_error) {
                    throw new Exception('Could not connect to Server: ' . $this->db_con->error);
                }
				
				if (!$this->db_con->set_charset("utf8")) {
					throw new Exception('Could not set charset to utf8. PHP version 5.2.3 or greater required');			
				}
            }
		}
		
        public function get_db_con() {
            return $this->db_con;
        }
    }
}
?>
