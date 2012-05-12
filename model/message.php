<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "../lib/noteitcommon.php";

if (!class_exists('Message')) {
	
	class Message {
		
		public $message_id		= 0;
		public $user_id 		= 0;
		public $from_user_id 	= 0;
		public $date_received	= '';
		public $subject			= '';
		public $text		 	= '';
		public $is_read			= 0;
		 
		function __construct(
				$message_id,
				$user_id, 
				$from_user_id,
				$date_received,
				$subject, 
				$text, 
				$is_read) {
			
			$this->message_id 		= $message_id;
			$this->user_id 			= $user_id;
			$this->from_user_id 	= $from_user_id;
			$this->date_received	= $date_received;
			$this->subject			= $subject;
			$this->text				= $text;
			$this->is_read			= $is_read;
		}
	}
}
?>