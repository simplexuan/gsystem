<?php 
class Logic_corporation_baike extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_corporation_baike'), $func), $args);
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
			$selected  = $selected + array('corporation_baike'=>array())
				;
		}

		$c['corporation_baike'] = $this->model('Model_corporation_baike')->fetch_one_by_id($id, $selected['corporation_baike']);
		if (empty($c['corporation_baike']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
		} 


		return $c; 
	}
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$corporation_baikes = $this->model('Model_corporation_baike')->search($param, $param['page'], $param['pagesize']);
		$corporation_baikes['results'] = array_values($corporation_baikes['results']);
		return $corporation_baikes;
	}
	/**
	 * 实现ToB的ToBusiness_Baike_load
	 */
	function load($param) {
		if(empty($param['id']) || !is_numeric($param['id'])) {
			throw new Exception('no company', 10001);
		}
		try {
			//$this->model('Model_corporation')->fetch_one_by_id($param['id']);
			$rs = $this->model('Model_corporation_baike')->search_all(array('corporation_id'=>$param['id']));
			return $this->model('Model_corporation_baike')->format_data($rs);
		} catch (Exception $e) {
			throw new Exception('no company', 10001);
		}
	}
	/**
	 * 实现ToB的ToBusiness_Baike_loadByName
	 */
	function loadByName($param) {
		if(empty($param['name'])) {
			throw new Exception('no company', 10001);
		}
		$rs = $this->model('Model_corporation')->search_one(array('name'=>$param['name']));
		if(empty($rs)) {
			throw new Exception('no company', 10001);
		} else {
			return $this->load($rs['id']);
		}
	}
	/**
	 * 实现ToB的ToBusiness_Baike_hasDetail
	 */
	function hasDetail($param) {
		$ids = array();
		$is_one = true;
		$return = array();
		if(empty($param['id']) || !is_numeric($param['id'])) {
			if(!empty($param['ids'])) {
				$ids = is_array($param['ids']) ? $param['ids'] : explode(',', $param['ids']);
				$is_one = false;
			} else {
				return false;
			}
		} else {
			$ids = array($param['id']);
		}

		foreach($ids as $id) {
			$rs = $this->model('Model_corporation_baike')->search_one(array('corporation_id'=>$id));
			if($rs && $rs['status'] == 1) {
				$return[$id] = intval($rs['mid'] == 1 || $rs['mid'] == 2);	
			}
		}

		return $is_one ? reset($return) : $return;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
