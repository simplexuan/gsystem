<?php 
class Logic_major extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_major'), $func), $args);
	}

	/*
	 * 详情
	 */
	function detail($param) {
		if(empty($param['id'])) throw new Exception('ID不能为空', 10001);

		return $this->model('Model_major')->detail(intval($param['id']));
	}

	/*
	 * 根据名称模糊搜索学校
	 */
	function search_by_likename($param) {
		if(empty($param['name'])) throw new Exception('名称不能为空', 10001);

		return $this->model('Model_major')->searchByLikeName($param['name']);
	}

	/*
     * 添加专业
     */
	public function add($param){
		if(empty($param)) throw new Exception('参数不能为空', 100001);

		return $this->model('Model_major')->add($param);
	}

	/*
     * 修改专业
     */
	public function update($param){
		if(empty($param)) throw new Exception('参数不能为空', 100001);

		return $this->model('Model_major')->update($param);
	}

	/*
     * 删除专业
     */
	public function delete($param){
		if(empty($param)) throw new Exception('参数不能为空', 100001);
		if(empty($param['id'])) throw new Exception('ID不能为空', 100001);

		return $this->model('Model_major')->delete(intval($param['id']));
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
