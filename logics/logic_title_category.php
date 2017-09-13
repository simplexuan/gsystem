<?php
class Logic_title_category extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_title_category'), $func), $args);
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
			$selected  = $selected + array('title_category'=>array())
				;
		}

		$c['title_category'] = $this->model('Model_title_category')->fetch_one_by_id($id, $selected['title_category']);
		if (empty($c['title_category']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));
		}


		return $c;
	}
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$title_categorys = $this->model('Model_title_category')->search($param, $param['page'], $param['pagesize']);
		$title_categorys['results'] = array_values($title_categorys['results']);
		return $title_categorys;
	}

	/**
	 * 根据条件新增或更新数据
	 * @param type $param
	 */
	public function save($param)
	{
		return true;
//		$table_name = 'title_category';
//		//初始化
//		$param[$table_name]['updated_at'] = date('Y-m-d H:i:s');
//		if(empty($param[$table_name]['is_deleted']))
//			$param[$table_name]['is_deleted'] = 'N';
//		//获取参数
//		$id = isset($param[$table_name]['id']) ? intval($param[$table_name]['id']) : 0;
//
//		//新增
//		if($id <= 0)
//		{
//			$init_data = $this->model('Model_title_category')->new_c();
//			$param[$table_name] = array_merge($init_data[$table_name],$param[$table_name]);
//		}
//
//		return $this->model('Model_title_category')->save($param);
	}

	/**
	 * 根据id删除数据
	 * @param type $param
	 * @return boolean
	 * @throws Exception
	 */
	public function delete($param)
	{
//		$id = isset($param['id']) ? intval($param['id']) : 0 ;
//
//		if(empty($id))
//			throw new Exception ('缺少参数', 2313000);
//
//		$this->model('model_title_category')->delete_one_by_id($id);

		return true;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
