<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "tablebase.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "accesscontrolledobject.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "shoplist.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "permissions.php";


if (!class_exists('ListFunctorShopList')) {

class ListFunctorShopList
{
    public $_shoplist;

    function __construct(& $shoplist_array)
    {
        $this->_shoplist = & $shoplist_array;
    }

    public function iterate_row(ShoppingList $shop_list)
    {
        $this->_shoplist[] = $shop_list;
    }
}
}

if (!class_exists('ShopListTable')) {
	
class ShopListTable extends TableBase
{
	const kTableName = 'shoplists';
	const kCol_ListID = 'listID';
	const kCol_ListName = 'listName';
	const kCol_UserID = 'userID_FK';
	const kCol_ItemCount = 'itemCount';
	
	function __construct($db_base, $user_ID)
	{
		parent::__construct($db_base, $user_ID);
	}
	
	function list_all(
		$fetch_count, 
		&$functor_obj, 
		$function_name='iterate_row')
	{
		if ($fetch_count > 0) {
				
			$sql = sprintf("SELECT 
								sl.listID, 
								sl.listName,
								sl.userID_FK, 
								(SELECT 
									COUNT(`listID_FK`) 
								FROM 
									`shopitems` si 
								WHERE 
									si.`listID_FK`=sl.`listID` AND si.`isPurchased` <= 0) AS itemCount,
								sls.user_perms,
								sls.user_id_FK
							FROM 
								shoplists sl
							LEFT JOIN 
								shoplists_sharing sls
							ON 
								sl.`listID`=sls.`list_id_FK`
							WHERE 
								sl.`userID_FK`=%d OR sls.`user_id_FK`=%d",
					parent::GetUserID(),
					parent::GetUserID());
 
		} else {
				
			$sql = sprintf("SELECT 
								sl.listID, 
								sl.listName, 
								sl.userID_FK,
								sls.user_perms,
								sls.user_id_FK
							FROM 
								shoplists sl
							LEFT JOIN 
								shoplists_sharing sls
							ON 
								sl.`listID`=sls.`list_id_FK`
							WHERE 
								sl.userID_FK=%d OR sls.`user_id_FK`=%d",
					parent::GetUserID(),
					parent::GetUserID());
		}	
		
		$result = $this->get_db_con()->query($sql);
		if ($result == FALSE) {
			throw new Exception("SQL exec failed (" . $this->get_db_con()->errno . ")");
		}
		
		while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {

			$thisList = ShoppingList::create_from_fields(
					intval($row['listID']),
					$row['listName'],
					intval($row['userID_FK']),
					($fetch_count > 0) ? intval($row['itemCount']) : 0,
					!is_null($row['user_id_FK']) ? intval($row['user_id_FK']) : 0,
					!is_null($row['user_perms']) ? intval($row['user_perms']) : 0);

			if (Permissions::can_read(parent::GetUserID(), $thisList)) {
				call_user_func(
					array($functor_obj, $function_name), // invoke the callback function
					$thisList);
			}
		}
		
		if ($result)
			$result->free();
	}
	
	function get_list($list_id) {
		
		return ShoppingList::create_from_db($this->get_db_con(), $list_id, parent::GetUserID());		
	}
	
	function add_list($list_name)
	{
		$sql = sprintf(
				"INSERT INTO `%s` (`%s` , `%s`) VALUES ('%s', %d)", 
				self::kTableName, 
				self::kCol_ListName, 
				self::kCol_UserID, 
				$this->get_db_con()->escape_string($list_name),
				parent::GetUserID());

		$result = $this->get_db_con()->query($sql);
		if ($result == FALSE) {
			throw new Exception(
				"Failed to add Shopping List (" . 
				$this->get_db_con()->errno . ")");
		}
		
		return $this->get_db_con()->insert_id;
	}
	
	function remove_list($list_ID) {
		
		global $config;
		$isTransactional = FALSE;
		
		if (!$config['USE_STORED_PROCS']) {

			try {
				$this_list = $this->get_list($list_ID);
				
				if (Permissions::can_delete(parent::GetUserID(), $this_list)) {				
					
					$isTransactional = $this->get_db_con()->autocommit(FALSE);
					if ($isTransactional == FALSE) {
						throw new Exception("Could Not Start Transaction.");
					}
					
					$sql = sprintf("DELETE FROM `shopitems` 
									WHERE `" . self::kCol_UserID . "`=%d AND `listID_FK`=%d",
									$this_list->getOwnerId(),
									$list_ID);
				
					$result = $this->get_db_con()->query($sql);
					if ($result == FALSE) {
						throw new Exception(
							"Error deleting Shopping List (" . 
							$this->get_db_con()->errno . ")");
					}

					$sql = sprintf("DELETE FROM shoplists_sharing
									WHERE list_ID_FK=%d",
									$list_ID);
					
					$result = $this->get_db_con()->query($sql);
					if ($result == FALSE) {
						throw new Exception(
								"Error deleting Shopping List (" .
								$this->get_db_con()->errno . ")");
					}
						
					$sql = sprintf(
						"DELETE FROM `%s` WHERE `%s`=%d AND `%s`=%d",
						self::kTableName,
						self::kCol_ListID,
						$list_ID,
						self::kCol_UserID,
						parent::GetUserID());
		
					$result = $this->get_db_con()->query($sql);
					if ($result == FALSE) {
						throw new Exception(
							"Error deleting Shopping List (",
							$this->get_db_con()->errno . ")");
					}
					
					$this->get_db_con()->commit();
					$this->get_db_con()->autocommit(TRUE);
					
				} else {
					throw new Exception("You don't have permissions to perform this operation.");
				}
			} catch (Exception $e) {
					
				if ($isTransactional) {
					$this->get_db_con()->rollback();
					$this->get_db_con()->autocommit(TRUE);
				}
				
				throw $e;
			}
			
		} else {
			
	        $sql = sprintf(
				"call delete_shopping_list(%d, %d)",
				$list_ID, 
				$this->GetUserID());
				
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {
				throw new Exception(
					"Error Deleting Shopping List (" . 
					$this->get_db_con()->error . ")");
			}
		}
	}
	
	function edit_list($list_ID, $list_name)
	{
		$this_list = $this->get_list($list_ID);
		
		if (Permissions::can_write(self::GetUserID(), $this_list)) {
			
			$sql = sprintf(
					"UPDATE %s SET `listName`='%s' WHERE `listID`=%d AND `userID_FK`=%d", 
					self::kTableName,
					$this->get_db_con()->escape_string($list_name),
					$list_ID,
					$this->GetUserID());
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {
				throw new Exception ("Failed to rename list: (" . $this->get_db_con()->errno);
			}
		} else {
			throw new Exception("You don't have permissions to perform this operation.");
		}
	}
}
}
?>
