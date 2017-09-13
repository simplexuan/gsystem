<?php 
class Logic_bdlist_alarm extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_bdlist_alarm'), $func), $args);
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
			$selected  = $selected + array('bdlist_alarm'=>array())
				;
		}

		$c['bdlist_alarm'] = $this->model('Model_bdlist_alarm')->fetch_one_by_id($id, $selected['bdlist_alarm']);
		if (empty($c['bdlist_alarm']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
		} 


		return $c; 
	}
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$bdlist_alarms = $this->model('Model_bdlist_alarm')->search($param, $param['page'], $param['pagesize']);
		$bdlist_alarms['results'] = array_values($bdlist_alarms['results']);
		return $bdlist_alarms;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
