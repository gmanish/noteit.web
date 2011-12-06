<?php
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . ("ControllerDefines.php");
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . ("CommandHandler.php");

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