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
			throw new Exception("Handler Not Found: " . $command);
		} else {
			CommandHandler::$command();	
		}
	}
	catch(Exception $e) {
	    $arr = array(
	         JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
	         JSONCodes::kRetMessage => $e->getMessage());
	
	     echo(json_encode($arr));
	}
?>