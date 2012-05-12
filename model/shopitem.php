<?php 

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../lib/noteitcommon.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'accesscontrolledobject.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'permissions.php';

if (!class_exists('ShopItem')) {
	
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
	
		public $_instance_id 		= 0;
		public $_item_id 			= 0;
		public $_user_id 			= 0;
		public $_list_id 			= 0;
		public $_category_id 		= 0;
		public $_item_name 			= '';
		public $_unit_cost 			= 0.00;
		public $_quantity 			= 0.00;
		public $_unit_id 			= 0;
		public $_date_added			= '';
		public $_date_purchased 	= '';
		public $_is_purchased 		= 0; // TINYINT should be 0 or 1
		public $_is_asklater 		= 0; // TINYINT should be 0 or 1
		public $_barcode 			= "";
		public $_barcode_format 	= BarcodeFormat::BARCODE_FORMAT_UNKNOWN;
		public $_voteCount 			= 0;
	
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
				$barcode_format = BarcodeFormat::BARCODE_FORMAT_UNKNOWN,
				$vote_count = 0)
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
			$this->_voteCount = $vote_count;
		}
	}
}
?>