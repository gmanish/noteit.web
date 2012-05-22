<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../lib/noteitcommon.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tablebase.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'shopitem.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'samplevariance.php';

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

class ShopItemsPrice
{
	const kTableName 		= 'shopitems_price';
	const kColClassId		= 'classID_FK';
	const kColCurrencyCode	= 'currencyCode_FK';
	const kColUnitID		= 'unitID_FK';
	const kColDateAdded		= 'date_added';
	const kColItemPrice		= 'itemPrice';
	
	const kStats_Mean				= 'mean';
	const kStats_SampleDeviation	= 'sampleDeviation';
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
	const kColVoteCount		= 'voteCount';
	
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
    		
    		$parentList = ShoppingList::create_from_db(
    				$this->get_db_con(), 
    				$list_id, 
    				parent::GetUserID());
    		
    		if (!Permissions::can_write(parent::GetUserID(), $parentList))
				throw new Exception("You don't have permissions to perform this operation.");
    		
    		$class_ID = 0;
    		$sql = sprintf("SELECT `itemID` 
    						FROM `shopitemscatalog` 
    						WHERE `%s`='%s'", 
							self::kColItemName,
							$this->get_db_con()->escape_string($item_name));
			if (!empty($barcode)) {
				$sql .= " AND " . self::kColBarcode . '=' .  $this->get_db_con()->escape_string($barcode);
				if (!empty($barcode_format)) {
					$sql .= " AND " . self::kColBarcodeFormat . '=' . $barcode_format;
				}
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
				
				$instance_id = $this->get_db_con()->insert_id; 

				$item_stats = $this->log_price(
						$class_ID,
						$instance_id,
						$this->user_pref->currencyId,
						$unit_id,
						$unit_cost);
				
				if ($isTransactional == TRUE) {
					$this->get_db_con()->commit();
					$this->get_db_con()->autocommit(TRUE);
				}
				
				return new ShopItemAndStats(
						$this->get_item($instance_id), 
						$item_stats);
								
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
   		$parentList = ShoppingList::create_from_db(
							$this->get_db_con(), 
							$list_id, 
							parent::GetUserID());
    		
  		if (!Permissions::can_read(parent::GetUserID(), $parentList))
			throw new Exception("You don't have permissions to perform this operation.");
  		
    	$sql = "";
    	if ($show_purchased_items <= 0) {
	        $sql = sprintf(
				 		"SELECT 
							si.*, 
							sic.itemName, 
							sic.itemBarcode, 
							sic.itemBarcodeFormat,
							(SELECT 
								SUM(vote) 
							 FROM shopitems_metadata 
							 WHERE 
							 	`itemId_FK`=si.`itemID_FK`) as voteCount
					 	FROM `%s` AS si
					 	INNER JOIN `shopitemscatalog` AS sic
					 	INNER JOIN `shopitemcategories` AS sicg
						ON 
							si.itemID_FK=sic.itemID AND 
							si.`categoryID_FK`=sicg.`categoryID`
						WHERE 
							si.listID_FK=%d AND 
							si.isPurchased <= 0 
						ORDER BY 
							sicg.`categoryRank` ASC",
		                self::kTableName,
		                $list_id);
		} else {
	        $sql = sprintf(
					"SELECT 
						si.*, 
						sic.itemName, 
						sic.itemBarcode, 
						sic.itemBarcodeFormat,
						(SELECT 
							SUM(vote) 
						FROM shopitems_metadata 
						WHERE 
						 	`itemId_FK`=si.`itemID_FK`) as voteCount
					FROM `%s` AS si
					INNER JOIN `shopitemscatalog` AS sic
					INNER JOIN `shopitemcategories` AS sicg
					ON 
						si.itemID_FK=sic.itemID AND 
						si.`categoryID_FK`=sicg.`categoryID`
					WHERE 
						si.listID_FK=%d
					ORDER BY 
						sicg.`categoryRank` ASC",
	                self::kTableName,
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
				is_null($row[self::kColBarcodeFormat]) ? BarcodeFormat::BARCODE_FORMAT_UNKNOWN : $row[self::kColBarcodeFormat],
				is_null($row['voteCount']) ? 0 : $row['voteCount']);

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
    	$sql = sprintf(
        		"SELECT 
					si.*, 
					sic.itemName , 
					sic.itemBarcode, 
					sic.itemBarcodeFormat,
					(SELECT 
						SUM(vote) 
					FROM shopitems_metadata 
					WHERE 
					 	`itemId_FK`=si.`itemID_FK`) as voteCount
				FROM `%s` AS si 
				INNER JOIN `shopitemscatalog` AS sic  
				ON 
					si.itemID_FK = sic.itemID  
				WHERE 
					si.instanceID = %d  LIMIT 1",
				self::kTableName,
				$instance_id);

        NI::TRACE('ShopItems::get_item SQL: ' . $sql, __FILE__, __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);

        if ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
        {
            $result->free();
        	
        	$parentList = ShoppingList::create_from_db(
        			$this->get_db_con(),
        			intval($row[self::kColListID]),
        			parent::GetUserID());
        	        	
        	if (Permissions::can_read(parent::GetUserID(), $parentList)) {

	        	NI::TRACE('ShopItems::get_item() returned row: ' . print_r($row, TRUE), __FILE__, __LINE__);
	            $thisItem = new ShopItem(
	                intval($row[self::kColInstanceID]),
	                intval($row[self::kColItemID]),
	                intval($row[self::kColUserID]),
	                intval($row[self::kColListID]),
	                intval($row[self::kColCategoryID]),
	                $row[self::kColItemName],
	                floatval($row[self::kColUnitCost]),  // unit cost
	                floatval($row[self::kColQuantity]),
	                intval($row[self::kColUnitID]),
					intval($row[self::kColIsPurchased]),
					intval($row[self::kColIsAskLater]),
					is_null($row[self::kColBarcode]) ? "" : $row[self::kColBarcode],
					is_null($row[self::kColBarcodeFormat]) ? BarcodeFormat::BARCODE_FORMAT_UNKNOWN : intval($row[self::kColBarcodeFormat]),
					is_null($row['voteCount']) ? 0 : intval($row['voteCount']));
	            	
	            	return $thisItem;
        	} else {
        		throw new Exception("You don't have permissions to perform this operation.");
        	}
        } else {
        	throw new Exception("Item not found.");
        }
    }

	function get_item_price($classID) {
		
		$sql = sprintf("SELECT * FROM shopitems si
						INNER JOIN users AS u
						ON si.`userID_FK`=u.`userID`
						WHERE si.itemID_FK=%d AND u.`currencyId`=%d
						ORDER BY 
							(CASE WHEN userID_FK=%d THEN 1
							ELSE 2 END), dateAdded 
						LIMIT 1",
						$classID,
						$this->get_user_currency(),
						$this->GetUserID());
		$result = $this->get_db_con()->query($sql);
		if ($result != FALSE && mysqli_num_rows($result) == 1) {
			$row = $result->fetch_array();
			if ($row) {
				return floatval($row['unitCost']);
			}	
		} else {
			return 0.0;
		}
	}
	
    function edit_item($instance_id, ShopItem $item, $item_flags /* one of SHOPITEM_* flags */)
    {
    	$thisItem = $this->get_item($instance_id);
       	$parentList = ShoppingList::create_from_db(
      			$this->get_db_con(),
      			intval($thisItem->_list_id),
       			parent::GetUserID());
        	
       	if (Permissions::can_write(parent::GetUserID(), $parentList)) {
    	
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
	        
	        $item_stats = NULL;
	        
	        if (($item_flags & ShopItem::SHOPITEM_UNITCOST) && 
	        		$item->_unit_cost > 0) {
	        	
	        	$item_stats = $this->log_price(
	        			0, 								// We don't have class id here
	        			$instance_id,
	        			$this->user_pref->currencyId, 	// For now we use user prefs for currency id
	        			($item_flags & ShopItem::SHOPITEM_UNITID) > 0 ? $item->_unit_id : $thisItem->_unit_id, 
	        			($item_flags & ShopItem::SHOPITEM_UNITCOST) > 0 ? $item->_unit_cost : $thisItem->_unit_cost);
	        }
	        
	        // Fetch Item details from db and send back
			return new ShopItemAndStats(
					$this->get_item($instance_id), 
					$item_stats);
		} else {
       		throw new Exception("You don't have permissions to perform this operation.");
       	}
    }

    function delete_item($instance_id)
    {
    	$thisItem = $this->get_item($instance_id);
       	$parentList = ShoppingList::create_from_db(
      			$this->get_db_con(),
      			intval($thisItem->_list_id),
       			parent::GetUserID());
       	
       	if (Permissions::can_delete(parent::GetUserID(), $parentList)) {
       		
	    	$sql = sprintf("DELETE FROM `%s` 
	        		WHERE %s = %d ",
	                self::kTableName,
	                self::kColInstanceID,
	                $instance_id);
	
	        NI::TRACE('ShopItems::delete_item SQL: ' . $sql, __FILE__, __LINE__);
	        $result = $this->get_db_con()->query($sql);
	        if ($result == FALSE) {
	            throw new Exception('SQL exec failed ('. __FILE__ . __LINE__ . '): ' . $this->get_db_con()->error);
	        }
       	} else {
       		throw new Exception("You don't have permissions to perform this operation.");
       	}
    }

	function copy_item($instance_id, $target_list_id)
	{
		$targetList = ShoppingList::create_from_db($this->get_db_con(), $target_list_id, parent::GetUserID());
		
		if (Permissions::can_write(parent::GetUserID(), $targetList)) {
			
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
			
	        if ($result == FALSE) {
	            throw new Exception('The copy operation failed (' . $this->get_db_con()->errno . ')');
	        }
		} else {
			throw new Exception("You don't have permissions to perform this operation.");
		}	
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
		
		$sql = sprintf(
				"SELECT 
					SI.*, 
					SIC.itemName, 
					SIC.itemBarcode, 
					SIC.itemBarcodeFormat,
					(SELECT 
						SUM(vote) 
					FROM shopitems_metadata 
					WHERE 
					 	itemId_FK=SIC.itemID) as voteCount
				FROM shopitemscatalog SIC
				LEFT JOIN shopitems SI
				ON 
					SIC.itemID=SI.itemID_FK
				WHERE 
					SIC.itemBarcode = '%s' AND 
					SIC.itemBarcodeFormat = %d
				ORDER BY 
					SI.datePurchased DESC
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
                !is_null($row[self::kColInstanceID]) 	? $row[self::kColInstanceID] 	: 0,
                !is_null($row[self::kColItemID])		? $row[self::kColItemID] 		: 0,
                !is_null($row[self::kColUserID]) 		? $row[self::kColUserID]  		: 0,
                !is_null($row[self::kColListID]) 		? $row[self::kColListID]  		: 0, 
                !is_null($row[self::kColCategoryID]) 	? $row[self::kColCategoryID]  	: 1, // Uncategorized
                $row[self::kColItemName],
                !is_null($row[self::kColUnitCost]) 		? $row[self::kColUnitCost]  	: 0,
                !is_null($row[self::kColQuantity]) 		? $row[self::kColQuantity]  	: 1, // Default
                !is_null($row[self::kColUnitID]) 		? $row[self::kColUnitID]  		: 1, // unit
				0, // isPurchased is not yet, so 0
				!is_null($row[self::kColIsAskLater]) 	? $row[self::kColIsAskLater]  	: 0,
				!is_null($row[self::kColBarcode])    	? $row[self::kColBarcode] 		: "",
				!is_null($row[self::kColBarcodeFormat]) ? $row[self::kColBarcodeFormat] : BarcodeFormat::BARCODE_FORMAT_UNKNOWN,
				!is_null($row['voteCount']) 			? $row['voteCount'] : 0);
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
			    				$this->user_pref->get_currencycode() == $inventory->currency) {
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
								$barcodeFormat,
								0);
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
				WHERE `itemName`='%s'", 
				$this->get_db_con()->escape_string($item->_item_name));

