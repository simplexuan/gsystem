<?php
class Logic_corporation_baseinfo_dictionary extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_corporation_baseinfo_mapping'), $func), $args);
	}

	/*
	 * 获取字典列表
	 */
	public function get_list($param){
		$type_id = !empty($param['type_id'])? intval($param['type_id']): 0;
		try{
			return $this->model('Model_corporation_baseinfo_dictionary')->get_list($type_id);
		}catch (Exception $e){
			throw $e;
		}
	}
}
