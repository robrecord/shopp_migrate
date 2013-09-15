<?php
/**
*
*/
class Dev
{
	public static $old_error_handler;
	public static $plugin_path;
	public static $record_output;
	public static $flush_ext;
	public static $error_log_file;

	public static function start($plugin_path, $record_output = false, $flush_ext = null)
	{
		self::$record_output = $record_output;
		self::$plugin_path = $plugin_path;
		self::$flush_ext = $flush_ext;
		self::$error_log_file = $plugin_path.'error.log';
		define('DEV', true);
		Dev::handle_errors();
		Dev::log_errors();
		if ($record_output) {
			ob_start();
			echo "# START ". date("H:i:s");
		}
	}

	public static function end()
	{
		error_log("# END #");
		if (self::$record_output) {
			echo "# END ".date("H:i:s");
			self::flush_output();
		}
	}

	public static function log_errors($error_log_file = null)
	{
		if ($error_log_file) self::$error_log_file = $error_log_file;
		if( file_exists(self::$error_log_file))
		{
		if (( $log_handle = @fopen(self::$error_log_file, "r+") ) !== false) {
		    ftruncate($log_handle, 0);
		    fclose($log_handle);
		}
		}
		ini_set('error_log', self::$error_log_file);
		ini_set('log_errors', 1);
		error_log("# START #");
	}

	public static function handle_errors()
	{

		if (PHP_SAPI == 'cli' || DEV) {
			ini_set('log_errors', 1);
			// ini_set('display_errors', 0);
			// ini_set('html_errors', 0);
		}
		// error handler function
		self::$old_error_handler = set_error_handler(array("Migrate_Exception", "errorHandlerCallback"), E_ALL);

		// self::$old_error_handler = set_error_handler(array('self', 'error_handler'));
	}



	public static function error_handler($errno, $errstr, $errfile, $errline)
	{
		echo "ERROR";
	    if (!(error_reporting() & $errno)) {
	        // This error code is not included in error_reporting
	        return;
	    }

	    switch ($errno) {
	    case E_USER_ERROR:
	        echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
	        echo "  Fatal error on line $errline in file $errfile";
	        echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
	        echo "Aborting...<br />\n";
	        exit(1);
	        break;

	    case E_USER_WARNING:
	        echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
	        break;

	    case E_USER_NOTICE:
	        echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
	        break;

	    default:
	        echo "Unknown error type: [$errno] $errstr<br />\n";
	        break;
	    }

	    self::flush_output();
	    /* Don't execute PHP internal error handler */
	    return true;
	}

	public static function flush_output($ext = null)
	{
		$script_output = ob_get_contents();
		ob_end_flush();
		self::output_to_file($script_output, $ext ? : self::$flush_ext);
	}

	public static function output_to_md($data)
	{
		self::output_to_file($data, 'md');
	}

	public static function output_to_json($data, $filename = 'output')
	{
		// self::output_to_file(json_encode($data, JSON_PRETTY_PRINT), 'json', $filename);
		self::output_to_file(json_encode($data), 'json', $filename);

	}

	public static function output_to_file($data, $ext = 'html', $name = 'output')
	{
		$output_file_handle = fopen(self::$plugin_path."$name.$ext", "w");
		fwrite( $output_file_handle, $data);
		fclose($output_file_handle);
	}
}
class Migrate_Exception extends Exception {
  public static function errorHandlerCallback($code, $string, $file, $line, $context) {
    $e = new self($string, $code);
    $e->line = $line;
    $e->file = $file;
    // Dev::error_handler();
    Dev::flush_output();
	throw $e;
  }
}
