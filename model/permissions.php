<?php 
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "../lib/noteitcommon.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "accesscontrolledobject.php";

if(class_exists('Permissions') != TRUE) {

	class Permissions {
		
		const Read		= 1;
		const Write 	= 2;
		const Delete	= 4;

		const None		= 0; // No permissions
		const RWD		= 7; // Read, Write & Delete

		protected  static function check_perms($user_id, AccessControlledObject $object, $required_perm) {

			if (!is_subclass_of($object, 'AccessControlledObject'))
				throw new Exception("Object not an access controlled object");
				
			if (get_class($object) == "ShoppingList") {
				if ($object->getOwnerId() == $user_id ||
						($object->getSharedUserId() == $user_id && ($object->getPerms() & $required_perm) > 0)) {
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				throw new Exception('Unknown Object Type');
			}
		}
		
		public static function can_read($user_id, AccessControlledObject $object) {
			return self::check_perms($user_id, $object, self::Read);		
		}
		
		public static function can_write($user_id, AccessControlledObject $object) {
			return self::check_perms($user_id, $object, self::Write);
		}
		
		public static function can_delete($user_id, AccessControlledObject $object) {
			return self::check_perms($user_id, $object, self::Delete);
		}
	}
}

?>