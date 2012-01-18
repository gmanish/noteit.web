<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../lib/noteitcommon.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tablebase.php';

class SuggestedItem
{
	public $itemName = "";
	public $itemId = 0;
	
	function __construct($itemId, $itemName) {
		$this->itemId = $itemId;
		$this->itemName = $itemName;
	}	
}

class BarcodeFormat {
	const BARCODE_FORMAT_UNKNOWN 	= 1; 
	const BARCODE_FORMAT_UPC_A		= 2;
	const BARCODE_FORMAT_UPC_E		= 3;
	const BARCODE_FORMAT_EAN_8		= 4;
	const BARCODE_FORMAT_EAN_13		= 5;
	const BARCODE_FORMAT_RSS_14		= 6;
}

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
	public $_barcode = "";
	public $_barcode_format = BarcodeFormat::BARCODE_FORMAT_UNKNOWN;
	
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
		$is_asklater = FALSE,
		$barcode = "",
		$barcode_format = BarcodeFormat::BARCODE_FORMAT_UNKNOWN)
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
		$this->_barcode = $barcode;
		$this->_barcode_format = $barcode_format;
    }
}

class ShopItems extends TableBase
{
	const USE_STORED_PROC  	= FALSE;
	
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
	const kColBarcode		= 'itemBarcode';
	const kColBarcodeFormat = 'itemBarcodeFormat';
	
	protected $user_pref;

    function __construct($db_base, $user_ID, $user_pref)
    {
        parent::__construct($db_base, $user_ID);
		$this->user_pref = $user_pref;
    }	

