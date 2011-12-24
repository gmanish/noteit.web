<?php
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . ("controllerdefines.php");
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . ("commandhandler.php");

    if (session_id() == "") {
		session_start();
	}
	
	ob_start();

	$command = isset($_REQUEST[Command::$tag]) ? $_REQUEST[Command::$tag] : "";
	
	try {
		if ($command == "" || !is_callable("CommandHandler::$command")) {
			throw new Exception("Handler Not Found");
		} else {
			CommandHandler::$command();	
		}
	}
	catch(Exception $e) {
		echo('Unhandled exception: ' . $e->getMessage());
	}
?>