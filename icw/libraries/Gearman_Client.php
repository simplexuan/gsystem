<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed gearman');
/**
 * Class to utilize Gearman http://gearman.org/
 * @author Aniruddha Kale
 * @author Sunil Sadasivan <sunil@fancite.com>
 */
class CI_Gearman_Client
{

    private   $CI;
    protected $errors = array();
    private   $client;
    protected $_read_timeout_us   = 5000000;
    protected $_server_key        = 'default';
    protected $_client_timeout_ms = 5000;

    /**
     * Constructor
     * @access public
     * @return void
     */
    public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->helper('alarm');
	}
    /**
     * Function to create a gearman client
     * @access public
     * @return void
     */
    public function gearman_client($server_key='default',$client_timeout_ms=500000, $read_timeout_us=5000000)
    {
        $this->client      = new GearmanClient();
        $this->client->setOptions(GEARMAN_CLIENT_FREE_TASKS);
        $this->_server_key = $server_key;
        $this->_client_timeout_ms  = $client_timeout_ms;
        $this->_read_timeout_us    = $read_timeout_us;
        $this->client->setTimeout($this->_client_timeout_ms);
        $this->errors = array();
    }
    /**
     * Perform a job in background for a client
     * @access public
     * @param string
     * @param string
     * @return void
     */
    public function do_job_background($function,$param, $header=array(), $pack_func='msgpack_pack')
	{
        $this->_auto_connect($function);
		if ($this->CI->log->getEffectiveLevel() =='DEBUG'){
			log_message('debug', "Gearman Library: Performed task with function $function with parameter". var_export($param, TRUE));
		}
		if (isset($this->CI->request_header[getmypid()])){
			$header =  array_merge($this->CI->request_header[getmypid()], $header);
		}
		//$unique =  isset($header['unique'])  && $header['unique'] ? $header['unique'] :  uniqid();
		$this->client->doBackground($function, $pack_func(array('request'=>$param,
						'header'=>$header)));
		$this->CI->log->push_info('(c:%s) (m:%s) (w:%s) ', array('c'=>$param['c'], 'm'=>$param['m'], 'w'=>$function));
	}

    /**
     * Perform a job in foreground for a client
     * @access public
     * @param string
     * @param string
     * @return string  
     */
    public function do_job_foreground($function, $param, $header, 
	$pack_func='msgpack_pack', $unpack_func='msgpack_unpack') 
	{
		$uuid = uniqid('', true);
		$this->_auto_connect($function);
		if ($this->CI->log->getEffectiveLevel() =='DEBUG'){
			log_message('debug', "Gearman Library: Performed task with function $function with parameter". var_export($param, TRUE));
		}
		if (isset($this->CI->request_header[getmypid()])){
			$header =  array_merge($this->CI->request_header[getmypid()], $header);
		}
		//$unique =  isset($header['unique'])  && $header['unique'] ? $header['unique'] :  uniqid();
		$tm_start    = gettimeofday();
		$this->CI->benchmark->mark($function.'_start');
		do {
			$result = $this->client->doNormal($function, $pack_func(array('request'=>$param,
							'header'=>$header)));
			switch($this->client->returnCode()) {
				case GEARMAN_WORK_FAIL:
				//	log_message('WARN', sprintf("Gearman Library:  $function Failed %s:%d uuid:%s",
				//	__FILE__, __LINE__, $uuid));
					remote_alarm(sprintf('Gearman worker:%s Failed uuid:%s', $function, $uuid));
					throw new Exception(sprintf("$uuid Gearman Library:  $function Failed. request:%s, header:%s", 
								json_encode($param),json_encode($header)), $this->CI->config->item('gearman_work_fail', 'err_no')
							);
				case GEARMAN_SUCCESS:
					$this->CI->benchmark->mark($function.'_end');
					break;
				case GEARMAN_WORK_DATA:
				case GEARMAN_WORK_STATUS:
					break; 
				default:
			//		log_message('WARN', "Gearman Library: $uuid $function Failed RET:".$this->client->returnCode());
					remote_alarm(sprintf('Gearman worker:%s Failed RET:%d uuid:%s', $function,
					$this->client->returnCode(), $uuid));
					throw new Exception(sprintf("$uuid Gearman Library:  $function Failed RET: %s %s %s", 
								$this->client->returnCode(), $this->error(), var_export($param, TRUE)),
							$this->CI->config->item('do_job_foreground_err', 'err_no')
							);
			}
			$tm_current = gettimeofday();
			$intUSGone = ($tm_current['sec'] - $tm_start['sec']) * 1000000
				+ ($tm_current['usec'] - $tm_start['usec']);
			if ($intUSGone > $this->_read_timeout_us) {
				remote_alarm(sprintf('Gearman worker:%s Timeout uuid:%s', $function, $uuid));
				throw new Exception(sprintf("$uuid Gearman Library:  $function timeout: %u", $intUSGone),
						$this->CI->config->item('do_job_foreground_timeout', 'err_no')
						);
			}
		}while($this->client->returnCode() != GEARMAN_SUCCESS);

		$this->CI->log->push_info('(c:%s) (m:%s) (w:%s) (cost:%ss,res:ok)', array('c'=>$param['c'], 'm'=>$param['m'], 'w'=>$function,
					'cost'=>$this->CI->benchmark->elapsed_time($function.'_start', $function. '_end'))
				);
		if ($unpack_func =='json_decode') {
			return json_decode($result, TRUE);
		}else{
			return $unpack_func($result);
		}
	}
    public function do_job_foreground_no_header($function, $param, $pack_func='msgpack_pack', $unpack_func='msgpack_unpack') 
	{
		$this->_auto_connect($function);
		if ($this->CI->log->getEffectiveLevel() =='DEBUG'){
			log_message('debug', "Gearman Library: Performed task with function $function with parameter". var_export($param, TRUE));
		}
		$tm_start    = gettimeofday();
		$this->CI->benchmark->mark($function.'_start');
		do {
			$result = $this->client->doNormal($function, $pack_func($param));
			switch($this->client->returnCode()) {
				case GEARMAN_WORK_FAIL:
					log_message('WARN', sprintf("Gearman Library:  $function Failed %s:%d", __FILE__, __LINE__));
					throw new Exception(sprintf("Gearman Library:  $function Failed. request:%s", 
								json_encode($param)), $this->CI->config->item('gearman_work_fail', 'err_no')
							);
				case GEARMAN_SUCCESS:
					$this->CI->benchmark->mark($function.'_end');
					break;
				case GEARMAN_WORK_DATA:
				case GEARMAN_WORK_STATUS:
					break; 
				default:
					log_message('WARN', "Gearman Library:  $function Failed RET:".$this->client->returnCode());
					throw new Exception(sprintf("Gearman Library:  $function Failed RET: %s %s %s", 
								$this->client->returnCode(), $this->error(), var_export($param, TRUE)),
							$this->CI->config->item('do_job_foreground_err', 'err_no')
							);
			}
			$tm_current = gettimeofday();
			$intUSGone = ($tm_current['sec'] - $tm_start['sec']) * 1000000
				+ ($tm_current['usec'] - $tm_start['usec']);
			if ($intUSGone > $this->_read_timeout_us) {
				throw new Exception(sprintf("Gearman Library:  $function timeout: %u", $intUSGone),
						$this->CI->config->item('do_job_foreground_timeout', 'err_no')
						);
			}
		}while($this->client->returnCode() != GEARMAN_SUCCESS);

		$this->CI->log->push_info('(c:%s) (m:%s) (w:%s) (cost:%ss,res:ok)', array('c'=>'', 'm'=>'', 'w'=>$function,
					'cost'=>$this->CI->benchmark->elapsed_time($function.'_start', $function. '_end'))
				);
		if ($unpack_func =='json_decode') {
			return json_decode($result, TRUE);
		}else{
			return $unpack_func($result);
		}
	}

    /**
     * Runs through all of the servers defined in the configuration and attempts to connect to each
     * @param object
     * @return void
     */
    private function _auto_connect($server_key='')
	{
		if (!file_exists(GM_CONF)) {
			if ($this->_server_key == 'default'){
				$servers = implode(',', $this->CI->config->item('host', 'mgr_config'));
			}else{
				$servers = implode(',', $this->CI->config->item($this->_server_key, 'mgr_config'));
			}
		}else{
			clearstatcache(TRUE, GM_CONF);
			$last_modified_time = filemtime(GM_CONF);
			if ($this->CI->gm_last_modified_time ==0 || $this->CI->gm_last_modified_time < $last_modified_time){
				$this->CI->gm_last_modified_time = $last_modified_time;
				$this->_load_gm_config();
			}
			$servers = implode(',', $this->CI->gm_servers[$server_key]['host']);
		}

		if(!$this->client->addServers($servers))
		{
			$this->errors[] = "Gearman Library: Could not connect to the server named $servers";
			log_message('error', 'Gearman Library: Could not connect to the server named "'.$servers.'"');
		}
		else
		{
			log_message('debug', 'Gearman Library: Successfully connected to the server named "'.$servers.'"');
		}
	}
	protected function _load_gm_config(){
		$this->CI->gm_servers = json_decode(file_get_contents(GM_CONF), TRUE);
		$locate_conf = realpath(dirname(__FILE__).'/../../../') . '/gm.conf';
		if (file_exists($locate_conf)) {
			$locate_config_text = json_decode(file_get_contents($locate_conf), TRUE);
			if ($locate_config_text){
				$this->CI->gm_servers = array_merge($this->CI->gm_servers, $locate_config_text);
			}
		}
	}
    /**
     *  Returns worker error
     *  @access public
     *  @return void
     *         
     */
    function error()
    {
        return empty($this->errors) ? $this->client->error() : implode(';',$this->errors);
    }

}
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
