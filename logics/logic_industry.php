<?php
class Logic_industry extends Gsystem_logic {
	private $_table_name = 'industry';
	private $_table_alias_name = 'industry_alias';
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
		return call_user_func_array(array($this->model('Model_industry'), $func), $args);
	}
	/**
	 * detail
	 *
	 * @param mixed $id
	 * @access public
	 * @return mixed
	 */
	public function detail($param) {
		$c         = array();
		$id        = (int)$param['id'];
		$selected  = isset($param['selected']) ? parse_selected($param['selected']) : array();
		if (!$selected){
			$selected  = $selected + array('industry'=>array());
		}
		try{
			$c['industry'] = $this->model('Model_industry')->fetch_one_by_id($id, $selected['industry']);
		}catch(Exception $e){
			return $c;
		}

		if (!empty($param['alias'])){
			$alias = $this->model('Model_industry_alias')->search_all(array('industry_id' => $c['industry']['id']));
			foreach ($alias as $one){
				$alias_name[] = $one['alias'];
			}
			$c['industry']['alias'] = $alias_name;
		}

		return $c;
	}
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$industrys = $this->model('Model_industry')->search($param, $param['page'], $param['pagesize']);
		$industrys['results'] = array_values($industrys['results']);
		return $industrys;
	}
	/**
	 * 实现ToB的ToBusiness_Industry_load
	 */
	function load($param) {
		$return = false;
		if(empty($param['tid']) || !is_numeric($param['tid'])) {
			return $return;
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		try {
			return $this->form_data($this->model('Model_industry')->fetch_one_by_id($param['tid']), $select_field);
		} catch (Exception $e) {
			return $return;
		}
	}
	/**
	 * 实现ToB的ToBusiness_Industry_loadMulti
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
	 * 实现ToB的ToBusiness_Industry_loadByName
	 */
	function loadByName($param) {
		$return = false;
		if(empty($param['name'])) {
			return $return;
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		$rs = $this->model('Model_industry')->search(array('name'=>$param['name']), 1, 1000);
		foreach($rs['results'] as $one) {
			return $this->form_data($one, $select_field);
		}

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_Industry_loadByNames
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
				$return[$one['id']] = $rs;
			}
		}
		asort($return);

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_Industry_loadByAlias
	 */
	function loadByAlias($param) {
		$return = array();
		if(empty($param['alias'])) {
			return $return;
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		$alias_list = $this->model('Model_industry_alias')->search_all(array('alias'=>$param['alias']));
		foreach($alias_list as $one) {
			$return[$one['industry_id']] = $this->load(array('tid'=>$one['industry_id'], 'select_field'=>$select_field));
		}

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_Industry_loadByParent
	 */
	function loadByParent($param) {
		$return = array();
		if(!isset($param['parent']) || !is_numeric($param['parent'])) {
			return $return;
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		$list = $this->model('Model_industry')->search_all(array('parent_id'=>$param['parent']));
		foreach($list as $one) {
			$return[$one['id']] = $this->load(array('tid'=>$one['id'], 'select_field'=>$select_field));
		}
		asort($return);

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_Industry_loadTree
	 */
	function loadTree($param) {
		$return = array();
		static $industries;
		$pagesize = 1000;
		if(empty($industries)) {
			$industries = $this->model('Model_industry')->search_all(array());
			foreach($industries as $tid=>$val) {
				$industries[$tid] = $this->simple_data($val);
			}
			asort($industries);
		}

		$simple = empty($param['simple']) ? '' : $param['simple'];
		$tids	= empty($param['tids']) ? '' : $param['tids'];
		$demand	= empty($param['demand']) ? '' : $param['demand'];
		$depth	= empty($param['depth']) ? '' : $param['depth'];
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];

		if(is_numeric($depth)) {
			foreach($industries as $tid=>$v) {
				if($v['depth'] <= $depth) {
					$return[$tid] = $v;
				}
			}

			return $simple ? $return : $this->data_to_tree($return);
		}
		if(empty($tids)) {
			return $simple ? $industries : $this->data_to_tree($industries);
		}


		$allowedData = $this->loadMulti(array('tids'=>$tids, 'select_field'=>$select_field));
		//找出每个枝叶的最后层
		foreach ($allowedData as $tid => $v) {
			foreach ($allowedData as $ii => $vv) {
				for ($i = 1; $i < $vv->depth; $i++) {
					$p = 'p' . $i;
					if ($vv->{$p} == $v->tid) {
						unset($allowedData[$tid]);
					}
				}
			}
		}
		//只要给到的最后层
		if ($demand == 'self') {
			$allData = $allowedData;
			foreach ($allData as $tid => $v) {
				$allData[$tid] = (array) $v;
			}
			return $simple ? $allData : $this->data_to_tree($allData);
		}
		//找子父层
		$parents = $childs = array();
		foreach ($allowedData as $tid => $v) {
			for ($i = 1; $i < $v->depth; $i++) {
				$p = 'p' . $i;
				$parents[] = $v->{$p};
			}
			$subs = $this->get_tid_by_depth($v->depth, $v->tid);
			$childs = array_merge($childs, $subs);
		}
		$parents = array_filter(array_unique($parents));
		$childs  = array_unique($childs);
		//合并输出
		switch ($demand) {
			case 'parent':
				$allData = $this->loadMulti(array('tids'=>$parents, 'select_field'=>$select_field));
				break;
			case 'child':
				$allData = $this->loadMulti(array('tids'=>$childs, 'select_field'=>$select_field));
				break;
			default:
				$allData = $this->loadMulti(array('tids'=>array_merge($parents, $childs), 'select_field'=>$select_field));
		}
		$allData += $allowedData;
		foreach ($allData as $tid => $v) {
			$allData[$tid] = (array) $v;
		}
		return $simple ? $allData : $this->data_to_tree($allData);

	}
	//获取相应层次的tid
	function get_tid_by_depth($depth, $tid) {
		$return = array();
		$list = $this->model('Model_industry')->search_all(array('p'.$depth=>$tid));
		foreach($list as $key=>$val) {
			$return[] = $val['id'];
		}
		asort($return);

		return $return;

	}
	//瘦身
	function simple_data($data) {
		$new['tid']		= $data['id'];
		$new['parent']	= $data['parent_id'];
		$new['depth']	= $data['depth'];
		$new['name']	= $data['name'];
		$new['autosub']	= $data['autosub'];

		return $new;
	}
	//组装树结构
	function data_to_tree($data, $root = 0, $id = 'tid', $pid = 'parent', $child = 'child') {
		$tree  = array();
		$unset = array();
		foreach ($data as $tid => $v) {
			$parentId = $v[$pid];
			if ($root == $parentId) {
				$tree[] = &$data[$tid];
			} elseif (isset($data[$parentId])) {
				$data[$parentId][$child][] = &$data[$tid];
				$unset[] = $tid;
			}
		}
		if (!$tree) {
			foreach($unset as $tid) {
				unset($data[$tid]);
			}
			return $data;
		}
		return $tree;
	}
	//处理单条数据
	function form_data($data, $select_field = array()) {
		if(empty($data)) {
			return new stdClass;
		} else {
			$select_field = is_array($select_field) ? $select_field : explode(',',$select_field);

			$data['tid']	= $data['id'];
			$data['parent']	= $data['parent_id'];
			$data['created']= strtotime($data['created_at']);
			$data['updated']= strtotime($data['updated_at']);
			unset($data['id'], $data['parent_id'], $data['created_at'], $data['updated_at']);

			if(empty($select_field) || in_array('field_industry_alias', $select_field)) {
				$data['field_industry_alias']	= array();
				$alias_list = $this->model('Model_industry_alias')->search_all(array('industry_id' => $data['tid']));
				foreach($alias_list as $one) {
					$data['field_industry_alias'][]['value'] = $one['alias'];
				}
			}

			if(!empty($select_field)) {
				foreach($data as $k=>$v) {
					if(!in_array($k, $select_field)) {
						unset($data[$k]);
					}
				}
			}

			return (object)$data;
		}

	}

	/**
	 * 新增行业
	 * 行业一共5级
	 * 1.根据parent_id,计算depth,autosub,p1,p2,p3,p4
	 * 2.判断depth不能大于5
	 * 3.插入行业表和行业别名表
	 * @param type $param
	 */
	public function save($param)
	{
		$name = isset($param[$this->_table_name]['name']) ? strval($param[$this->_table_name]['name']) :null;
		if (empty($name)) {
			throw new Exception('行业名不能为空',85042003);
		}

		$id = isset($param[$this->_table_name]['id']) ? intval($param[$this->_table_name]['id']) : 0;
		if ($id <= 0) {
			//获取参数
			$parent_id = isset($param[$this->_table_name]['parent_id']) && !empty($param[$this->_table_name]['parent_id']) ? $param[$this->_table_name]['parent_id'] : 0;
			$alias = isset($param[$this->_table_name]['alias']) ? $param[$this->_table_name]['alias'] : NULL;
			unset($param[$this->_table_name]['alias']);
			if(!is_array($parent_id)) {
				$parent_id = array($parent_id);
			}

			//初始化
			$param[$this->_table_name]['depth'] = 1;
			$param[$this->_table_name]['updated_at'] = date('Y-m-d H:i:s');
			$param[$this->_table_name]['is_deleted'] = 'N';

			//遍历行业id，根据depth取出最深的那个id作为父id,然后拼接出行业层级id和请求进行对比。不一致则报错退出
			//去除无效数据
			$format_industry_ids = array ();
			foreach ($parent_id as $one) {
				if (!empty($one)) {
					$format_industry_ids[] = $one;
				}
			}
			$this->log->warn("===========》 format:".var_export($format_industry_ids,true));
			//非顶级分类时,计算depth,p1-p4
			if (!empty($format_industry_ids)) {
				//遍历出拥有最深depth的id
				$all_depth_datas = $this->get_multi(array ('ids' => $format_industry_ids));
				$max_depth_id = 0;
				$last_parent_id = 0;
				foreach ($all_depth_datas as $one) {
					if ($one['depth'] > $max_depth_id) {
						$max_depth_id = $one['depth'];
						$last_parent_id = $one['id'];
					}
				}
				//parent_id赋值
				$param[$this->_table_name]['parent_id'] = $last_parent_id;
//				$this->log->warn("===========》max_depth:".var_export($last_parent_id,true));
				//计算出行业层级
				$calculate_depth = $this->_get_depth_pn($last_parent_id)[$this->_table_name];
				//比对
				$flag = TRUE;
				//对比计算的层级数和用户输入的层级数是否一致
				$input_depth_count = count($format_industry_ids);
				$calculate_depth_count = count($calculate_depth) - 1;
				if($input_depth_count == $calculate_depth_count)
				{
					//判断用户输入的行业id是否正确
					foreach ($format_industry_ids as $one_id) {
						if (!in_array($one_id, array_values($calculate_depth))) {
							$flag = FALSE;
							break;
						}
					}
				} else {
					$flag = FALSE;
				}

				if ($flag === FALSE) {
					throw new Exception('行业选择错误', 85042004);
				}

				$param[$this->_table_name] = array_merge($param[$this->_table_name], $this->_get_depth_pn($last_parent_id)[$this->_table_name]);

//				$this->log->warn("===========》params:".var_export($param,true));
			} else {
				$param[$this->_table_name]['parent_id'] = 0;
			}

			//插入行业表
			$init_data = $this->model('model_industry')->new_c();
			$param[$this->_table_name] = array_merge($init_data[$this->_table_name],$param[$this->_table_name]);
			$industry_id = $this->model('model_industry')->save($param);
			//插入别名表
			if(isset($alias) && is_array($alias))
			{
				foreach($alias as $one)
				{
					$save_alias_data = array(
						$this->_table_alias_name=>array(
							'industry_id'=>$industry_id,
							'alias'=>$one,
							'is_deleted'=>'N',
							'updated_at'=>date('Y-m-d H:i:s'),
							'sort' =>0,
						)
					);
					$this->model('model_industry_alias')->save($save_alias_data);
				}
			}
			if(empty($industry_id))
			{
				throw new Exception('添加行业分类失败', 400004);
			}
			return $industry_id;
		}
		else
		{
			return $this->save_update($param);
		}

	}

	/**
	 * 根据parent_id计算出相应的depth,p1-p4
	 * @param type $parent_id
	 * @return array
	 *			array(
	 *				'industry'=>array(
	 *					'depth'=>1,
	 *					'p1'=>123,
	 *				)
	 *			)
	 * @throws Exception
	 */
	private function _get_depth_pn($parent_id)
	{
		$param = array();
		//获取pid的数据
		$parent_data = $this->detail(array('id'=>$parent_id));
		//计算depth
		$param[$this->_table_name]['depth'] = $parent_data[$this->_table_name]['depth']+1;
		if($param[$this->_table_name]['depth'] >5) throw new Exception ('超过5级', 2313001);
		//计算p1-p4
		$pn = $param[$this->_table_name]['depth']-1;//pid对应的pn值
		$pn > 0 && $param[$this->_table_name]['p'.$pn] = $parent_id;
		if($pn>1)
		{
			for($i=1;$i<$pn;$i++)
			{
				$param[$this->_table_name]['p'.$i] = $parent_data[$this->_table_name]['p'.$i];
			}
		}

		return $param;
	}

	/**
	 * 更新数据
	 * 不能修改分类id
	 * @param type $param
	 */
	public function save_update($param)
	{
		//初始化
		$id = isset($param[$this->_table_name]['id']) ? intval($param[$this->_table_name]['id']) : 0;
		if(empty($id))
			throw new Exception ('id不能为空', 2313000);
		$alias = isset($param[$this->_table_name]['alias']) ? $param[$this->_table_name]['alias'] : null;
		unset($param[$this->_table_name]['alias']);
		$param[$this->_table_name]['updated_at'] = date('Y-m-d H:i:s');
//		//获取原始数据
//		$old_data = $this->model('model_industry')->new_c($id);
//		//获取原始分类
//		$old_parent_id = $old_data[$this->_table_name]['parent_id'];
//		$new_parent_id = $param[$this->_table_name]['parent_id'];
//		//比对更新数据.若修改了分类则更新相关数据,否则直接更新数据
//		if($old_parent_id != $new_parent_id)
//		{
//			throw new Exception('不能修改分类id',2313001);
//		}
//		else
//		{
//			$this->model('model_industry')->save($param);
//		}
		$this->model('model_industry')->save($param);
		//比对别名.修改了别名则更新相关数据
		$old_alias = $this->model('model_industry_alias')->search_all(array('industry_id'=>$id));

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
					$this->model("Model_industry_alias")->delete_one_by_id($one['id']);
				}
			}
			//insert
			if(!empty($alias))
			{
				$alias_data = array();
				//新增别名
				$alias_data[$this->_table_alias_name]['industry_id'] = $id;
				$alias_data[$this->_table_alias_name]['updated_at'] = date('Y-m-d H:i:s');
				foreach ($alias as $one)
				{
					$alias_data[$this->_table_alias_name]['alias'] = $one;
					$alias_data[$this->_table_alias_name]['sort'] = 0;
					$alias_data[$this->_table_alias_name]['is_deleted'] = "N";
					$this->model('model_industry_alias')->save($alias_data);
				}
			}
		} else {
			//请求别名为空，若没有旧数据则新增，否则需要删除数据库旧数据
			if(empty($old_alias)) {
				//insert
				$alias_data[$this->_table_alias_name]['industry_id'] = $id;
				$alias_data[$this->_table_alias_name]['updated_at'] = date('Y-m-d H:i:s');
				foreach ($alias as $one)
				{
					$alias_data[$this->_table_alias_name]['alias'] = $one;
					$alias_data[$this->_table_alias_name]['sort'] = 0;
					$alias_data[$this->_table_alias_name]['is_deleted'] = "N";
					$this->model('model_industry_alias')->save($alias_data);
				}
			} else {
				//del
				foreach ($old_alias as $one)
				{
					$this->model("Model_industry_alias")->delete_one_by_id($one['id']);
				}
			}

		}

		return $id;
	}

	/**
	 * 根据id删除数据
	 * @param type $param
	 */
	public function delete($param)
	{
		$id = isset($param['id']) ? intval($param['id']) : 0;
		if(empty($id))
			throw new Exception ('id不能为空', 2313000);

		//查询此id下是否有数据.有则不能删除
		$sub_data = $this->model('model_industry')->search_all(array('parent_id'=>$id));
		if(!empty($sub_data))
		{
			throw new Exception('不能删除中间节点', 400003);
		}
		//删除主表数据
		$this->model('model_industry')->delete_one_by_id($id);
		//删除别名数据
		$alias_data = $this->model('model_industry_alias')->search_all(array('industry_id'=>$id));
		if(!empty($alias_data))
		{
			foreach($alias_data as $one)
			{
				$this->model('model_industry_alias')->delete_one_by_id($one['id']);
			}
		}
		return true;
	}

	/**
	 * 根据传进来的行业id获取该行业的二级父id
	 */
	function get_p2 ($params) {
		$return = array();
		if(!empty($params['ids'])) {
			$ids = is_array($params['ids']) ? $params['ids'] : explode(',', $params['ids']);
			$rs = $this->model('Model_industry')->get_multi($ids);
			foreach($rs as $one) {
				if($one['is_deleted'] == 'Y') continue;

				if($one['depth'] == 2) {
					$return[] = $one['id'];
				} elseif ($one['p2'] > 0) {
					$return[] = $one['p2'];
				}
			}
		}

		return array_unique($return);
	}

}
