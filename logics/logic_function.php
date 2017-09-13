<?php
class Logic_function extends Gsystem_logic {
	/**
	 * __construct
	 *
	 * @access protected
	 * @return mixed
	 */
	function __construct() {
		parent::__construct();
		$this->load->database();
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
		return call_user_func_array(array($this->model('Model_function'), $func), $args);
	}

	/*
	 * 获取详情
	 */
	function detail($param) {
		$c         = array();
		$id        = intval($param['id']);
		$selected  = isset($param['selected']) ? parse_selected($param['selected']) : array();
		if (!$selected){
			$selected  = $selected + array('function'=>array())
			;
		}

		// 兼容老表数据
		if($id < 1000000){
			$c['function'] = $this->model('Model_function')->get_function_by_id($id);
		}else{
			$c['function'] = $this->model('Model_function')->fetch_one_by_id($id, $selected['function']);
		}

		if (empty($c['function']) ){
			throw new Exception(sprintf('%s: The function id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));
		}
		return $c;
	}

	function search($param){
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$functions = $this->model('Model_function')->search($param, $param['page'], $param['pagesize']);
		$functions['results'] = array_values($functions['results']);
		return $functions;
	}

	function search_relation($param)
	{
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$functions = $this->model('Model_function')->search($param, $param['page'], $param['pagesize']);
		$functions['page'] = $param['page'];
		$functions['pagesize'] = $param['pagesize'];
		$search_response = $functions['results'] = array_values($functions['results']);

		foreach($search_response as $key=>$response_one)
		{
			$alias = [];
			$functions['results'][$key]['function_alias'] = (object)$alias;
		}

		$functions['industry_relation'] = [];
		$functions['parent_relation'] = [];

		return $functions;
	}
	/**
	 * 实现ToB的ToBusiness_Functions_load
	 */
	function load($param) {
		if(empty($param['tid']) || !is_numeric($param['tid'])) {
			return false;
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		try {
			return $this->form_data($this->model('Model_function')->fetch_one_by_id($param['tid']), $select_field);
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 实现ToB的ToBusiness_Functions_loadMulti
	 */
	function loadMulti($param) {
		$return = array();
		if(empty($param['tids'])) {
			return $return;
		}
		$tids = is_array($param['tids']) ? $param['tids'] : explode(',', $param['tids']);
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		foreach($tids as $tid) {
			$rs = $this->load(array('tid'=>$tid, 'select_field'=>$select_field));
			if($rs) {
				$return[$tid] = $rs;
			}
		}

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_Functions_loadByName
	 */
	function loadByName($param) {
		$return = false;
		if(empty($param['name'])) {
			return $return;
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		$rs = $this->model('Model_function')->search_one(array('name'=>$param['name']));
		if(empty($rs)) {
			return $return;
		} else {
			return $this->form_data($rs, $select_field);
		}
	}
	/**
	 * 实现ToB的ToBusiness_Functions_loadByNameLike
	 */
	function loadByNameLike($param) {
		$return = array();
		if(empty($param['name'])) {
			return $return;
		}
		$rs = $this->db->select('name')->from('functions_cluster')->like('name', $param['name'], 'after')->get()->result_array();
		foreach($rs as $one) {
			$return[] = $one['name'];
		}

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_Functions_loadByNames
	 */
	function loadByNames($param) {
		$return = array();
		if(empty($param['names'])) {
			return $return;
		}
		$names = is_array($param['names']) ? $param['names'] : explode(',', $param['names']);
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		foreach($names as $name) {
			$rs = $this->loadByName(array('name'=>$name, 'select_field'=>$select_field));
			if($rs) {
				$return[$name] = $rs;
			}
		}

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_Functions_loadByIndustry
	 */
	function loadByIndustry($param) {
		return [];
	}
	/**
	 * 实现ToB的ToBusiness_Functions_loadByParent
	 */
	function loadByParent($param) {
		$return = array();
		if(!isset($param['parent']) || !is_numeric($param['parent'])) {
			return $return;
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];

		return $this->loadCustom(array('parent'=>$param['parent'], 'select_field'=>$select_field));
	}
	/**
	 * 实现ToB的ToBusiness_Functions_loadCustom
	 */
	function loadCustom($param) {
		$cond = array();
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		$cond['is_deleted'] = 0;
		if(isset($param['parent']) && is_numeric($param['parent'])) {
			$cond['parent_id'] = $param['parent'];
		}

		$rs = $this->model('Model_function')->search_all($cond);

		return $this->form_multi_data($rs, $select_field);
	}
	/**
	 * 实现ToB的ToBusiness_Functions_loadUniversal
	 */
	function loadUniversal($param) {
		return [];
	}
	//格式化多条数据
	function form_multi_data($rs, $select_field = array()) {
		$return = array();

		foreach($rs as $key=>$one) {
			$one['tid']		= $one['function_id'];
			$one['industry']= 0;
			$one['parent']	= $one['parent_id'];
			$one['jd']		= '';
			$one['created']	= strtotime($one['created_at']);
			$one['updated']	= strtotime($one['updated_at']);
			unset($one['function_id'], $one['parent_id'], $one['created_at'], $one['updated_at']);

			$return[$key]		= (object)$one;
		}

		return $return;
	}
	//格式化每条数据
	function form_data($rs, $select_field = array()) {

		if(!empty($rs)) {
			$rs['tid']		= $rs['function_id'];
			$rs['industry']	= 0;
			$rs['parent']	= $rs['parent_id'];
			$rs['jd']		= '';
			$rs['created']	= strtotime($rs['created_at']);
			$rs['updated']	= strtotime($rs['updated_at']);
			unset($rs['parent_id'], $rs['created_at'], $rs['updated_at']);

			return (object)$rs;
		} else {
			return new stdClass;
		}
	}

	/*
	 * 为对原接口保留处理，避免意外风险，直接返回
	 */
	function save($param)
	{
		return true;
	}

	function delete_one($param)
	{
		$id = $param['function']['id'];
		$pid_response = $this->model('Model_function')->search_one(array('parent_id' => $id));
		if ($pid_response)
		{
			throw new Exception('', 403);
		}
		$this->model('Model_function')->delete_one_by_id($id);
		return true;
	}


	/*
	 * 操作职能
	 * 可以新增/更改 行业ID、父ID、职能名称、状态、别名
	 * return boolean
	 */
	public function operate_function($param)
	{
		return true;
	}

	/*
	 * 添加职能
	 */
	public function add($param){
		if(empty($param)) throw new Exception('参数不能为空', 100001);
		if(empty($param['name'])) throw new Exception('name不能为空', 100001);
		if(!isset($param['parent_id'])) throw new Exception('parent_id不符合', 100001);
		if(empty($param['depth'])) throw new Exception('depth不能为空', 100001);

		return $this->model('Model_function')->add($param);
	}


	/*
	 * 修改职能
	 */
	public function update($param){
		if(empty($param)) throw new Exception('参数不能为空', 100001);
		if(empty($param['function_id']) || intval($param['function_id']) == 0) throw new Exception('function_id不能为空', 100001);
		return $this->model('Model_function')->edit($param);
	}

	public function get_multi($param){
		if(empty($ids = $param['ids'])) return [];

		$selected  = isset($param['selected']) ? $param['selected'] : [];
		return  $this->model('Model_function')->getMulti($ids, $selected);
	}

	/*
	 * 根据职能名获取对应层级职能数据
	 */
	public function getFunctionsByNameDepth($param){
		if(empty($param['name'])) throw new Exception('name不能为空', 100001);
		if(!in_array($param['depth'], [1,2,3,4])) throw new Exception('depth不符合规则', 100001);
		if(!empty($param['return_depth']) && !in_array($param['return_depth'], [1,2,3,4])) throw new Exception('return_depth不符合规则', 100001);

		return $this->model('Model_function')->getFunctionsByNameDepth($param['name'], (int)$param['depth'], (int)$param['return_depth']);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
