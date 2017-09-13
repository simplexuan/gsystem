<?php
class Logic_title extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_title'), $func), $args);
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
			$selected  = $selected + array('title'=>array())
				;
		}

		$c['title'] = $this->model('Model_title')->fetch_one_by_id($id, $selected['title']);
		if (empty($c['title']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));
		}
		else
		{
			//获取别名
			$alias_data = $this->model('Model_title_alias')->search_all(array('title_id'=>$id));
			if(!empty($alias_data))
			{
				$c['title']['alias'] = array_values($alias_data);
			}
		}


		return $c;
	}
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$titles = $this->model('Model_title')->search($param, $param['page'], $param['pagesize']);
		$titles['results'] = array_values($titles['results']);
		return $titles;
	}
	/**
	 * 实现ToB的ToBusiness_Title_load
	 */
	function load($param) {
		if(empty($param['tid']) || !is_numeric($param['tid'])) {
			return false;
		}
		try {
			$return = $this->model('Model_title')->fetch_one_by_id($param['tid']);
			$return['tid']		= $return['id'];
			$return['title']	= $return['name'];
			$return['bundle']	= $return['category_id'];
			$return['created']	= strtotime($return['created_at']);
			$return['updated']	= strtotime($return['updated_at']);

			$alias_list = $this->model('Model_title_alias')->search_all(array('title_id'=>$param['tid']));
			$return['field_title_alias'] = array();
			foreach($alias_list as $alias) {
				$return['field_title_alias'][]['value'] = $alias['alias'];
			}

			return (object)$return;
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 实现ToB的ToBusiness_Title_loadByName
	 */
	function loadByName($param) {
		if(empty($param['title'])) {
			return false;
		}

		$return = $this->model('Model_title')->search_one(array('name'=>$param['title']));
		if(!empty($return)) {
			$return['tid']		= $return['id'];
			$return['title']	= $return['name'];
			$return['bundle']	= $return['category_id'];
			$return['created']	= strtotime($return['created_at']);
			$return['updated']	= strtotime($return['updated_at']);

			$alias_list = $this->model('Model_title_alias')->search_all(array('title_id'=>$return['id']));
			$return['field_title_alias'] = array();
			foreach($alias_list as $alias) {
				$return['field_title_alias'][]['value'] = $alias['alias'];
			}

			return (object)$return;
		} else {
			return false;
		}
	}
	/**
	 * 实现ToB的ToBusiness_Title_loadByAlias
	 */
	function loadByAlias($param) {
		$return = array();
		if(empty($param['alias'])) {
			return $return;
		}

		$list = $this->model('Model_title_alias')->search_all(array('alias'=>$param['alias']));
		foreach ($list as $one) {
			$return[$one['title_id']] = $this->load(array('tid'=>$one['title_id']));
		}

		return $return;
	}

	/**
	 * 根据条件新增或更新数据
	 * logic:
	 *	1.根据id判断新增/更新
	 *	2.判断参数中是否存在alias,存在则更新/新增别名
	 * 别名逻辑:
	 *  1.存在id,更新别名
	 *		1.1 比对新旧别名;交集(不需要更新的别名;新旧数据中删除);差集(需要更新的别名)
	 *		1.2 数据库删除旧别名中的差集数据
	 *		1.3 若新别名中还有数据.则新增这部分别名
	 *  2.不存在id,新增别名
	 * @param type $param
	 */
	public function save($param)
	{
		$table_name = 'title';
		//初始化
		$param[$table_name]['updated_at'] = date('Y-m-d H:i:s');
		if(empty($param[$table_name]['is_deleted']))
			$param[$table_name]['is_deleted'] = 'N';
		//获取参数
		$id = isset($param[$table_name]['id']) ? intval($param[$table_name]['id']) : 0;
		$alias = isset($param[$table_name]['alias']) && is_array($param[$table_name]['alias']) ? $param[$table_name]['alias'] : null;
		unset($param[$table_name]['alias']);
		//初始化新增职级参数
		if($id <= 0)
		{
			$init_data = $this->model('Model_title')->new_c();
			$param[$table_name] = array_merge($init_data[$table_name],$param[$table_name]);
		}
		//新增/更新职级
		$title_id = $this->model('Model_title')->save($param);
		//新增/更新职级别名
		if(!empty($alias))
		{
			$table_name_alias = 'title_alias';
			if($id > 0 )
			{
				$old_alias = $this->model('model_title_alias')->search_all(array('title_id'=>$id));
				if(!empty($old_alias) && is_array($old_alias))
				{
					//获取需要删除的别名
					foreach($old_alias as $old=>$old_one)
					{
						foreach($alias as $new=>$new_one)
						{
							if ($new_one == $old_one['alias'])
							{
								unset($alias[$new], $old_alias[$old]);
							}
						}
					}
					//del
					if(!empty($old_alias))
					{
						foreach ($old_alias as $one)
						{
							$this->model("Model_title_alias")->delete_one_by_id($one['id']);
						}
					}
				}
			}
			if(!empty($alias))
			{
				$alias_data = array();
				//新增别名
				$alias_data[$table_name_alias]['title_id'] = $title_id;
				$alias_data[$table_name_alias]['updated_at'] = date('Y-m-d H:i:s');
				foreach ($alias as $one)
				{
					$alias_data[$table_name_alias]['alias'] = $one;
					$alias_data[$table_name_alias]['sort'] = 0;
					$alias_data[$table_name_alias]['is_deleted'] = "N";
					$this->model('model_title_alias')->save($alias_data);
				}
			}
		}


	}

	/**
	 * 根据id删除数据
	 * @param type $param
	 * @return boolean
	 */
	public function delete($param)
	{
		$id = isset($param['id']) ? intval($param['id']) : 0 ;
		if(empty($id))
			throw new Exception ('参数不正确', 231300);
		$this->model('model_title')->delete_one_by_id($id);
		$alias = $this->model('model_title_alias')->search_all(array('title_id'=>$id));
		if(!empty($alias))
		{
			foreach($alias as $one)
			{
				$del_id = $one['id'];
				$this->model('model_title_alias')->delete_one_by_id($del_id);
			}
		}
		return true;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
