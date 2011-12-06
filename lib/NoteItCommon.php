<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../config/config.php');

if (!class_exists('NI'))
{
    class NI
    {
        const TRACE_ENABLED = FALSE;    // TRUE - Prints output, otherwise doesn't

        public static function TRACE($str, $file, $line)
        {
            if (self::TRACE_ENABLED)
                self::TRACE_ALWAYS($str, $file, $line);
        }

        public static function TRACE_ALWAYS($str, $file, $line)
        {
            echo($str . " (File: " . $file . " Line: " . $line . ")<br />");
        }
    }
}

if (!function_exists("base"))
{
	function get_virtual_path($tail = '')
	{
		global $config;
		return 'http://' . $_SERVER['SERVER_NAME'] . 
			DIRECTORY_SEPARATOR . $config['APP_DIR'] . DIRECTORY_SEPARATOR . $tail;			
	}
}

?>