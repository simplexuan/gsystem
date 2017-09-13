<?php
class Logic_corporation_baseinfo_mapping extends Gsystem_logic {
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
	 * 操作公司基础信息映射表数据
	 * 1 融资，2 规模，3 性质
	 */
	public function operate_baseinfos_mapping($param){
		if(empty($param)) throw new Exception('参数不能为空', 100001);

		if(empty($param['corporation_id']) || !is_numeric($param['corporation_id'])) throw new Exception("公司ID不合法", 100001);

		if(empty($param['financing']) && empty($param['scale']) && empty($param['nature'])) throw new Exception("请完善数据后重新提交", 100001);

		try{
			return $this->model('Model_corporation_baseinfo_mapping')->operate_baseinfos_mapping($param);
		}catch (Exception $e){
			throw $e;
		}
	}

	/*
	 * 获取公司基础信息
	 */
	public function get_baseinfos($param){
		if(empty($param['corporation_id'])) throw new Exception('公司ID不能为空', 100001);
		$corporation_id = intval($param['corporation_id']);
		$type_id = !empty($param['type_id'])? intval($param['type_id']): 0;
		try{
			return $this->model('Model_corporation_baseinfo_mapping')->get_baseinfos($corporation_id, $type_id);
		}catch (Exception $e){
			throw $e;
		}
	}
}
