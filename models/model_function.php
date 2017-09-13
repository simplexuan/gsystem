<?php
/**
 * Model_function
 *
 * @uses Gsystem
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com>
 * @license
 */
class Model_function extends Gsystem_model {
	protected $depth = [1,2,3,4];

	/**
	 * _model
	 *
	 * @var string
	 * @access protected
	 */
	protected $_model    = '';

	/**
	 * _equal_search_items
	 *
	 * @var string
	 * @access protected
	 */
	protected $_equal_search_items = array('name'=>'t','industry_id'=>'t','parent_id'=>'t','status'=>'t',);
	/**
	 * __construct
	 *
	 * @access protected
	 * @return mixed
	 */
	function __construct() {
		parent :: __construct();
		$this->_model = substr(__CLASS__, 6);
		$this->load->model('model_record_log');
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
		$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		return call_user_func_array(array($this->dao('/Dao_function',  $dao_param), $func), $args);
	}

	/**
	 * new_c
	 *
	 * @param int $id
	 * @access public
	 * @return mixed
	 */
	function new_c($id = 0) {
		$dao_param      = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		$new_function  = array();
		if ($id > 0) {
			$new_function['function'] = $this->dao('/Dao_function', $dao_param)->fetch_one_by_id($id);
		} else {
			$new_function['function']  = $this->dao('/Dao_function', $dao_param)->new_one();
		}
		return $new_function;
	}

	/**
	 * search
	 *
	 * @param array $param
	 * @param int $page
	 * @param int $pagesize
	 * @access public
	 * @return mixed
	 */
	function search($param = array(), $page = 0, $pagesize = 0) {
		$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		$functions = array('num'=>0, 'results'=>array());
		if ($page > 0 && $pagesize > 0) {
			$param['page']      = $page;
			$param['pagesize']  = $pagesize;
		}
		$selected = !empty($param['selected']) ? $param['selected'] : array();
		$param['is_deleted'] = 0;

		$key = $this->_get_cache_key($param);
		if(empty($param['ordersort'])) {
			$param['ordersort']     =  'function_id ASC';
		}

		if ($key === FALSE || ($page * $pagesize) > self :: ID_CACHE_NUM ){
			//    $param['ordersort']     =  'created_at DESC';
			$functions = $this->dao('/Dao_function', $dao_param )->search($param);
		}else{ //缓存
			$functions = $this->cache->memcached->get($key);
			if (empty($functions)) {
				$param['page']     = 1;
				$param['pagesize'] = self :: ID_CACHE_NUM;
				$functions = $this->dao('/Dao_function', $dao_param )->search($param);
				if (!$this->cache->memcached->save($key, $functions)){
					$this->log->push_info('del memcached key:%s fail', array($key));
				}
			}else{
				$this->log->push_info('(model:%s) (hit key:%s)', array($this->_model, $key));
			}

			if ($page > 0 && $pagesize > 0) {
				// array_slice 获取需要的数据
				$limit = $pagesize;
				if ($functions['num'] < $page* $pagesize){
					$limit = $functions['num'] - ($page-1) * $pagesize;
				}
				$functions['results'] = array_slice($functions['results'], ($page-1) * $pagesize, $limit, TRUE);
			}
		}

		if ( $functions['num'] > 0) {
			$items = $this->dao('/Dao_function', $dao_param)
				->get_multi(array_keys($functions['results']), $selected);
			foreach ($items as $item) {
				$functions['results'][$item['function_id']] = $item;
			}
		}

		return  $functions;
	}
	/**
	 * delete_one_by_id
	 *
	 * @param int $id
	 * @param int $user_id
	 * @access public
	 * @return mixed
	 */
	function delete_one_by_id($id=0, $user_id=0) {
		$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		$id = intval($id);
		if ($id <= 0) {
			throw new Exception(sprintf('function: %s, parameter: id must greater than 0', __FUNCTION__),
				$this->config->item('parameter_err_no', 'err_no'));

		}
		$function = $this->dao('/Dao_function', $dao_param)->fetch_one_by_id($id);
		if ($user_id >0 && $function['user_id'] != $user_id){
			throw new Exception(sprintf('function: %s, op:%d has no permission to delete user_id:%d', __FUNCTION__,
				$user_id, $function['user_id']),
				$this->config->item('permission_err_no', 'err_no'));
		}
		$this->dao('/Dao_function', $dao_param)->delete_one_by_id($id);
		$operation_type = Model_record_log::RECORD_LOG_TYPE_DELETE;
		$new_data = array();
		$this->model_record_log->save_operation_log($function, $new_data, $id, $this->_model, $operation_type);
		foreach($this->_mkeys as $k=>$items){
			$temp = array();
			foreach($items as $item){
				$temp[$item] = $function[$item];
			}
			$key = vsprintf($k, $temp);
			if (!$this->cache->memcached->del($key)){
				//  $this->log->warn(sprintf('%s: delete key:%s from memcached failure.', __FUNCTION__, $key));
				$this->log->push_info('del memcached key:%s fail', array($key));
			}
		}
		return TRUE;
	}
	/**
	 * update_by_id
	 *
	 * @param array $param
	 * @param int $id
	 * @access public
	 * @return mixed
	 */
	function update_by_id($param = array(), $id = 0) {
		$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		$id = intval($id);
		if ($id <= 0) {
			throw new Exception(sprintf('function: %s, parameter: id must greater than 0', __FUNCTION__),
				$this->config->item('parameter_err_no', 'err_no')
			);

		}
		$function = $this->dao('/Dao_function', $dao_param)->fetch_one_by_id($id);
		$this->dao('/Dao_function', $dao_param)->update($param, $id);
		//清除缓存
		foreach (array($function, array_merge($function, $param)) as $v){
			foreach ($this->_mkeys as $key_pattern => $keys){
				$temp = array();
				foreach($keys as $key){
					$temp[$key] = $v[$key];
				}
				$key  = vsprintf($key_pattern, $temp);
				if (!$this->cache->memcached->del($key)){
					//                    $this->log->warn(sprintf('%s: delete key:%s from memcached failure.', __FUNCTION__, $key));
					$this->log->push_info('del memcached key:%s fail', array($key));
				}
			}
		}
		return $id;
	}


