<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../lib/noteitcommon.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tablebase.php';

if (!class_exists('Reports')) {
	
class Reports extends TableBase {
	
	public static $Table_Items				= 'shopitems';
	public static $Table_Categories			= 'shopitemcategories';
	
	public static $kCol_ItemsItemID			= 'itemID';
	public static $kCol_ItemsName			= 'itemName';	
	public static $kCol_ItemsCategory		= 'categoryID_FK';
	public static $kCol_ItemsListId			= 'listID_FK';
	public static $kCol_ItemsPurchased		= 'isPurchased';
	public static $kCol_ItemsUnitCost		= 'unitCost';
	public static $kCol_ItemsQuantity		= 'quantity';
	public static $kCol_ItemsPrice			= 'price';
	public static $kCol_ItemsUserId			= 'userID_FK';
	
	public static $kCol_CategoriesName		= 'categoryName';
	public static $kCol_CategoriesId		= 'categoryID';
	
	
    function __construct($db_base, $user_ID)
    {
        parent::__construct($db_base, $user_ID);
    }

	public function per_item($isPurchased, $date_from, $date_to) {
		$sql = "";
		if (!empty($date_from) && !empty($date_to)) {
			$sql = sprintf("SELECT itemID, `itemName`, sum(`itemPrice` * `quantity`) as `price`
							FROM `shopitems` si
							INNER JOIN `shopitemscatalog` sic
							ON si.`itemID_FK` = sic.`itemID`
							WHERE `isPurchased`=%d AND si.userID_FK=%d AND `datePurchased` BETWEEN '%s' AND '%s'
							GROUP BY `itemID`", 
							$isPurchased,
							parent::GetUserID(),
							$date_from->format('Y-m-d'),
							$date_to->format('Y-m-d'));
		} else if (!$empty($date_from)) {
			$sql = sprintf("SELECT itemID, `itemName`, sum(`itemPrice` * `quantity`) as `price`
							FROM `shopitems` si
							INNER JOIN `shopitemscatalog` sic
							ON si.`itemID_FK` = sic.`itemID`
							WHERE `isPurchased`=%d AND si.userID_FK=1 AND `datePurchased` >= '%s'
							GROUP BY `itemID`", 
							$isPurchased,
							parent::GetUserID(),
							$date_from->format('Y-m-d'));
		} else if (!empty($date_to)) {
			$sql = sprintf("SELECT itemID, `itemName`, sum(`itemPrice` * `quantity`) as `price`
							FROM `shopitems` si
							INNER JOIN `shopitemscatalog` sic
							ON si.`itemID_FK` = sic.`itemID`
							WHERE `isPurchased`=%d AND si.userID_FK=1 AND `datePurchased` <= '%s'
							GROUP BY `itemID`", 
							$isPurchased,
							parent::GetUserID(),
							$date_to->format('Y-m-d'));
		} else {
			throw new Exception("Both To and From Dates cannot be null");
		}
		
		$result = $this->get_db_con()->query($sql);
		$result_set = array();
		while ($result && $row = $result->fetch_array()) {
			$result_set[] = array(
				self::$kCol_ItemsItemID => $row[self::$kCol_ItemsItemID],
				self::$kCol_ItemsName   => $row[self::$kCol_ItemsName],
				self::$kCol_ItemsPrice 	=> $row[self::$kCol_ItemsPrice]);
		}
		return $result_set;
	}
	
	public function per_category($isPurchased, $date_from, $date_to) {
		$sql = "";
		if (!empty($date_from) && !empty($date_to)) {
			$sql = sprintf("SELECT `categoryID_FK`, `categoryName`, sum(unitCost *  quantity) as `price`
							FROM shopitems si
							INNER JOIN shopitemcategories sic
							ON si.categoryID_FK = sic.categoryID
							WHERE isPurchased=%d  
							GROUP BY `categoryID`", 
							$isPurchased,
							parent::GetUserID(),
							$date_from->format('Y-m-d'),
							$date_to->format('Y-m-d'));
		}
		else if (!empty($date_from)) {
			$sql = sprintf("SELECT `categoryID_FK`, `categoryName`, sum(unitCost *  quantity) as `price`
							FROM shopitems si
							INNER JOIN shopitemcategories sic
							ON si.categoryID_FK = sic.categoryID
							WHERE isPurchased=%d AND si.userID_FK=%d AND si.dateAdded > '%s' 
							GROUP BY `categoryID`", 
							$isPurchased,
							parent::GetUserID(),
							$date_from->format('Y-m-d'));
		}
		else if (!empty($date_to)) {
			$sql = sprintf("SELECT `categoryID_FK`, `categoryName`, sum(unitCost *  quantity) as `price`
							FROM shopitems si
							INNER JOIN shopitemcategories sic
							ON si.categoryID_FK = sic.categoryID
							WHERE isPurchased=%d AND si.userID_FK=%d AND si.dateAdded < '%s' 
							GROUP BY `categoryID`", 
							$isPurchased,
							parent::GetUserID(),
							$date_to->format('Y-m-d'));
		} else {
			throw new Exception("Both To and From Dates Cannot be null.");
		}
			
		$result = $this->get_db_con()->query($sql);
		$result_set = array();
		while ($result && $row = $result->fetch_array()) {
			$result_set[] = array(
				self::$kCol_CategoriesName => $row[self::$kCol_CategoriesName],
				self::$kCol_CategoriesId   => $row[self::$kCol_ItemsCategory],
				self::$kCol_ItemsPrice => $row[self::$kCol_ItemsPrice]);
		}
		return $result_set;
	}	
}

}
?>