    function add_item(
    	$list_id, 
    	$category_id, 
    	$item_name, 
    	$unit_cost, 
    	$item_quantity, 
    	$unit_id, 
    	$is_asklater, 
    	$barcode,
		$barcode_format)
    {
    	if (!self::USE_STORED_PROC) {
    		
    		$class_ID = 0;
    		$sql = sprintf("SELECT `itemID` 
    						FROM `shopitemscatalog` 
    						WHERE `%s`='%s'", 
							self::kColItemName,
							$this->get_db_con()->escape_string($item_name));
			if ($barcode) {
				$sql .= " AND " . self::kColBarcode . $this->get_db_con()->escape_string($barcode);
			}
			$sql .= " LIMIT 1";

			$isTransactional = FALSE;
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE || mysqli_num_rows($result) <= 0) {
				
				// A record of this item does not exist in the `shopitemcatelog` table; create one
				// At this point start a transaction which is can  roll back on failure
				try {
					$isTransactional = $this->get_db_con()->autocommit(FALSE);
					if (!$isTransactional) {
						throw new Exception("Could Not Create Transaction");	
					}
					
					$sql = sprintf("INSERT INTO `shopitemscatalog` (`itemName`, `itemBarcode`, `itemBarcodeFormat`) 
								VALUES ('%s', '%s', '%d')",
								$this->get_db_con()->escape_string($item_name),
								$this->get_db_con()->escape_string($barcode),
								$barcode_format);
						
					$result = $this->get_db_con()->query($sql);
					if ($result == FALSE) {
						throw new Exception(
							"Item could not be added to Catalog (". 
							$this->get_db_con()->errno .")");
					}
					
					$class_ID =	$this->get_db_con()->insert_id;
					
				} catch (Exception $e) {
					if ($isTransactional) {
						$this->get_db_con()->rollback();
						$this->get_db_con()->autocommit(TRUE);
					}
					throw $e;
				}
			} else {
		       	$row = $result->fetch_row();
				$class_ID = $row[0];
				$result->free();
			}
			
			
			try {
				$sql = sprintf("INSERT INTO `%s` (`%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`)
						VALUES(%d, %d, curDate(), %d, %f, %f, %d, %d, %d)",
						self::kTableName,
						self::kColUserID,
						self::kColItemID,
						self::kColDateAdded,
						self::kColListID,
						self::kColUnitCost,
						self::kColQuantity,
						self::kColUnitID,
						self::kColCategoryID,
						self::kColIsAskLater,
						parent::GetUserID(),
						$class_ID,
						$list_id,
						$unit_cost,
						$item_quantity,
						$unit_id,
						$category_id,
						$is_asklater);
				
				$result = $this->get_db_con()->query($sql);
				if ($result == FALSE) {
					throw new Exception("Error Adding Item. (" . $this->get_db_con()->errno . ")");
				}
				
				$instance_id =  $this->get_db_con()->insert_id; 
				if ($isTransactional == TRUE) {
					$this->get_db_con()->commit();
					$this->get_db_con()->autocommit(TRUE);
				}
				return $instance_id;
				
			} catch (Exception $e) {
				if ($isTransactional) {
					$this->get_db_con()->rollback();
					$this->get_db_con()->autocommit(TRUE);
				}
				throw $e;
			}			
				
		} else { //USE_STORED_PROC
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
				throw new Exception("Item could not be added.");
	        $row = $result->fetch_row();
			$result->free();
	        return $row[0];
		}
    }

    function list_range(
        $show_purchased_items,
        $move_purchased_items_to_bottom,
        $list_id,
        $start_at,
        $num_rows_fetch,
        & $functor_obj,
        $function_name="iterate_row")
    {
    	$sql = "";
    	if ($show_purchased_items <= 0) {
	        $sql = sprintf(
					"SELECT si.*, sic.itemName, sic.itemBarcode, sic.itemBarcodeFormat FROM `%s` AS si " .
					"INNER JOIN `shopitemscatalog` AS sic " .
					"INNER JOIN `shopitemcategories` AS sicg " .
					"ON si.itemID_FK=sic.itemID AND si.`categoryID_FK`=sicg.`categoryID` " .
					"WHERE si.userID_FK=%d AND si.listID_FK=%d AND si.isPurchased <= 0 " . 
					"ORDER BY sicg.`categoryRank` ASC ",
	                self::kTableName,
	                parent::GetUserID(),
	                $list_id);
		} else {
	        $sql = sprintf(
					"SELECT si.*, sic.itemName, sic.itemBarcode, sic.itemBarcodeFormat FROM `%s` AS si
					INNER JOIN `shopitemscatalog` AS sic
					INNER JOIN `shopitemcategories` AS sicg
					ON si.itemID_FK=sic.itemID AND si.`categoryID_FK`=sicg.`categoryID`
					WHERE si.userID_FK=%d AND si.listID_FK=%d
					ORDER BY sicg.`categoryRank` ASC",
	                self::kTableName,
	                parent::GetUserID(),
	                $list_id);
	        if ($move_purchased_items_to_bottom)
				$sql .= ", isPurchased asc ";
		}

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
				$row[self::kColIsAskLater],
				is_null($row[self::kColBarcode]) ? "" : $row[self::kColBarcode],
				is_null($row[self::kColBarcodeFormat]) ? BarcodeFormat::BARCODE_FORMAT_UNKNOWN : $row[self::kColBarcodeFormat]);

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
        $sql = sprintf("SELECT si.*, sic.itemName , sic.itemBarcode, sic.itemBarcodeFormat 
        		FROM `%s` AS si 
        		inner join `shopitemscatalog` AS sic  
        		ON si.itemID_FK = sic.itemID  
        		WHERE si.userID_FK = %d and si.instanceID = %d  LIMIT 1",
                self::kTableName,
                parent::GetUserID(),
                $instance_id);

        NI::TRACE('ShopItems::get_item SQL: ' . $sql, __FILE__, __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);

        if ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
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
				$row[self::kColIsAskLater],
				is_null($row[self::kColBarcode]) ? "" : $row[self::kColBarcode],
				is_null($row[self::kColBarcodeFormat]) ? BarcodeFormat::BARCODE_FORMAT_UNKNOWN : $row[self::kColBarcodeFormat]);

            if ($result)
                $result->free();

            return $thisItem;
        }
    }

    function edit_item($instance_id, $item, $item_flags /* one of SHOPITEM_* flags */)
    {
		$sql = sprintf('UPDATE %s SET', self::kTableName);
        $prev_column_added = FALSE;
		
        if ($item_flags & ShopItem::SHOPITEM_LISTID)
        {
            $sql .= ' ' . self::kColListID . '=' . $item->_list_id;
            $prev_column_added = TRUE;
        }

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
			
			if ($item->_is_purchased > 0) {
				$sql .= ', ' . self::kColDatePurchased . "='" . $item->_date_purchased->format('Y-m-d') . "'";
			}
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
                self::kTableName,
                self::kColUserID,
                parent::GetUserID(),
                self::kColInstanceID,
                $instance_id);

        NI::TRACE('ShopItems::delete_item SQL: ' . $sql, __FILE__, __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);
    }

	function copy_item($instance_id, $target_list_id)
	{
		$sql = sprintf("insert into shopitems (
						`userID_FK`,
						`itemID_FK`,
						`dateAdded`,
						`listID_FK`,
						`unitCost`,
						`quantity`,
						`unitID_FK`,
						`categoryID_FK`,
						`isPurchased`,
						`isAskLater`)
							select `userID_FK`,
									`itemID_FK`,
									curDate(),
									%d,
									`unitCost`,
									`quantity`,
									`unitID_FK`,
									`categoryID_FK`,
									`isPurchased`,
									`isAskLater` 
							from shopitems as si
							where instanceID=%d and `userID_FK`=%d",
						$target_list_id,
						$instance_id,
						parent::GetUserID());
		$result = $this->get_db_con()->query($sql);
		
        if ($result == FALSE)
            throw new Exception('The copy operation failed (' . $this->get_db_con()->errno . ')');
		
		return $this->get_db_con()->insert_id;
	}
	
	function suggest_item($string, $max_suggestions)
	{
		$sql = sprintf("SELECT `itemID`, `%s` 
					FROM `shopitemscatalog` 
					WHERE `%s` LIKE '%%%s%%' LIMIT %d",
					self::kColItemName, 
					self::kColItemName, 
					$this->get_db_con()->escape_string($string), 
					min($max_suggestions, 10));

		NI::TRACE("SQL: " . $sql, __FILE__, __LINE__);
		$result = $this->get_db_con()->query($sql);
		
        if ($result == FALSE)
            throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);
		
		$suggestions = array();
        while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
        {
        	$suggestions[] = new SuggestedItem($row['itemID'], $row[self::kColItemName]);
        }
		if ($result)
			$result->free();

		return $suggestions;
	}
	
	function searchitem_barcode($barcode, $barcodeFormat) {
		
		$sql = sprintf("SELECT SI.*, SIC.itemName, SIC.itemBarcode, SIC.itemBarcodeFormat  
						FROM shopitemscatalog SIC
						LEFT JOIN shopitems SI
						ON SIC.itemID=SI.itemID_FK
						WHERE SIC.itemBarcode = '%s' AND SIC.itemBarcodeFormat = %d
						ORDER BY SI.datePurchased DESC
						LIMIT 1",
						$barcode,
						$barcodeFormat);
						
		$result = $this->get_db_con()->query($sql);
		if ($result == FALSE) {
			throw new Exception('Error Executing Barcode Search in Database (' . $this->get_db_con()->errno . ')'); 
		}
		
		$row = $result->fetch_array();
		if ($row) {
            return new ShopItem(
                is_null($row[self::kColInstanceID]) ? 0  : $row[self::kColInstanceID],
                is_null($row[self::kColItemID])		? 0  : $row[self::kColItemID],
                is_null($row[self::kColUserID]) 	? 0  : $row[self::kColUserID],
                is_null($row[self::kColListID]) 	? 0  : $row[self::kColListID],
                is_null($row[self::kColCategoryID]) ? 0  : $row[self::kColCategoryID],
                $row[self::kColItemName],
                is_null($row[self::kColUnitCost]) 	? 0  : $row[self::kColUnitCost],
                is_null($row[self::kColQuantity]) 	? 0  : $row[self::kColQuantity],
                is_null($row[self::kColUnitID]) 	? 0  : $row[self::kColUnitID],
				0, // isPurchased is not yet, so 0
				is_null($row[self::kColIsAskLater]) ? 0  : $row[self::kColIsAskLater],
				is_null($row[self::kColBarcode])    ? "" : $row[self::kColBarcode],
				is_null($row[self::kColBarcodeFormat]) ? BarcodeFormat::BARCODE_FORMAT_UNKNOWN : $row[self::kColBarcodeFormat]);
		} else {
			// Shall we try google
			$google_api_key = "AIzaSyA9IqL-QR5YezowBLgMIwwDvd_lDtWcSlo";
			$searchURL = "https://www.googleapis.com/shopping/search/v1/public/products";
    		
    		$args = "key=" . urlencode($google_api_key);
    		$args .= "&country=" . urlencode("US");
    		$args .= "&q=" . urlencode($barcode);
			$args .= "&alt=json";
			
			$result = file_get_contents($searchURL . "?" . $args);
			if (!$result) {
				throw new Exception('Error Executing Barcode Search in Provider.');
			} else {
				$json = json_decode($result);
		    	for ($index = $json->startIndex; $index < $json->totalItems; $index++) {
		    			
		    		$itemJSON = $json->items[$index];
					if (!is_null($itemJSON)) {
						
			    		$product 	 = $itemJSON->product;
			    		$inventories = $product->inventories;
			    		
			    		if (!is_null($product->inventories)) {
			    			$item_price = 0; 
			    			$inventory = $inventories[0];
			    			if (!is_null($inventory->currency) && 
			    				$this->user_pref->currencyCode == $inventory->currency) {
								$item_price = !is_null($inventory->price) ? $inventory->price : 0;
							}
							
				    		return new ShopItem(                
				    			0,
				                0,
				                0,
				                0,
				                1, // Uncategorized
				                !is_null($product->title) ? $product->title : "", 
				                $item_price,
				                1, // quantity
				                1, // unit
								0, // isPurchased is not yet, so 0
								0, // isAskLater
								$barcode,
								$barcodeFormat);
				    		// String currency = inventories.getJSONObject(0).getString("currency");
				    		// if (currency.equals(mUserPrefs.mCurrencyCode)) {
			    	    		// item.mUnitPrice = (float) inventories.getJSONObject(0).getDouble("price");
				    		// }
			    		}
					}
		    	} 
				
				return NULL;
			}
		}
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
		$sql = sprintf("SELECT `itemID` 
				FROM `shopitemscatalog` 
				WHERE `itemName`='%s' LIMIT 1", 
				$this->get_db_con()->escape_string($item->_item_name));

		if ($item->_barcode != "") {
			$sql .= " AND " . self::kColBarcode . $this->get_db_con()->escape_string($barcode);
		}
		$sql .= " LIMIT 1";
				
		$result = $this->get_db_con()->query($sql);
		if ($result == FALSE) {
            throw new Exception('Could not find matching entry in Catalog (' . $this->get_db_con()->errno . ')');
		}
		
		$row = mysqli_fetch_array($result);
		if ($row)
		{
			$new_itemid = $row['itemID'];
			$result->free();
		}
		else
		{
			// No item was found create a new record for this name
			$sql = sprintf("INSERT INTO `shopitemscatalog` (`itemName`, `itemBarcode`, `itemBarcodeFormat`) 
							VALUES ('%s', '%s', %d)",
							$this->get_db_con()->escape_string($item->_item_name),
							$this->get_db_con()->escape_string($item->_barcode),
							$item->_barcode_format);
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {
				throw new Exception('Could not create Catalog entry (' . $this->get_db_con()->errno . ')');
			}
			
			$new_itemid = $this->get_db_con()->insert_id;
		} 

		return $new_itemid;
	}

	function mark_all_done($list_Id, $done, $date_purchased) {
		
		$sql = sprintf("UPDATE `%s` 
						SET `%s`=%d,
						`%s`='%s'
						WHERE `%s`=%d and `%s`=%d and `%s`=%d", 
						self::kTableName, 
						self::kColIsPurchased,
						$done,
						self::kColDatePurchased,
						$date_purchased->format('Y-m-d'),
						self::kColUserID,
						parent::GetUserID(),
						self::kColListID,
						$list_Id,
						self::kColIsPurchased,
						$done > 0 ? 0 : 1);
		$result = $this->get_db_con()->query($sql);
		if (!$result)
			throw new Exception("Error Updating Items.");
	}
	
	// Returns the total items cost and cost of pending items
	function get_pending_cost($list_id) {
		$sql = sprintf("select sum(%s * %s) as `totalCost`
						from %s 
						where %s=%d and %s=0",
						self::kColUnitCost,
						self::kColQuantity,
						self::kTableName,
						self::kColListID,
						$list_id,
						self::kColIsPurchased);
		$result = $this->get_db_con()->query($sql);		
		if (!$result) {			
			throw new Exception("Error Calculating Total." . $this->get_db_con()->errno . ")");
		}
		
		$pending_cost = 0.0;
        if ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
        {
			$pending_cost = $row['totalCost'];
			$result->free();
        }
		return $pending_cost;
	}
	
	function list_instances(
		$class_id, 
		$num_instances,
		& $functor_obj,
        $function_name="iterate_row") {
		
		$num_instances = min($num_instances, 1); // Let's restrict till we know we need more
		$sql = sprintf(
			"SELECT `instanceID`, `categoryID_FK`, `unitCost`, `quantity`, `unitID_FK` 
			FROM `shopitems` 
			WHERE `itemID_FK`=%d AND `isPurchased`=1 AND `unitCost` > 0 AND `userID_FK`=%d  
			ORDER BY `datePurchased` DESC 
			LIMIT %d", 
			$class_id, 
			parent::GetUserID(),
			$num_instances);
		$result = $this->get_db_con()->query($sql);
		if (!$result) {
			throw new Exception("Could not fetch item history. (" . $this->get_db_con()->errno . ".");	
		}
		
        while ($row = mysqli_fetch_array($result)) {
        	
            $thisItem = new ShopItem(
                $row[self::kColInstanceID],
				$class_id,
                parent::GetUserID(),
                0,
                $row[self::kColCategoryID],
                "",
                $row[self::kColUnitCost],  // unit cost
                $row[self::kColQuantity],
                $row[self::kColUnitID],
				1,
				0);
				
            call_user_func(
                array($functor_obj, $function_name), // invoke the callback function
                $thisItem);
                
            $thisItem = NULL;
        }
        
        if ($result)
        	$result->free();
	}
}
?>