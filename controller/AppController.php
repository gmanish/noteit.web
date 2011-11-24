<?php
        require_once $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . ("noteit.web/controller/ControllerDefines.php");
        require_once $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . ("noteit.web/controller/CommandHandler.php");

        if (session_id() == "") 
	{
		session_start();
	}
	
	ob_start();

	
	$command = isset($_REQUEST[Command::$tag]) ? $_REQUEST[Command::$tag] : "";
	
	try
	{
		if ($command == "")
			throw new Exception("Handler Not Found: Please implement the CommandHandler::$command Handler in CommandHandler.php");
			
		CommandHandler::$command();	
	}
	catch(Exception $e)
	{
//		echo $e->getMessage();
		echo('Unhandled exception: ' . $e->getMessage());
	}
?>