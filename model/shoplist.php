<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "accesscontrolledobject.php";

if (!class_exists('ShoppingList')) {

	// Note: the names of the class member variable are important as the objects will 
	// directly be converted to JSON objects in order to send the info to client
	class ShoppingList implements AccessControlledObject {
		
		public $listID 			= 0;
		public $listName 		= '';
		public $userID_FK		= 0; 
		public $itemCount		= 0;
		public $perms			= 0;
		public $shared_user_id	= 0;
		
		function __construct() {
		}
		
		public static function create_from_fields(
				$list_id, 
				$list_name, 
				$owner_id, 
				$item_count, 
				$shared_user_id = 0, 
				$perms = Permissions::None) {

			$instance = new ShoppingList();

			$instance->listID 			= $list_id;
			$instance->listName 		= $list_name;
			$instance->userID_FK 		= $owner_id;
			$instance->itemCount		= $item_count;
			$instance->shared_user_id	= !is_null($shared_user_id) ? $shared_user_id : 0;
			$instance->perms			= !is_null($perms) ? $perms : Permissions::None;
			
			return $instance;
		}
		
		public static function create_from_db($db_con, $list_id, $user_id) {
			
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
					(sl.userID_FK=%d OR sls.`user_id_FK`=%d) AND sl.listID=%d
					LIMIT 1",
					$user_id,
					$user_id,
					$list_id);
			
			$result = $db_con->query($sql);
			if (!$result) {
				throw new Exception("Failed to retrieve Shopping List (" . $this->get_db_con()->errno . ")");
			}
			
			if ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
			
				$result->free();
				return ShoppingList::create_from_fields(
						intval($row['listID']),
						$row['listName'],
						intval($row['userID_FK']),
						0,
						!is_null($row['user_id_FK']) ? intval($row['user_id_FK']) : 0,
						!is_null($row['user_perms']) ? intval($row['user_perms']) : 0);
			} else {
				throw new Exception("Failed to retrieve Shopping List (" . $this->get_db_con()->errno . ")");
			}
		}
		
		public function getOwnerId() {
			return $this->userID_FK;	
		}
		
		public function getPerms() {
			return $this->perms;
		}
		
		public function getSharedUserId() {
			return $this->shared_user_id;
		}
	}
} 

?>