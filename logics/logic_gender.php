<?php 
class Logic_gender extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_gender'), $func), $args);
	}
	/**
	 * detail 
	 * 
	 * @param mixed $param 
	 * @access public
	 * @return mixed
	 */
	function detail($param) {
		$c         = array();
		$id        = $param['id'];
		$selected  = isset($param['selected']) ? parse_selected($param['selected']) : array();
		if (!$selected){
			$selected  = $selected + array('gender'=>array())
				;
		}

		$c['gender'] = $this->model('Model_gender')->fetch_one_by_id($id, $selected['gender']);
		if (empty($c['gender']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
		} 


		return $c; 
	}
	/**
	 * search
	 *
	 * @param array $param 参数列表
	 * @return array
	 */
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$genders = $this->model('Model_gender')->search($param, $param['page'], $param['pagesize']);
		$genders['results'] = array_values($genders['results']);
		return $genders;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
