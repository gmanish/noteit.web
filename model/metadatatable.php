<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "../lib/noteitcommon.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "tablebase.php";

if (class_exists("MetadataTable") != TRUE) {
	
	class MetadataTable extends TableBase {

		const kTable_Name 		= 'shopitems_metadata';
		const kCol_ItemId		= 'itemId_FK';
		const kCol_UserId		= 'userId_FK';
		const kCol_Vote			= 'vote';
		const kCol_DateVoted	= 'date_voted';
		
		function __construct($db_base, $user_ID) {
				
			parent::__construct($db_base, $user_ID);
		}
		
		function do_like_item($itemId) {
				
			$sql = sprintf("INSERT INTO `shopitems_metadata` 
					(`itemId_FK`, `userId_FK`, `vote`, `date_voted`) 
					VALUES(%d, %d, 1, NOW())", $itemId, parent::GetUserID());
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE && $this->get_db_con()->errno == 1062){
				$sql = sprintf("UPDATE `shopitems_metadata` 
								SET `vote`=1, `date_voted`=NOW()  
								WHERE `itemId_FK`=%d  AND `userId_FK`=%d",
								$itemId, parent::GetUserID());
				$result = $this->get_db_con()->query($sql);
				if ($result == FALSE) {
					throw new Exception(
						"Error Liking Item (" . 
						$this->get_db_con()->errno . ")");
				}				
			} else if ($result == FALSE){
				throw new Exception("Error Liking Item (" .
					$this->get_db_con()->errno . ")");
			}
			// Return the count of likes
			return $this->do_count_likes($itemId);
		}
		
		function do_dislike_item($itemId) {
				
			$sql = sprintf("INSERT INTO `shopitems_metadata` 
					(`itemId_FK`, `userId_FK`, `vote`, `date_voted`)
					VALUES (%d, %d, -1, NOW())", $itemId, parent::GetUserID());
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE && $this->get_db_con()->errno == 1062) {
				$sql = sprintf("UPDATE `shopitems_metadata`
								SET `vote`=-1, `date_voted`=NOW()
								WHERE `itemId_FK`=%d AND `userId_FK`=%d",
								$itemId, parent::GetUserID());
				$result = $this->get_db_con()->query($sql);
				if ($result == FALSE) {
					throw new Exception(
						"Error Disliking Item (" . 
						$this->get_db_con()->errno . ")");
				}				
			} else if (result == FALSE){
				throw new Exception("Error Disliking Item (" . 
					$this->get_db_con()->errno . ")");
			}
			// Return the count of likes
			return $this->do_count_likes($itemId);
		}
		
		function do_count_likes($itemId) {
			
			$sql = sprintf("SELECT SUM(`vote`) FROM `shopitems_metadata` WHERE `itemId_FK`=%d", $itemId);
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {
				return 0;
			} else {
				$row = $result->fetch_row();
				$result->free();
				return $row[0];
			}
		}
	}
}
?>