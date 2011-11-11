<?php
require_once $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "noteit.web/model/DBBase.php";

class TableBase extends DBBase
{
	protected $col_count 	= 0;
	protected $col_names 	= array();
	protected $db_table_name = "";
	protected $db_user_ID = 0;
	
	/*
	** Initialize the object with some properties of the table
	*/
	function __construct($table_name, $user_ID)
	{
        parent::__construct();
        
		if (is_null($table_name) || $table_name == "")
			throw new Exception('Null databse name');
		
		$this->db_table_name = $table_name;
		$this->db_user_ID = $user_ID;

		$sql = "DESCRIBE $table_name";
//		echo $sql;
		$result = $this->get_db_con()->query($sql);
		if ($result == FALSE)
			throw new Exception("Database operation failed (" . __FILE__ . __LINE__ . "): " . $this->db_con->error);
		
		while($row = mysqli_fetch_array($result))
		{
			$this->col_names[] = $row['Field'];
			$this->col_count++;
		}
		
		if ($result)
		{
			mysqli_free_result($result);
		}
	}
	
	function __destruct()
	{
		unset($this->col_names);
	}
	
	
	function GetTableName()
	{
		return $this->db_table_name;
	}
	
	function GetUserID()
	{
		return $this->db_user_ID;
	}
	
	function ListAll($functor_obj, $function_name)
	{
		$sql = "SELECT * FROM $this->table_name";
		$result = $this->get_db_con()->query($sql);
		
		while($row = mysqli_fetch_array($result))
		{
			call_user_func(array($functor_obj, $function_name), $row);
		}
	}
	
	function Add($row, $start_col)
	{
		$sql = "INSERT INTO 'db_table_name' (";
		$field_sql = "";
		$value_sql = " VALUES (";
		
		for ($i = $start_col; i < $this->col_count; $i++)
		{
			$field_sql .= "'";
			$field_sql .= $this->col_names[$i];
			$field_sql .= "'";
			
			$value_sql .= "'";
			$value_sql .= $row[$i];
			$value_sql .= "'";
			
			if ($i < ($this->col_count - 1))
			{
				$sql .= ',';
				$value_sql .= ',';
			}
			else
			{
				$sql .= ")";
				$value_sql .= ")";
			}
		}
			
		$sql = $sql . $field_sql . $value_sql;
		$result = $this->get_db_con()->query($sql);
		if (!$result)
			throw new Exception("Could not add record into dabatabse");
	}
}
?>