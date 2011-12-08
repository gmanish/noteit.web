<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../lib/noteitcommon.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tablebase.php';


class ShopItem
{
    const SHOPITEM_INSTANCEID       = 1;    // 1 << 0
    const SHOPITEM_USERID           = 2;    // 1 << 1
    const SHOPITEM_LISTID           = 4;    // 1 << 2
    const SHOPITEM_CATEGORYID       = 8;    // 1 << 3
    const SHOPITEM_ITEMNAME         = 16;   // 1 << 4
    const SHOPITEM_UNITCOST         = 32;   // 1 << 5
    const SHOPITEM_QUANTITY         = 64;   // 1 << 6
    const SHOPITEM_UNITID           = 128;  // 1 << 7
    const SHOPITEM_DATEADDED        = 256;  // 1 << 8
    const SHOPITEM_DATEPURCHASED    = 512;  // 1 << 9
	const SHOPITEM_CLASSID			= 1024; // 1 << 10
	const SHOPITEM_ISPURCHASED		= 2048; // 1 << 11
	const SHOPITEM_ISASKLATER		= 4096; // 1 << 12

    public $_instance_id = 0;
    public $_item_id = 0;
    public $_user_id = 0;
    public $_list_id = 0;
    public $_category_id = 0;
    public $_item_name = '';
    public $_unit_cost = 0.00;
    public $_quantity = 0.00;
    public $_unit_id = 0;
    public $_date_added;
    public $_date_purchased;
	public $_is_purchased = 0; // TINYINT should be 0 or 1
	public $_is_asklater = 0; // TINYINT should be 0 or 1
	
	function __construct(
        $instance_id,
        $item_id = 0,
        $user_id = 0,
        $list_id = 0,
        $category_id = 0,
        $item_name = '',
        $unit_cost = 0.00,
        $quantity = 1.00,
        $unit_id = 3 /* General Unit */,
		$is_purchased = FALSE,
		$is_asklater = FALSE)
    {
        $this->_instance_id = $instance_id;
        $this->_item_id = $item_id;
        $this->_user_id = $user_id;
        $this->_list_id = $list_id;
        $this->_category_id = $category_id;
        $this->_item_name = $item_name;
        $this->_unit_cost = $unit_cost;
        $this->_quantity = $quantity;
        $this->_unit_id = $unit_id;
		$this->_is_purchased = $is_purchased;
		$this->_is_asklater = $is_asklater;
    }
}

class ShopItems extends TableBase
{
    const kTableName        = 'shopitems';
    const kColInstanceID    = 'instanceID';
    const kColUserID        = 'userID_FK';
    const kColItemID        = 'itemID_FK';
    const kColItemName      = 'itemName';
    const kColDateAdded     = 'dateAdded';
    const kColDatePurchased = 'datePurchased';
    const kColListID        = 'listID_FK';
    const kColUnitCost      = 'unitCost';
    const kColQuantity      = 'quantity';
    const kColUnitID        = 'unitID_FK';
    const kColCategoryID	= 'categoryID_FK';
	const kColIsPurchased	= 'isPurchased';
	const kColIsAskLater	= 'isAskLater';

    function __construct($user_ID)
    {
        parent::__construct(self::kTableName, $user_ID);
    }

