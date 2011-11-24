<?php

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

?>