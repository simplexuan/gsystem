<?php 
class Logic_corporation_baseinfo extends Gsystem_logic {
	/**
	 * __construct 
	 * 
	 * @access protected
	 * @return mixed
	 */
	function __construct() {
		parent::__construct();
	}
	/**
	 * __call 
	 * 
	 * @param mixed $func 
	 * @param mixed $args 
	 * @access protected
	 * @return mixed
	 */
	function __call($func, $args) {
		return call_user_func_array(array($this->model('Model_corporation_baseinfo'), $func), $args);
	}
	/**
	 * detail 
	 * 
	 * @param mixed $id 
	 * @access public
	 * @return mixed
	 */
	function detail($param) {
		$c         = array();
		$id        = $param['id'];
		$selected  = isset($param['selected']) ? parse_selected($param['selected']) : array();
		if (!$selected){
			$selected  = $selected + array('corporation_baseinfo'=>array())
				;
		}

		$c['corporation_baseinfo'] = $this->model('Model_corporation_baseinfo')->fetch_one_by_id($id, $selected['corporation_baseinfo']);
		if (empty($c['corporation_baseinfo']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
		} 


		return $c; 
	}

	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$corporation_baseinfos = $this->model('Model_corporation_baseinfo')->search($param, $param['page'], $param['pagesize']);
		$corporation_baseinfos['results'] = array_values($corporation_baseinfos['results']);
		return $corporation_baseinfos;
	}

	/*
     * 查询公司扩展信息  支持单、多条查询
     */
	public function get_baseinfos_extend($param){
		if(empty($param)) throw new Exception('参数不能为空', 100001);
		try{
			return $this->model('Model_corporation_baseinfo')->get_baseinfos_extend($param);
		}catch (Exception $e){
			throw $e;
		}
	}

	/*
     * 添加公司扩展信息
     */
	public function add_baseinfos_extend($param){
		if(empty($param)) throw new Exception('参数不能为空', 100001);
		try{
			return $this->model('Model_corporation_baseinfo')->add_baseinfos_extend($param);
		}catch (Exception $e){
			throw $e;
		}
	}
}