    function add_item($list_id, $category_id, $item_name, $unit_cost, $item_quantity, $unit_id, $is_asklater)
    {
        $sql = sprintf(
				"SELECT add_shop_item(%d, %d, %d, '%s', %.2f, %.2f, %d, %d)", 
				parent::GetUserID(), 
				$list_id, 
				$category_id, 
				$this->get_db_con()->escape_string($item_name), 
				$unit_cost, 
				$item_quantity,
				$unit_id,
				$is_asklater);

        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE || mysqli_num_rows($result) <= 0)
		{
			$str = sprintf(
				"SQL exec failed (%s, %s) : %s (%d)",
				__FILE__,
				__LINE__, 
				$this->get_db_con()->error, 
				$this->get_db_con()->errno);
			throw new Exception($str); 
		}
        $row = $result->fetch_row();
		$result->free();
        return $row[0];
    }

    function list_range(
        $show_purchased_items,
        $list_id,
        $start_at,
        $num_rows_fetch,
        & $functor_obj,
        $function_name="iterate_row")
    {
        $sql = sprintf(
				"SELECT si.*, sic.itemName FROM `%s` AS si " .
				"INNER JOIN `shopitemscatalog` AS sic " .
				"ON si.itemID_FK=sic.itemID WHERE si.userID_FK=%d AND si.listID_FK=%d",
                parent::GetTableName(),
                parent::GetUserID(),
                $list_id);

        if ($show_purchased_items == FALSE) // Hide items that have been purchased
            $sql = $sql . " AND si.datePurchased IS NULL";

		$sql = sprintf("%s LIMIT %d, %d", $sql, $start_at, $num_rows_fetch);

        NI::TRACE('ShopItems::list_range SQL: ' . $sql, __FILE__, __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);

        NI::TRACE($sql, __FILE__, __LINE__);
        while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
        {
            NI::TRACE('ShopItems::list_range() returned row: ' . print_r($row, TRUE), __FILE__, __LINE__);
			
            $thisItem = new ShopItem(
                $row[self::kColInstanceID],
				$row[self::kColItemID],
                $row[self::kColUserID],
                $row[self::kColListID],
                $row[self::kColCategoryID],
                $row[self::kColItemName],
                $row[self::kColUnitCost],  // unit cost
                $row[self::kColQuantity],
                $row[self::kColUnitID],
				$row[self::kColIsPurchased],
				$row[self::kColIsAskLater]);

            call_user_func(
                array($functor_obj, $function_name), // invoke the callback function
                $thisItem);

            $thisItem = NULL;
        }

        if ($result)
            $result->free();
    }

    function get_item($instance_id)
    {
        $sql = sprintf("SELECT si.*, sic.itemName 
        		FROM `%s` AS si 
        		inner join `shopitemscatalog` AS sic  
        		ON si.itemID_FK = sic.itemID  
        		WHERE si.userID_FK = %d and si.instanceID = %d  LIMIT 1",
                parent::GetTableName(),
                parent::GetUserID(),
                $instance_id);

        NI::TRACE('ShopItems::get_item SQL: ' . $sql, __FILE__, __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);

        while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
        {
            NI::TRACE('ShopItems::get_item() returned row: ' . print_r($row, TRUE), __FILE__, __LINE__);
            $thisItem = new ShopItem(
                $row[self::kColInstanceID],
                $row[self::kColItemID],
                $row[self::kColUserID],
                $row[self::kColListID],
                $row[self::kColCategoryID],
                $row[self::kColItemName],
                $row[self::kColUnitCost],  // unit cost
                $row[self::kColQuantity],
                $row[self::kColUnitID],
				$row[self::kColIsPurchased],
				$row[self::kColIsAskLater]);

            if ($result)
                $result->free();

            return $thisItem;
        }
    }

    function edit_item($instance_id, $item, $item_flags /* one of SHOPITEM_* flags */)
    {
		$sql = sprintf('UPDATE %s SET', parent::GetTableName());
        $prev_column_added = FALSE;
		
        if ($item_flags & ShopItem::SHOPITEM_CATEGORYID)
        {
            $sql .= ' ' . self::kColCategoryID . '=' . $item->_category_id;
            $prev_column_added = TRUE;
        }

       if ($item_flags & ShopItem::SHOPITEM_ITEMNAME)
        {
		   // If the name has been updated we have to special case it as
		   // names are stored in the `shopitemscatalog` table
		   $item_classid = $this->create_catalog_entry($item);
            if ($prev_column_added)
                $sql .= ', ';
			
            $sql .= ' ' . self::kColItemID . '=' . $item_classid;
            $prev_column_added = TRUE;
        }

		if ($item_flags & ShopItem::SHOPITEM_UNITCOST)
        {
            if ($prev_column_added)
                $sql .= ', ';
            $sql .= ' ' . self::kColUnitCost . '=' . $item->_unit_cost;
            $prev_column_added = TRUE;
        }

		if ($item_flags & ShopItem::SHOPITEM_ISASKLATER)
		{
			if ($prev_column_added)
				$sql .= ',';
			$sql .= ' ' . self::kColIsAskLater . '=' . $item->_is_asklater;
			$prev_column_added = TRUE;
		}
			
        if ($item_flags & ShopItem::SHOPITEM_QUANTITY)
        {
            if ($prev_column_added)
                 $sql .= ', ';
            $sql .= ' ' . self::kColQuantity . '=' . $item->_quantity;
            $prev_column_added = TRUE;
        }

        if ($item_flags & ShopItem::SHOPITEM_UNITID)
        {
            if ($prev_column_added)
                $sql .= ', ';
            $sql .= ' ' . self::kColUnitID . '=' . $item->_unit_id;
            $prev_column_added = TRUE;
        }
		
		if ($item_flags & ShopItem::SHOPITEM_ISPURCHASED)
		{
			if ($prev_column_added)
				$sql .= ', ';
			$sql .= ' ' . self::kColIsPurchased . '=' . $item->_is_purchased;
			$prev_column_added = TRUE;
		}

        $sql .= '  WHERE ' . self::kColInstanceID . '=' . $item->_instance_id;
		
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);
    }

    function delete_item($instance_id)
    {
        $sql = sprintf("DELETE FROM `%s` 
        		WHERE %s = %d AND %s = %d ",
                parent::GetTableName(),
                self::kColUserID,
                parent::GetUserID(),
                self::kColInstanceID,
                $instance_id);

        NI::TRACE('ShopItems::delete_item SQL: ' . $sql, __FILE__, __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);
    }


	function suggest_item($string, $max_suggestions)
	{
		$sql = sprintf("SELECT `%s` FROM `shopitemscatalog` 
					WHERE `%s` LIKE '%%s%%' LIMIT %d", 
					self::kColItemName, 
					self::kColItemName, 
					$this->get_db_con()->escape_string($string), 
					max($max_suggestions, 10));

		NI::TRACE('SQL: $sql', __FILE__, __LINE__);
		$result = $this->get_db_con()->query($sql);
		
        if ($result == FALSE)
            throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);
		
		$suggestions = array();
        while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
        {
			$suggestions[] = $row[self::kColItemName];
        }
		if ($result)
			$result->free();

		return $suggestions;
	}
	// Creates an entry for the $item->_item_name in the `shopitemscatalog` 
	// table if it does not exist, return the id if the entry is present
	function create_catalog_entry($item)
	{
		// We first check if the new name exists in the `shopitemscatalog` table
		// if it does we simply edit the `itemID_FK` field in the `shopitems` 
		// table. If it doesn't we have to insert a new record for the item in 
		// the catalogs table first and then update the appropriate record in the
		// `shopitems` table.
		$new_itemid = 0;
		$sql = sprintf("SELECT `itemID` FROM `shopitemscatalog` 
				WHERE `itemName`='%s' AND `userID_FK`=%d LIMIT 1", 
				$this->get_db_con()->escape_string($item->_item_name), 
				parent::GetUserID());
				
		$result = $this->get_db_con()->query($sql);
		if ($result == FALSE)
            throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);
		
		$row = mysqli_fetch_array($result);
		if ($row)
		{
			$new_itemid = $row['itemID'];
			$result->free();
		}
		else
		{
			// No item was found create a new record for this name
			$sql = sprintf("INSERT INTO `shopitemscatalog` 
							(`itemName`, `itemPrice`, `userID_FK`, `categoryID_FK`) 
							VALUES ('%s', %.2f, %d, %d)",
						$this->get_db_con()->escape_string($item->_item_name), 
						$item->_unit_cost,
						parent::GetUserID(),
						$item->_category_id);
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE)
				throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);
			
			
			$new_itemid = $this->get_db_con()->insert_id;
		} 

		return $new_itemid;
	}
}
?>