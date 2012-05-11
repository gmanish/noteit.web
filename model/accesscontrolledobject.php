<?php 
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "../lib/noteitcommon.php";

if (!class_exists('AccessControlledObject')) {

	interface AccessControlledObject {
		
		public function getOwnerId();
		public function getPerms();
		public function getSharedUserId();
	} 
}


?>