	/**
	 * update_by_unique
	 *
	 * @param mixed $param
	 * @access public
	 * @return mixed
	 */
	function update_by_unique($param) {
		$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		unset($param['id']);
		$id  = $this->dao(sprintf('/Dao_%s', 'function'), $dao_param)->update_by_unique($param);
		if (intval($id)<=0){
			$param = array_merge($this->dao(sprintf('/Dao_%s', 'function'), $dao_param)->new_one(), $param);
			$this->dao(sprintf('/Dao_%s', 'function'), $dao_param)->insert($param);
		}else{
			$param['id'] = $id;

		}
		foreach ($this->_mkeys as $key_pattern => $keys){
			$temp = array();
			foreach($keys as $key){
				$temp[$key] = $param[$key];
			}
			$key  = vsprintf($key_pattern, $temp);
			if (!$this->cache->memcached->del($key)){
				//                $this->log->warn(sprintf('%s: delete key:%s from memcached failure.', __FUNCTION__, $key));
				$this->log->push_info('del memcached key:%s fail', array($key));
			}
		}
		return $id;
	}

	/*
	 * 操作职能
	 * 可以新增/更改 行业ID、父ID、职能名称、状态、别名
	 * return boolean
	 */
	public function operate_function($param){
		$id = isset($param['id']) ? intval($param['id']) : 0; // 所传职能ID，有则代表更新，无则代表新增

		$verify_where = '';
		if($id > 0) $verify_where = 'and id <> ' . $id;

		$verify_sql = "select count(1) total from functions where `is_deleted`  = 'N' and `industry_id` = {$param['industry_id']} and `name` = '{$param['name']}' {$verify_where}";
		$count = $this->parse_sql($verify_sql);

		if($count[0]['total'] > 0) throw new Exception('该行业职能名已存在，请核实', 188888);

		// 有id则更新，无则新增
		$function_data['function'] = [
			'industry_id' => $param['industry_id'],
			'parent_id' => $param['parent_id'],
			'name' => $param['name'],
			'status' => $param['status'],
			'uid' => isset($param['uid']) ? intval($param['uid']): 0,
			'updated_at' => date('Y-m-d H:i:s'),
		];

		if($id > 0){
			if(isset($param['category_id'])) $function_data['function']['category_id'] = intval($param['category_id']);
			if(isset($param['special_id'])) $function_data['function']['special_id'] = intval($param['special_id']);
			if(isset($param['weight'])) $function_data['function']['weight'] = intval($param['weight']);
			if(isset($param['flag'])) $function_data['function']['flag'] = intval($param['flag']);
			if(isset($param['jd_cn'])) $function_data['function']['jd_cn'] = $param['jd_cn'];
			if(isset($param['jd_en'])) $function_data['function']['jd_en'] = $param['jd_en'];
			if(isset($param['is_deleted'])) $function_data['function']['is_deleted'] = $param['is_deleted'];
			$function_detail = $this->fetch_one_by_id($id);
			if(empty($function_detail)) throw new Exception('该职能ID不存在', 100001);
			$function_data['function']['id'] = $id;
		}else{
			$function_data['function']['category_id'] = isset($param['category_id']) ? intval($param['category_id']): 0;
			$function_data['function']['special_id'] = isset($param['special_id']) ? intval($param['special_id']): 0;
			$function_data['function']['weight'] = isset($param['weight']) ? intval($param['weight']): 0;
			$function_data['function']['flag'] = isset($param['flag']) ? intval($param['flag']): 1;
			$function_data['function']['jd_cn'] = isset($param['jd_cn']) ? $param['jd_cn']: '';
			$function_data['function']['jd_en'] = isset($param['jd_en']) ? $param['jd_en']: '';
		}

		$alias_param['function_alias']['function_id'] = $this->save($function_data);
		if(!$alias_param['function_alias']['function_id']) return false;

		// 操作职能别名
		$alias = is_array($param['aliases']) ? $param['aliases'] : [];
		$this->load->model('model_function_alias');
		if ($id <= 0)
		{
			//新增别名
			foreach ($alias as $alias_one)
			{
				$alias_param['function_alias']['alias'] = $alias_one;
				$alias_param['function_alias']['is_deleted'] = 'N';
				$alias_param['function_alias']['sort'] = '0';
				$alias_param['function_alias']['updated_at'] = date('Y-m-d H:i:s');
				$this->model_function_alias->save($alias_param);
			}
		}
		else//更新别名
		{
			$functions_alias_response = $this->model_function_alias->search_all(['function_id' => $id]);

			//分析是新增还是删除别名
			foreach ($functions_alias_response as $old => $alias_one)
			{
				foreach ($alias as $new => $new_alias_one)
				{
					if ($new_alias_one == $alias_one['alias'])
					{
						unset($alias[$new], $functions_alias_response[$old]);
					}
				}
			}
			//删除无效别名
			if ($functions_alias_response)
			{
				foreach ($functions_alias_response as $one)
				{
					$this->model_function_alias->delete_one_by_id($one['id']);
				}
			}
			//新增别名
			if ($alias)
			{
				foreach ($alias as $one)
				{
					$alias_param['function_alias']['alias'] = $one;
					$alias_param['function_alias']['is_deleted'] = 'N';
					$alias_param['function_alias']['sort'] = '0';
					$alias_param['function_alias']['updated_at'] = date('Y-m-d H:i:s');
					$this->model_function_alias->save($alias_param);
				}
			}
		}
		return true;
	}


