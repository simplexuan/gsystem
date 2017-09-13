<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * API数据接口类
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Logging
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/general/errors.html
 */
class CI_Api {

    public $_app_path;
    public $_api_app_path;
	
	/**
	 * Constructor
	 */
	public function __construct()
    {
        $this->_app_path = '/opt/wwwroot/rd/easyhunter/';
        if (preg_match('#^/data/liugj/#', __FILE__)){
            $this->_app_path = '/data/liugj/wwwroot/rd/easyhunter/';
        }
    }


    public function call_api($app, $model, $function ,$params = array())
    {
        try{
            $this->start_api($app);
            $return = $this->get_api($model, $function ,$params);
            $this->end_api($app);
        } catch(Exception $e) {
            $this->end_api($app);
            throw $e; 
        }
        return $return;
    }

    public function start_api($app)
    {
        global $_API_APP_START_PATH;

        $_API_APP_START_PATH = $this->_app_path . $app . '/';        
	//	$CI =& get_instance();
        //array_unshift($CI->config->_config_paths, $_API_APP_START_PATH);
    }

    public function end_api()
    {
        unset($GLOBALS['_API_APP_START_PATH']);
        $CI =& get_instance();
        //array_shift($CI->config->_config_paths);
        if (isset($CI->db)) {
            //$CI->db->close();
        }
        //if (isset($CI->cache->memcached)) {
        //    unset($CI->cache->memcached);
        //    //$CI->db->close();
        //}
        $CI->config->load();
        if ($CI->config->config['base_url'] == '') {
            if (isset($_SERVER['HTTP_HOST'])) {
                $base_url = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
                $base_url .= '://'. $_SERVER['HTTP_HOST'];
                $base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
            } else {
                $base_url = 'http://localhost/';
            }
            $CI->config->set_item('base_url', $base_url);
        }
        //$CI->db = $CI->load->database('', TRUE);
    }

    public function get_api($model, $function ,$params)
    {	
        global $_API_APP_START_PATH;
        log_message('debug', sprintf('%s:%d call get_api %s:%s %s', __FILE__, __LINE__, 
                    $model, $function, var_export($params, TRUE))
                );
		$path = '';

		if (($last_slash = strrpos($model, '/')) !== FALSE)
		{
			$path = substr($model, 0, $last_slash + 1);
			$model = substr($model, $last_slash + 1);
		}
		
		$model = strtolower($model);
		if ( ! file_exists($_API_APP_START_PATH . 'apis/' . $path . $model . '.php'))
		{
            log_message('warn', sprintf('%s:%d call get_api not exist %s:%s %s', __FILE__, __LINE__, 
                    $model, $function, var_export($params, TRUE))
                );
			exit;
		}
		
		if ( ! class_exists('CI_Model'))
		{
			load_class('Model', 'core');
        }
		if ( ! class_exists('CI_Dao'))
		{
			load_class('Dao', 'core');
		}
        include($_API_APP_START_PATH.'config/config.php');
        $db_path = $_API_APP_START_PATH.'config/'.ENVIRONMENT.'/common.db.php';


        if (file_exists($db_path)) {
            require_once($db_path);
        }
        $mymodel = $_API_APP_START_PATH.'core/'.$config['subclass_prefix'].'model.php';
        if (file_exists($mymodel)) {
            require_once($mymodel);
        }
        $mydao = $_API_APP_START_PATH.'core/'.$config['subclass_prefix'].'dao.php';
        if (file_exists($mydao)) {
            require_once($mydao);
        }
        $CI =& get_instance();
        //$CI->config->config = array();
        //array_unshift($CI->config->_config_paths, $_API_APP_START_PATH);
        $CI->config->load('','','',TRUE);

        if(file_exists($_API_APP_START_PATH.'config/config.err_no.php')) {
            include($_API_APP_START_PATH.'config/config.err_no.php');
            $CI->config->config['err_no'] = $config['err_no'];
            $CI->config->config['err_msg'] = $config['err_msg'];
        } 
        if (file_exists($_API_APP_START_PATH.'config/config.tableMap.php')){
            include($_API_APP_START_PATH.'config/config.tableMap.php');
            $CI->config->config['tableMap'] = $config['tableMap'];
        }

        //$CI->db = $CI->load->database('', TRUE);

		require_once($_API_APP_START_PATH . 'apis/' . $path . $model . '.php');

		$nmobel = ucfirst($model);
            
		$CI->$model = new $nmobel();

        // return $CI->$model->$function($params);
        $rs = $CI->$model->$function(is_array($params) ? $params : '');

		//$this->_ci_models[] = $model;

		return $rs;
    }
}
