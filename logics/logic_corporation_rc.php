<?php 
class Logic_corporation_rc extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_corporation_rc'), $func), $args);
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
			$selected  = $selected + array('corporation_rc'=>array())
				;
		}

		$c['corporation_rc'] = $this->model('Model_corporation_rc')->fetch_one_by_id($id, $selected['corporation_rc']);
		if (empty($c['corporation_rc']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
		} 


		return $c; 
	}
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$corporation_rcs = $this->model('Model_corporation_rc')->search($param, $param['page'], $param['pagesize']);
		$corporation_rcs['results'] = array_values($corporation_rcs['results']);
		return $corporation_rcs;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
