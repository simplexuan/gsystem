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
 * CodeIgniter Model Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/config.html
 */
class CI_Model {

	/**
	 * Constructor
	 *
	 * @access public
	 */
	function __construct()
	{
		log_message('debug', "Model Class Initialized");
	}

	/**
	 * __get
	 *
	 * Allows models to access CI's loaded classes using the same
	 * syntax as controllers.
	 *
	 * @param	string
	 * @access private
	 */
	function &__get($key)
	{
		$CI =& get_instance();
		if ($key =='db' && !isset($CI->$key)){ 
			$this->load->database('', FALSE, TRUE);
			//$CI->db->conn_id->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			//$CI->db->conn_id->query('set names utf8');
			return $CI->$key;
		}
		elseif($key=='cache' && !isset($CI->$key)){ 
			$this->load->driver('cache', NULL, 'cache');
		}
		return $CI->$key;
	}
//    public function work($worker_name, $param, $header=array(), $client_timeout_ms=20000, $read_timeout_us=20000000){
//        $this->load->library('Gearman_Client', '' ,'gc');
//        $this->gc->gearman_client($worker_name, $client_timeout_ms, $read_timeout_us);
//        if (!$header) {
//            $header = isset($this->request_header[getmypid()])?$this->request_header[getmypid()]:array();
//        }
//        $res = $this->gc->do_job_foreground($worker_name, $param, $header);
//        if ($res['response']['err_no']!=0){
//            throw new Exception(sprintf('%s:%d call_gearman %s failure response:%s', 
//                        __FILE__, __LINE__, $worker_name,  $res['response']['err_msg']), 
//                    $res['response']['err_no']);
//        }
//        return $res['response']['results'];
//    }
//    public function work_background($worker_name, $param, $header=array()){
//        $this->load->library('Gearman_Client', '' ,'gc');
//        $this->gc->gearman_client($worker_name, 10000, 10000*1000);
//        if (!$header) {
//            $header = isset($this->request_header[getmypid()])?$this->request_header[getmypid()]:array();
//        }
//        $res = $this->gc->do_job_background($worker_name, $param, $header);
//        if ($res['response']['err_no']!=0){
//            throw new Exception(sprintf('%s:%d call_gearman %s failure response:%s', __FILE__, __LINE__, $worker_name, 
//                        $res['response']['err_msg']), 
//                    $res['response']['err_no']
//                    );
//        }
//        return $res['response']['results'];
//    }
//    public function work_foreground($worker_name, $param, $header=array(), $client_timeout_ms=20000, $read_timeout_us=20000000){
//        return $this->work($worker_name, $param, $header, $client_timeout_ms, $read_timeout_us);
//    }
}
// END Model Class

/* End of file Model.php */
/* Location: ./system/core/Model.php */
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
