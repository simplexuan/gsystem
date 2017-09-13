<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * Logging Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Logging
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/general/errors.html
 */
class CI_Log {
    const LOG_SPACE = "\t";
    const PAGE_SIZE = 4096;
	const MARK      = 'ifchange_notice ';
    protected $_enabled	= TRUE;
    protected $_levels	= array('FATAL'=>1,'ERROR'=>'2','WARN'=>'3', 'INFO' =>'4', 'TRACE'=>5, 'DEBUG' => '6', 'ALL' => '8');
    protected $logger;
    protected $initialized = FALSE;
    protected $_threshold  = 1;
    protected $arr_basic   = array();
    protected $log_file;
	static $basic_fields   = array (
			/*'signid',
			'ip',
			'uid',
			'uname',
			'm',
			'c',
			'provider',
			'uri',
			'worker',
			'auth',*/
            'class_name',
            'product_name', //产品线标示，example: tob_web
            'log_id', //请求唯一ID
            'receive_time',
            'cost',
            'client_ip',
            'local_ip',
            'user_ip',//用户端IP
            'uid',
            'session_id', //浏览器生成的唯一ID
            'cost',
            'request_api'
    );
    static $lack_fields = array();
    protected $basic_info;
    protected $log_str;
    protected $info_str;
    function getEffectiveLevel(){
        return $this->logger->getEffectiveLevel();
    }
    public function add_basic_info($arr_basic_info)
    {
        $this->arr_basic = array_merge($this->arr_basic, $arr_basic_info);
        $this->gen_basic_info();
    }
    public function gen_basic_info() {
        $this->basic_info = '';
        foreach (self::$basic_fields as $key) {
            if (!empty($this->arr_basic[$key])) {
                unset(self::$lack_fields[$key]);
                $this->basic_info .= $this->gen_log_part($this->arr_basic[$key]);
            }else{
                self::$lack_fields[$key] = '';
                $this->basic_info .= $this->gen_log_part($key);
            }
        }
    }
    public function push_info($format, $arr_data)
    {
        $this->info_str .= $this->gen_log_part(vsprintf($format, $arr_data)) . ' ';
    }

    public function clear_info()
    {
		unset($this->info_str);
		unset($this->arr_basic);
        $this->info_str = '';
        $this->arr_basic= array();
    }
    private function gen_log_part($str)
    {
		if ( ENVIRONMENT =='production' && strlen($str)> 4096){
			$str = substr($str, 0, 1024).'...truncated';
		}
        return self::LOG_SPACE . $str;
    }
    /**
     * Constructor
     */
    public function __construct()
    {

        if ($this->initialized === false) {
            $config =& get_config();
            if (is_numeric($config['log_threshold'])){
                $this->_threshold = $config['log_threshold'];
            }
            $this->initialized = true;
            if (class_exists('LoggerPropertyConfigurator')){
                $config_file = APPPATH . 'config/log4php.properties';
                if ( defined('ENVIRONMENT') && file_exists( APPPATH . 'config/' . ENVIRONMENT . '/log4php.properties' ) ) {
                    $config_file = APPPATH . 'config/' . ENVIRONMENT . '/log4php.properties';
                }
                if ( defined('ENVIRONMENT') && file_exists( APPPATH . 'config/' . ENVIRONMENT . '/log4php_work.properties' ) ) {
                    $config_file = APPPATH . 'config/' . ENVIRONMENT . '/log4php_work.properties';
                }
                LoggerPropertyConfigurator::configure($config_file);
                $this->logger = LoggerManager::getLogger('icw');
            }else{
                $config_file = APPPATH . 'config/log4php_.properties';
                if ( defined('ENVIRONMENT') && file_exists( APPPATH . 'config/' . ENVIRONMENT . '/log4php_.properties' ) ) {
                    $config_file = APPPATH . 'config/' . ENVIRONMENT . '/log4php_.properties';
                }
                require_once BASEPATH . '../log4php/Logger.php';
                Logger::configure($config_file);
                $this->logger = Logger::getRootLogger();
            }
        }
        $this->arr_basic= array();
        $this->arr_basic['receive_time'] = microtime(true); 

    }

    // --------------------------------------------------------------------

    /**
     * Write Log File
     *
     * Generally this function will be called using the global log_message() function
     *
     * @param	string	the error level
     * @param	string	the error message
     * @param	bool	whether the error is a native PHP error
     * @return	bool
     */
    public function write_log($level = 'error', $msg, $file='rootLogger', $php_error = FALSE)
    {
        if ($this->_enabled === FALSE) {
            return FALSE;
        }
        //替换base info中占位符
        if(!empty(self::$lack_fields)){
            $this->basic_info = strtr($this->basic_info, self::$lack_fields);
        }

        $level = strtoupper($level);
    	if( !isset($this->logger_extend[$file])){
	        if (class_exists('LoggerPropertyConfigurator')){
    	    	$this->logger_extend[$file] = LoggerManager::getLogger($file);
       		}else{
        		$this->logger_extend[$file] = Logger::getLogger($file);
       		}
        }
        if ( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold) ) {
            return FALSE;
        }
        if (strlen($msg)> self :: PAGE_SIZE){
           $msg = mb_substr($msg, 0, self :: PAGE_SIZE, 'UTF-8'). ' ... truncated'; 
        }
    	switch ($level) {
			case 'ERROR':
			    //$msg = self :: MARK . $msg ;
				$this->log_str = "\tERROR" . $this->basic_info . self::LOG_SPACE . $msg;
				$this->logger_extend[$file]->error($msg);
				break;
			case 'INFO':
				$this->log_str = "\tINFO" . $this->basic_info. $this->info_str . self::LOG_SPACE . $msg;
				$this->logger_extend[$file]->info($this->log_str);
				$this->clear_info();
				break;
			case 'WARN':
			    //$msg = self :: MARK . $msg ;
				$this->log_str = "\tWARN" . $this->basic_info . self::LOG_SPACE . $msg;
				$this->logger_extend[$file]->warn($this->log_str);
				break;
			case 'FATAL':
			    //$msg = self :: MARK . $msg ;
				$this->log_str = "\tFATAL" . $this->basic_info . self::LOG_SPACE . $msg;
				$this->logger_extend[$file]->fatal($this->log_str);
				$this->clear_info();
				break;
			case 'TRACE':
				$this->logger_extend[$file]->trace("\tTRACE" . self::LOG_SPACE . $msg);
				break;
			case 'DEBUG':
				$this->logger_extend[$file]->debug("\tDEBUG" . self::LOG_SPACE .$msg);
				break;
			default:
                $this->logger_extend[$file]->debug("\tDEBUG" . self::LOG_SPACE .$msg);
				break;
		}
        

        return TRUE;
    }
    public function warn($msg, $file='rootLogger'){
        $this->write_log('warn', $msg, $file);
    }
    public function debug($msg, $file='rootLogger'){
        $this->write_log('debug', $msg, $file);
    }
    public function error($msg, $file='rootLogger'){
        $this->write_log('error', $msg, $file);
    }
    public function fatal($msg, $file='rootLogger'){
        $this->write_log('fatal', $msg, $file);
    }
    public function trace($msg, $file='rootLogger'){
        $this->write_log('trace', $msg, $file);
    }
    public function info($msg, $file='rootLogger'){
        $this->write_log('info', $msg, $file);
    }

}
// END Log Class

/* End of file Log.php */
/* Location: ./CodeIgniter/libraries/Log.php */
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