		if (!empty($item->_barcode)) {
			$sql .= " AND " . self::kColBarcode . "=" . $this->get_db_con()->escape_string($barcode);
			if (!empty($item->_barcode_format)) {
				$sql .= " AND " . self::kColBarcodeFormat . "=" . $item->_barcode_format;
			}
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
		
		$thisList = ShoppingList::create_from_db($this->get_db_con(), $list_Id, parent::GetUserID());
		
		if (Permissions::can_write(parent::GetUserID(), $thisList)) {

			$sql = sprintf("UPDATE `%s` 
							SET `%s`=%d,
							`%s`='%s'
							WHERE `%s`=%d and `%s`=%d", 
							self::kTableName, 
							self::kColIsPurchased,
							$done,
							self::kColDatePurchased,
							$date_purchased->format('Y-m-d'),
							self::kColListID,
							$list_Id,
							self::kColIsPurchased,
							$done > 0 ? 0 : 1);
			
			$result = $this->get_db_con()->query($sql);
			if (!$result)
				throw new Exception("Error Updating Items.");
		} else {
			throw new Exception("You don't have permissions to perform this operation.");
		}
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
			WHERE `itemID_FK`=%d AND 
				`isPurchased`=1 AND 
				`unitCost` > 0 AND 
				`userID_FK`=%d
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
	
	/*
	 * Either of $classID or instance_id must be supplied to this function. 
	 */
	private function log_price(
		$classID, 
		$instance_id,
		$currencyId, 
		$unitID, 
		$itemPrice) {
		
		$item_stats = NULL;
		
		if ($currencyId > 0 && 
			$unitID > 0 && 
			$itemPrice > 0) {
			
			if ($classID <= 0 && $instance_id <= 0) {
				throw new Exception("Error processing request.");
			}
			
			if ($classID <= 0) {
				$sql = sprintf("SELECT `itemID_FK` 
								FROM `shopitems`
								WHERE `instanceID`=%d",
								$instance_id);
				$result = $this->get_db_con()->query($sql);
				if ($result == FALSE) {
					throw new Exception("Could not find Item (" . $this->get_db_con()->errno . ")");
				}
				
				if ($row = mysqli_fetch_assoc($result)) {
					$classID = $row['itemID_FK'];
				} else {
					throw new Exception("Could not find Item");
				}
			}
			
			// itemPrice maintains a running sum of all itemPrices seen on a given day 
			// while itemCount maintains the running total of the number of items seen
			// for a given PK combination
			$sql = sprintf("
						INSERT INTO 
							shopitems_price (`classID_FK`, `currencyId_FK`, `unitID_FK`, `date_added`, `itemPrice`, `itemCount`) 
						VALUES 
							(%d, %d, %d, CURDATE(), %f, 1)
						ON DUPLICATE KEY UPDATE
							`itemPrice`=`itemPrice` + %f, `itemCount`=`itemCount`+1",
						$classID, 
						$currencyId,
						$unitID,
						$itemPrice,
						$itemPrice);
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {	
				throw new Exception("Error logging item price. (" . $this->get_db_con()->errno . ")");
			}

			$sample_variance = $this->calculate_stats(
					$classID, 
					$currencyId, 
					$unitID);
			
			if ($sample_variance != NULL) {
				$item_stats = new ShopItemStats(
						$sample_variance->mean(), 
						$sample_variance->sample_deviation());
			}
		}
		
		return $item_stats;
	}
	
	public function calculate_stats($classId, $currencyId, $unitId) {
		
		// Calculate the mean and sample variance for the samples recorded in the last 6 months
		$date_today = new DateTime();
		$date_to = $date_today->format("Y-m-d");
		
		$date_today->sub(new DateInterval('P6M')); // Six months before today
		$date_from = $date_today->format("Y-m-d");
		
		// itemPrice maintains a running sum of all itemPrices seen on a given day 
		// while itemCount maintains the running total of the number of items seen
		// for a given PK combination. We take the average of any given day.
		$sql = sprintf("SELECT 
							`itemPrice`/`itemCount` AS `itemPrice`
						FROM 
							`shopitems_price`
						WHERE 
							`classID_FK`=%d AND 
							`currencyId_FK`=%d AND 
							`unitID_FK`=%d AND 
							`date_added` BETWEEN CAST('%s' AS DATE) AND CAST('%s' AS DATE)",
						$classId,
						$currencyId,
						$unitId,
						$date_from,
						$date_to);
		
		$result = $this->get_db_con()->query($sql);
		if ($result) {
			$variance = new SampleVariance();
			while ($row = mysqli_fetch_array($result)) {
				$variance->push(floatval($row['itemPrice']));
			}
			return $variance;			
		} else {
			throw new Exception("No data for item."); // No sample
		}
	}
}
?>