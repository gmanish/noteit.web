<?php 
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "../lib/noteitcommon.php";

if (!class_exists('AccessControlledObject')) {

	abstract class AccessControlledObject {
		
		abstract public function getOwnerId();
		abstract public function getPerms();
		abstract public function getSharedUserId();
	} 
}


?>