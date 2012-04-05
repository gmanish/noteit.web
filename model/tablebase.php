<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "dbbase.php";

class TableBase {

	protected $db_user_ID = 0;
	protected $db_base = NULL;
	
	/*
	 ** Initialize the object with some properties of the table
	 *  $my_dbbase_obj: DbBase object instanse
	 */
	function __construct($my_dbbase_obj, $user_ID) {
			
		if (is_null($my_dbbase_obj)) {
			throw new Exception('Could Not Connect to Database.');
		} 
		
		$this->db_user_ID = $user_ID;
		$this->db_base = $my_dbbase_obj;
		$this->db_base->add_ref();
	}

	function __destruct() {
		$this->db_base->release();
	}

	function GetUserID() {
		return $this->db_user_ID;
	}
	
	public function get_db_object() {
		return $this->db_base;	
	}
	
    public function get_db_con() {
        return $this->db_base->get_db_con();
    }
	
	public function get_user_currency() {
		return $this->db_base->get_db_userCurrency();
	}
}
?>