	/*
	 * 添加职能
	 * @param array $param 参数
	 * return id
	 */
	public function add($param){
		$depth = intval($param['depth']);
		$parent_id = intval($param['parent_id']);

		// 验证depth 及 function_id
		if(!in_array($depth, $this->depth)) throw new Exception('depth不在范围内，请核实后重新请求', 100004);
		$sql = "select max(function_id) function_id from functions_cluster where `depth` = $depth";
		$function = $this->parse_sql($sql);
		if($function[0]['function_id'] < 1) throw new Exception('depth不在范围内，请核实后重新请求', 100004);

		// 验证parent_id是否有效

		if($parent_id != 0){
			$verify_sql = "select count(1) total from functions_cluster where `is_deleted`  = 0 and `function_id` = $parent_id";
			$parent_detail = $this->parse_sql($verify_sql);
			if($parent_detail[0]['total'] < 1) throw new Exception('该父类ID不存在，请核实', 100004);
		}

		$function_id = $function[0]['function_id'] + 1;
		$data = [
			'function_id' => $function_id,
			'name' => isset($param['name']) ? $param['name']: '',
			'parent_id' => isset($param['parent_id']) ? intval($param['parent_id']): 0,
			'depth' => $depth,
			'alias_name' => isset($param['alias_name']) ? $param['alias_name']: '',
			'remark' => isset($param['remark']) ? $param['remark']: '',
		];

		$this->db->insert('functions_cluster', $data);
		return $function_id;
	}


