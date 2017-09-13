<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Common Functions
 *
 * Loads the base classes and executes the request.
 *
 * @package		CodeIgniter
 * @subpackage	codeigniter
 * @category	Common Functions
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/
 */

// ------------------------------------------------------------------------

/**
 * Determines if the current version of PHP is greater then the supplied value
 *
 * Since there are a few places where we conditionally test for PHP > 5
 * we'll set a static variable.
 *
 * @access	public
 * @param	string
 * @return	bool	TRUE if the current version is $version or higher
 */
if (!function_exists('is_php')) {
	function is_php($version = '5.0.0') {
		static $_is_php;
		$version = (string) $version;

		if (!isset($_is_php[$version])) {
			$_is_php[$version] = (version_compare(PHP_VERSION, $version) < 0) ? FALSE : TRUE;
		}

		return $_is_php[$version];
	}
}

// ------------------------------------------------------------------------

/**
 * Tests for file writability
 *
 * is_writable() returns TRUE on Windows servers when you really can't write to
 * the file, based on the read-only attribute.  is_writable() is also unreliable
 * on Unix servers if safe_mode is on.
 *
 * @access	private
 * @return	void
 */
if (!function_exists('is_really_writable')) {
	function is_really_writable($file) {
		// If we're on a Unix server with safe_mode off we call is_writable
		if (DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == FALSE) {
			return is_writable($file);
		}

		// For windows servers and safe_mode "on" installations we'll actually
		// write a file then read it.  Bah...
		if (is_dir($file)) {
			$file = rtrim($file, '/') . '/' . md5(mt_rand(1, 100) . mt_rand(1, 100));

			if (($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE) {
				return FALSE;
			}

			fclose($fp);
			@chmod($file, DIR_WRITE_MODE);
			@unlink($file);
			return TRUE;
		} elseif (!is_file($file) OR ($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE) {
			return FALSE;
		}

		fclose($fp);
		return TRUE;
	}
}

// ------------------------------------------------------------------------

/**
 * Class registry
 *
 * This function acts as a singleton.  If the requested class does not
 * exist it is instantiated and set to a static variable.  If it has
 * previously been instantiated the variable is returned.
 *
 * @access	public
 * @param	string	the class name being requested
 * @param	string	the directory where the class should be found
 * @param	string	the class name prefix
 * @return	object
 */
if (!function_exists('load_class')) {
	function &load_class($class, $directory = 'libraries', $prefix = 'CI_') {
		static $_classes = array();
		if (isset($_classes[$class])) {
			return $_classes[$class];
		}

		$name = FALSE;
		foreach (array(APPPATH, BASEPATH) as $path) {
			if (file_exists($path . $directory . '/' . $class . '.php')) {
				$name = $prefix . $class;
				if (class_exists($name) === FALSE) {
					require $path . $directory . '/' . $class . '.php';
				}
				break;
			}
		}

		if (file_exists(APPPATH . $directory . '/' . config_item('subclass_prefix') . strtolower($class) . '.php')) {
			$name = config_item('subclass_prefix') . $class;
			if (class_exists($name) === FALSE) {
				require APPPATH . $directory . '/' . config_item('subclass_prefix') . strtolower($class) . '.php';
			}
		}

		if ($name === FALSE) {
			L("$class, $directory , $prefix", 1);
			exit('Unable to locate the specified class: ' . $class . '.php');
		}
		is_loaded($class);
		$_classes[$class] = new $name();
		return $_classes[$class];
	}
}

// --------------------------------------------------------------------

/**
 * Keeps track of which libraries have been loaded.  This function is
 * called by the load_class() function above
 *
 * @access	public
 * @return	array
 */
if (!function_exists('is_loaded')) {
	function &is_loaded($class = '') {
		static $_is_loaded = array();

		if ($class != '') {
			$_is_loaded[strtolower($class)] = $class;
		}
		return $_is_loaded;
	}
}

// ------------------------------------------------------------------------

/**
 * Loads the main config.php file
 *
 * This function lets us grab the config file even if the Config class
 * hasn't been instantiated yet
 *
 * @access	private
 * @return	array
 */
if (!function_exists('get_config')) {
	function &get_config($replace = array()) {
		static $_config;

		if (isset($_config)) {
			return $_config[0];
		}

		// Is the config file in the environment folder?
		if (!defined('ENVIRONMENT') OR !file_exists($file_path = APPPATH . 'config/' . ENVIRONMENT . '/config.php')) {
			$file_path = APPPATH . 'config/config.php';
		}

		// Fetch the config file
		if (!file_exists($file_path)) {
			log_message('warn', "The configuration file does not exist. $file_path");
			exit('The configuration file does not exist.');
		}

		require $file_path;

		// Does the $config array exist in the file?
		if (!isset($config) OR !is_array($config)) {
			log_message('warn', "Your config file does not appear to be formatted correctly. $file_path");
			exit('Your config file does not appear to be formatted correctly.');
		}

		// Are any values being dynamically replaced?
		if (count($replace) > 0) {
			foreach ($replace as $key => $val) {
				if (isset($config[$key])) {
					$config[$key] = $val;
				}
			}
		}

		return $_config[0] = &$config;
	}
}

// ------------------------------------------------------------------------

/**
 * Returns the specified config item
 *
 * @access	public
 * @return	mixed
 */
if (!function_exists('config_item')) {
	function config_item($item) {
		static $_config_item = array();

		if (!isset($_config_item[$item])) {
			$config = &get_config();

			if (!isset($config[$item])) {
				return FALSE;
			}
			$_config_item[$item] = $config[$item];
		}

		return $_config_item[$item];
	}
}

// ------------------------------------------------------------------------

/**
 * Error Handler
 *
 * This function lets us invoke the exception class and
 * display errors using the standard error template located
 * in application/errors/errors.php
 * This function will send the error page directly to the
 * browser and exit.
 *
 * @access	public
 * @return	void
 */
if (!function_exists('show_error')) {
	function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered') {
		$_error = &load_class('Exceptions', 'core');
		echo $_error->show_error($heading, $message, 'error_general', $status_code);
		exit(__FILE__ . __LINE__);
	}
}

// ------------------------------------------------------------------------

/**
 * 404 Page Handler
 *
 * This function is similar to the show_error() function above
 * However, instead of the standard error template it displays
 * 404 errors.
 *
 * @access	public
 * @return	void
 */
if (!function_exists('show_404')) {
	function show_404($page = '', $log_error = TRUE) {
		$_error = &load_class('Exceptions', 'core');
		$_error->show_404($page, $log_error);
		exit(__FILE__ . __LINE__);
	}
}

// ------------------------------------------------------------------------

/**
 * Error Logging Interface
 *
 * We use this as a simple mechanism to access the logging
 * class and send messages to be logged.
 *
 * @access	public
 * @return	void
 */
if (!function_exists('log_message')) {
	function log_message($level = 'error', $message, $php_error = FALSE) {
		static $_log;

		if (config_item('log_threshold') == 0) {
			return;
		}

		$_log = &load_class('Log');
		$_log->write_log($level, $message, $php_error);
	}
}

// ------------------------------------------------------------------------

/**
 * Set HTTP Status Header
 *
 * @access	public
 * @param	int		the status code
 * @param	string
 * @return	void
 */
if (!function_exists('set_status_header')) {
	function set_status_header($code = 200, $text = '') {
		$stati = array(
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',

			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			307 => 'Temporary Redirect',

			400 => 'Bad Request',
			401 => 'Unauthorized',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',

			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
		);

		if ($code == '' OR !is_numeric($code)) {
			show_error('Status codes must be numeric', 500);
		}

		if (isset($stati[$code]) AND $text == '') {
			$text = $stati[$code];
		}

		if ($text == '') {
			show_error('No status text available.  Please check your status code number or supply your own message text.', 500);
		}

		$server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;

		if (substr(php_sapi_name(), 0, 3) == 'cgi') {
			header("Status: {$code} {$text}", TRUE);
		} elseif ($server_protocol == 'HTTP/1.1' OR $server_protocol == 'HTTP/1.0') {
			header($server_protocol . " {$code} {$text}", TRUE, $code);
		} else {
			header("HTTP/1.1 {$code} {$text}", TRUE, $code);
		}
	}
}

// --------------------------------------------------------------------

/**
 * Exception Handler
 *
 * This is the custom exception handler that is declaired at the top
 * of Codeigniter.php.  The main reason we use this is to permit
 * PHP errors to be logged in our own log files since the user may
 * not have access to server logs. Since this function
 * effectively intercepts PHP errors, however, we also need
 * to display errors based on the current error_reporting level.
 * We do that with the use of a PHP error template.
 *
 * @access	private
 * @return	void
 */
if (!function_exists('_exception_handler')) {
	function _exception_handler($severity, $message, $filepath, $line) {
		// We don't bother with "strict" notices since they tend to fill up
		// the log file with excess information that isn't normally very helpful.
		// For example, if you are running PHP 5 and you use version 4 style
		// class functions (without prefixes like "public", "private", etc.)
		// you'll get notices telling you that these have been deprecated.
		if ($severity == E_STRICT) {
			return;
		}

		$_error = &load_class('Exceptions', 'core');

		// Should we display the error? We'll get the current error_reporting
		// level and add its bits with the severity bits to find out.
		if (($severity & error_reporting()) == $severity) {
			$_error->show_php_error($severity, $message, $filepath, $line);
		}

		// Should we log the error?  No?  We're done...
		if (config_item('log_threshold') == 0) {
			return;
		}

		$_error->log_exception($severity, $message, $filepath, $line);
	}
}
/**
 *
 * shutdown function
 *
 * Checking if last error is a fatal error
 * Here we handle the error, displaying HTML, logging, ...
 **/
if (!function_exists('_shutdown_function')) {
	function _shutdown_function() {
		$error = error_get_last();
		if (isset($error['type']) && ($error['type'] === E_ERROR || $error['type'] === E_USER_ERROR)) {
			_exception_handler($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}
}

// --------------------------------------------------------------------

/**
 * Remove Invisible Characters
 *
 * This prevents sandwiching null characters
 * between ascii characters, like Java\0script.
 *
 * @access	public
 * @param	string
 * @return	string
 */
if (!function_exists('remove_invisible_characters')) {
	function remove_invisible_characters($str, $url_encoded = TRUE) {
		$non_displayables = array();

		// every control character except newline (dec 10)
		// carriage return (dec 13), and horizontal tab (dec 09)

		if ($url_encoded) {
			$non_displayables[] = '/%0[0-8bcef]/'; // url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/'; // url encoded 16-31
		}

		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127

		do {
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		} while ($count);

		return $str;
	}
}

// ------------------------------------------------------------------------

/**
 * Returns HTML escaped variable
 *
 * @access	public
 * @param	mixed
 * @return	mixed
 */
if (!function_exists('html_escape')) {
	function html_escape($var) {
		if (is_array($var)) {
			return array_map('html_escape', $var);
		} else {
			return htmlspecialchars($var, ENT_QUOTES, config_item('charset'));
		}
	}
}
/** 记录日志函数
 * $msg string 日志信息
 * $level int 日志等级
 * $log_name string 日志文件名称
 * $path string 日志的文件存放的绝对路径
 * $msg_length int 日志长度 为0则不限制
 */
if (!function_exists('L')) {
	function L($msg, $level = 8, $log_name = 'gsystem.', $path = LOGPATH, $msg_legth = 4096) {
		$level_arr = array('ERROR' => 1, 'WARN' => 2, 'NOTICE' => 3, 'INFO' => 4, 'TRACE' => 5, 'DEBUG' => 6, 'SQL' => 7, 'ALL' => 8);
		!is_dir($path) && mkdir($path, 0755, true);
		$destination = $path . $log_name . date("Y-m-d");
		$now = date('Y-m-d H:i:s');
		if (is_array($msg)) {
			$msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
		}

		if ($msg_legth > 0 && strlen($msg) > $msg_legth) {
			$msg = mb_substr($msg, 0, $msg_legth, 'UTF-8') . ' ...';
		}

		$level_name = array_search($level, $level_arr);
		return error_log("{$now}\t{$level_name}\t{$msg}\r\n", 3, $destination);
	}
}

/**
 * logic 调用方法
 */
if (!function_exists("Logic")) {
	function Logic($class) {
		if (($last_slash = strrpos($class, '/')) !== FALSE) {
			$logic = substr($class, $last_slash + 1);
		}

		$file = APPPATH . "logics/" . strtolower($class) . '.php';
		if (!file_exists($file)) {
			return false;
		}

		require $file;
		$logic = ucfirst($logic);
		return new $logic();
	}
}

