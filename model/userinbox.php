<?php 
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "../lib/noteitcommon.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tablebase.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'message.php';

if (!class_exists('UserInbox')) {
	
	class UserInbox extends TableBase {

		const kTableInbox_Name				= 'user_inbox';
		const kTableMessages_Name			= 'messages';
		
		const kInboxCol_MessageID			= 'messageid_FK';
		const kInboxCol_UserID 				= 'userid_FK'; // to user
		const kInboxCol_IsRead				= 'is_read';
		
		const kMessagesCol_MessageID		= 'messageid';
		const kMessagesCol_FromUserID		= 'from_userid'; // from user
		const kMessagesCol_DateReceived		= 'date_received';
		const kMessagesCol_Message			= 'message';
		const kMessagesCol_Subject			= 'subject';
		
		function __construct($db_base, $user_ID)
		{
			parent::__construct($db_base, $user_ID);
		}
		
		function get_message_headers($ignore_read) {
			
			$sql = sprintf("SELECT ui.messageid_FK, ui.userid_FK, ui.is_read, msg.from_userid, msg.date_received, msg.subject 
							FROM `user_inbox` ui
							INNER JOIN `messages` msg
							ON ui.messageid_FK = msg.messageid
							WHERE `userid_FK` = %d", 
							parent::GetUserID());
			
			$sql .= $ignore_read > 0 ? " AND `is_read` != 1" : "";
			$sql .= " ORDER BY date_received ASC";
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {
				throw new Exception("Error in reading Inbox contents. (" . $this->get_db_con()->errno . ")");
			}
			
			$messages = array();
			while ($row = mysqli_fetch_assoc($result)) {
				
				$messages[] = new Message(
						$row[self::kInboxCol_MessageID],
						$row[self::kInboxCol_UserID], 
						$row[self::kMessagesCol_FromUserID], 
						$row[self::kMessagesCol_DateReceived],
						$row[self::kMessagesCol_Subject],
						'',										 // We don't read message text 							 
						$row[self::kInboxCol_IsRead]);
			}
			
			return  $messages;
		}
		
		function get_message($messageid) {
			
			$sql = sprintf("SELECT * 
							FROM `user_inbox` ui
							INNER JOIN `messages` msg
							ON ui.messageid_FK = msg.messageid
							WHERE ui.`userid_FK` = %d AND msg.`messageid`=%d
							LIMIT 1", 
							parent::GetUserID(),
							$messageid);
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {
				throw new Exception("Error in retrieving message. (" . $this->get_db_con()->errno . ")");
			}
			
			if ($row = mysqli_fetch_assoc($result)) {
				return new Message(
						$row[self::kInboxCol_MessageID],
						$row[self::kInboxCol_UserID], 
						$row[self::kMessagesCol_FromUserID], 
						$row[self::kMessagesCol_DateReceived],
						$row[self::kMessagesCol_Subject],
						$row[self::kMessagesCol_Message], 							 
						$row[self::kInboxCol_IsRead]);
			} else {
				throw new Exception("Error in retrieving message. (" . $this->get_db_con()->errno . ")");
			}
		}
		
		function mark_read($messageid) {
			
			$sql = sprintf("UPDATE `user_inbox` 
					SET `is_read`=1
					WHERE `messageid_FK`=%d AND `userid_FK`=%d",
					$messageid,
					self::GetUserID());
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {
				throw new Exception("Error in updating message status. (" . $this->get_db_con()->errno . ")");
			}
		}
		
		function send_message(Message $message) {
			
			$sql = sprintf("INSERT INTO `messages` (`from_userid`, `date_received`, `subject`, `message`) 
							VALUES (%d, NOW(), '%s', '%s')",
							$message->from_user_id,
							$this->get_db_con()->escape_string($message->subject),
							$this->get_db_con()->escape_string($message->text));
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {
				throw new Exception("Error in sending message. (" . $this->get_db_con()->errno . ")");
			}
			
			$message_id = $this->get_db_con()->insert_id;
			$sql = sprintf("INSERT INTO `user_inbox` (`messageid_FK`, `userid_FK`, `is_read`)
							VALUES (%d, %d, %d)",
							$message_id,
							$this->GetUserID(),
							0);
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {
				throw new Exception("Error in posting message to user's inbox. (" . $this->get_db_con()->errno . ")");
			}
		}
	}
}
?>