	/*
	 * 修改职能
	 * @param array $param 参数
	 * return boolean
	 */
	public function edit($param){
		$function_id = intval($param['function_id']);
		// 验证function_id是否有效
		$function_sql = "select function_id,depth from functions_cluster where `is_deleted`  = 0 and `function_id` = $function_id";
		$function_detail = $this->parse_sql($function_sql);
		if(empty($function_detail)) throw new Exception('该职能ID不存在，请核实', 100004);

		// 验证depth
		if(!empty($param['depth']) && !in_array($param['depth'], $this->depth)) throw new Exception('depth不在范围内，请核实后重新请求', 100004);

		// 验证parent_id是否有效
		if(!empty($param['parent_id'])){
			// 验证parent_id是否有效
			$verify_sql = "select count(1) total from functions_cluster where `is_deleted`  = 0 and `function_id` = {$param['parent_id']}";
			$parent_detail = $this->parse_sql($verify_sql);
			if($parent_detail[0]['total'] < 1) throw new Exception('该父类ID不存在，请核实', 100004);
		}

		$data = [];
		if(isset($param['name'])) $data['name'] = $param['name'];
		if(isset($param['parent_id'])) $data['parent_id'] = intval($param['parent_id']);
		if(isset($param['depth'])) $data['depth'] = $param['depth'];
		if(isset($param['alias_name'])) $data['alias_name'] = $param['alias_name'];
		if(isset($param['remark'])) $data['remark'] = $param['remark'];
		if(isset($param['is_deleted'])) $data['is_deleted'] = $param['is_deleted'];

		if(empty($data)) throw new Exception('无内容需修改，请核实后重新请求', 100004);
		$this->db->where('function_id', $function_id);
		$this->db->update('functions_cluster', $data);

		$cache_key = sprintf('%s_%d', 'functions_cluster', $function_id);
		$this->cache->memcached->del($cache_key);
		return true;
	}

	/*
	 * 根据ID获取详情(查老表)
	 * @param int $id
	 * return array
	 */
	public function get_function_by_id($id){
		if(empty($id)) return [];
		$sql = "select `id` function_id, `name` from functions where `is_deleted`  = 'N' and `id` = $id";
		$detail = $this->parse_sql($sql);
		return !empty($detail)? $detail[0]: [];
	}

	/*
	 * 根据IDs获取所有（兼容老数据）
	 * @param array $ids ID集
	 * @param string $seleted
	 */
	public function getMulti($ids, $selected = []){
		if(empty($ids)) return [];

		$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);

		$new_ids = [];
		$old_ids = [];
		foreach ($ids as $id) {
			if($id < 1000000){
				$old_ids[] = $id;
			}else{
				$new_ids[] = $id;
			}
		}

		$result = [];

		if(!empty($new_ids)){
			$new_list = $this->dao('/Dao_function', $dao_param)->get_multi($new_ids, $selected);
			if(!empty($new_list)) $result = $new_list;
		}

