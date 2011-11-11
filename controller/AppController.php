<?php
	if (session_id() == "") 
	{
		session_start();
	}

	require_once $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . ('FirePHPCore/fb.PHP');
	require_once $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . ('noteit.web/controller/CommandHandler.php');
	
	ob_start();

	FB::setEnabled(TRUE);
	fb($_REQUEST, 'Dumping _REQUEST array: ');

//	require_once('../../FirePHPCore/FirePHP.class.php');


//	$firephp = & FirePHP::getInstance(true);
//	$firephp->log($_REQUEST, 'Dumping _REQUEST array: ');	
	
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