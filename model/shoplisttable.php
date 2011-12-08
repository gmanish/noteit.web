<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "tablebase.php";

class ListFunctorShopList
{
    public $_shoplist;

    function __construct(& $shoplist_array)
    {
        $this->_shoplist = & $shoplist_array;
    }

    public function iterate_row($list_id, $list_name)
    {
        $thisItem = array(
            ShopListTable::kCol_ListID => $list_id,
            ShopListTable::kCol_ListName => $list_name
            );

        $this->_shoplist[] = $thisItem;
    }
}

class ShopListTable extends TableBase
{
	const kTableName = 'shoplists';
	const kCol_ListID = 'listID';
	const kCol_ListName = 'listName';
	const kCol_UserID = 'userID_FK';
	
	function __construct($user_ID)
	{
		parent::__construct('shoplists', $user_ID);
	}
	
	function list_all(&$functor_obj, $function_name='iterate_row')
	{
		$sql = sprintf(
				"SELECT * FROM `%s` WHERE `userID_FK`=%d", 
				parent::GetTableName(),
				parent::GetUserID());
		
		$result = $this->get_db_con()->query($sql);
		if ($result == FALSE)
			throw new Exception("SQL exec failed (". __FILE__ . __LINE__ . "): $this->get_db_con()->error");
			
		while ($row = mysqli_fetch_array($result))
		{
			call_user_func(
				array($functor_obj, $function_name), // invoke the callback function
				$row[0], // 'listID 
				$row[1]); // 'listName		}
		}
		
		if ($result)
			$result->free();
	}
	
	function add_list($list_name)
	{
		$sql = sprintf(
				"INSERT INTO `%s` (`%s` , `%s`) VALUES ('%s', %d)", 
				parent::GetTableName(), 
				self::kCol_ListName, 
				self::kCol_UserID, 
				$this->get_db_con()->escape_string($list_name),
				parent::GetUserID());
	//	echo($sql);
        
		$result = $this->get_db_con()->query($sql);
		if ($result == FALSE)
			throw new Exception("SQL exec failed (" . __FILE__ . __LINE__ . "): " . $this->get_db_con()->error);
		
		return $this->get_db_con()->insert_id;
	}
	
	// [TODO] : Make this a transaction
	function remove_list($list_ID)
	{
        $sql = sprintf(
				"call delete_shopping_list(%d, %d)",
				$list_ID, 
				$this->GetUserID());
		$result = $this->get_db_con()->query($sql);
		if ($result == FALSE)
			throw new Exception("Database operaion failed (" . __FILE__ . __LINE__ . "): " . $this->get_db_con()->error .
                "\nActual SQL: " . $sql);
	}
	
	function edit_list($list_ID, $list_name)
	{
		$sql = sprintf(
				"UPDATE %s SET `listName`='%s' WHERE `listID`=%d AND `userID_FK`=%d", 
				parent::GetTableName(),
				$this->get_db_con()->escape_string($list_name),
				$list_ID,
				$this->GetUserID());
		$result = $this->get_db_con()->query($sql);
		if ($result == FALSE)
			throw new Exception ("Failed to rename list: " . $sql);
	}
}
?>