		if(!empty($old_ids)){
			$old_list = $this->dao('/Dao_function_old', $dao_param)->get_multi($old_ids, ['id','name', 'is_deleted']);
			foreach($old_list as $key => $val){
				$result[$key] = ['function_id' => $val['id'], 'name' => $val['name'], 'is_deleted' => $val['is_deleted']];
			}
		}

		return $result;
	}

	/*
	 * 根据职能名获取对应层级职能数据
	 * @param string $name 职能名
	 * @param int $depth 查询条件depth
	 * @param int $return_depth 需返回层级 不传时，默认返回$depth数据
	 */
	public function getFunctionsByNameDepth($name, $depth, $return_depth){
		if(empty($name)) return [1];
		if(!in_array($depth, [1,2,3,4])) return [2];
		if(!empty($return_depth) && !in_array($return_depth, [1,2,3,4])) return [3];

		$cache_key = 'GSYSTEM_FUNCTIONS_' . md5('get_functions_by_name_depth_' . serialize([$name, $depth, $return_depth]));

		if(empty($result = $this->cache->memcached->get($cache_key))) {
			// 根据depth 名字模糊查询列表
			$sql = "select function_id,name from functions_cluster where depth = $depth and is_deleted = 0 and name like '%{$name}%'";
			$list = $this->parse_sql($sql);

			if(empty($list)) return [];
			// 如果return_depth为空，或depth等于return_peth 那么直接返回结果
			if(empty($return_depth) || $depth == $return_depth){
				$this->cache->memcached->save($cache_key, $list, 604800);
				return $list;
			}

			if($depth > $return_depth){
				// 获取上层数据
				$depth_num = ($depth - $return_depth);
				switch($depth_num){
					// 获取上一级数据
					case 1:
						$depth_sql = "select DISTINCT function_id,name from functions_cluster where function_id in (select parent_id from functions_cluster where depth = $depth and is_deleted = 0 and name like '%{$name}%') and is_deleted = 0";
						break;
					// 获取上二级数据
					case 2:
						$depth_sql = "select DISTINCT function_id,name from functions_cluster where function_id in (select parent_id from functions_cluster where function_id in (select parent_id from functions_cluster where depth = $depth and is_deleted = 0 and name like '%{$name}%') and is_deleted = 0) and is_deleted = 0";
						break;
					// 获取上三级数据
					case 3:
						$depth_sql = "select DISTINCT function_id,name from functions_cluster where function_id in (select parent_id from functions_cluster where function_id in (select parent_id from functions_cluster where function_id in (select parent_id from functions_cluster where depth = $depth and is_deleted = 0 and name like '%{$name}%') and is_deleted = 0) and is_deleted = 0) and is_deleted = 0";
						break;
					default:
						$depth_sql = "";
				}
			}else{
				$depth_num = ($return_depth - $depth);
				// 获取下层数据
				switch($depth_num) {
					// 获取下一级数据
					case 1:
						$depth_sql = "select DISTINCT function_id,name from functions_cluster where parent_id in (select function_id from functions_cluster where depth = $depth and is_deleted = 0 and name like '%{$name}%') and is_deleted = 0";
						break;
					// 获取下二级数据
					case 2:
						$depth_sql = "select DISTINCT function_id,name from functions_cluster where parent_id in (select function_id from functions_cluster where parent_id in (select function_id from functions_cluster where depth = $depth and is_deleted = 0 and name like '%{$name}%') and is_deleted = 0) and is_deleted = 0";
						break;
					// 获取下三级数据
					case 3:
						$depth_sql = "select DISTINCT function_id,name from functions_cluster where parent_id in (select function_id from functions_cluster where parent_id in (select function_id from functions_cluster where parent_id in (select function_id from functions_cluster where depth = $depth and is_deleted = 0 and name like '%{$name}%') and is_deleted = 0) and is_deleted = 0) and is_deleted = 0";
						break;
					default:
						$depth_sql = "";
				}
			}

			if(empty($depth_sql)) return [];
			$result = $this->parse_sql($depth_sql);
			$this->cache->memcached->save($cache_key, $result, 604800);
		}

		return $result;
	}
}
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
