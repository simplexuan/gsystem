<?php
/**
 * Model_school
 *
 * @uses Gsystem
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com>
 * @license
 */
class Model_school extends Gsystem_model {

	private $data_year = 2015; // 获取年份
	private $none_rank = 9999999999; // 未知排名

	protected $table = 'schools';

				/**
	 * _model 
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_model    = '';
	/**
	 * _mkeys 
	 * 
	 * @var array
	 * @access protected
	 */
	protected $_mkeys = array(
			'Gsystem_Model_school_parent_id:%d$name_cn:%s' => array('parent_id', 'name_cn', ),
			'Gsystem_Model_school_name_cn:%s' => array('name_cn', ),
			'Gsystem_Model_school_status:%d' => array('status', ),
			 
			'Gsystem_Model_school' => array(),
			);
	/**
	 * _equal_search_items 
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_equal_search_items = array('parent_id'=>'t','name_cn'=>'t','status'=>'t',);
		/**
	 * __construct
	 *
	 * @access protected
	 * @return mixed
	 */
	function __construct() {
		parent :: __construct();
		$this->_model = substr(__CLASS__, 6);
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
		$new_school  = array();
		if ($id > 0) {
			$new_school['school'] = $this->dao('/Dao_school', $dao_param)->fetch_one_by_id($id);
		} else {
			$new_school['school']  = $this->dao('/Dao_school', $dao_param)->new_one();
		}
								return $new_school;
	}
						/**
	 * save
	 *
	 * @param array $param
	 * @access public
	 * @return mixed
	 */
	function save($param = array()) {
		if ($this->_log->getEffectiveLevel() =='DEBUG'){
			$this->log->debug(var_export($param, TRUE));
		}
		if (empty($param) || !is_array($param)) {
			throw new Exception(sprintf('%s:%s parameters not array()', __CLASS__, __FUNCTION__),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}
		if (!isset($param[$this->_model])) {
			throw new Exception(sprintf('%s:%s input parameters not exists.', __CLASS__, __FUNCTION__),
					$this->config->item('parameter_err_no', 'err_no')
					);
			$model  = $this->_model;
			$$model = $param[$this->_model];
		}
								$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
					$school = $param['school'];
			$school_id = isset($school['id']) ? $school['id'] : 0;
									$is_update = FALSE;
			if ($school_id > 0) {
				unset($school['id']);
		        $old_school = $this->dao('/Dao_school', $dao_param)->fetch_one_by_id($school_id);
				//$this->_history($school_id, $school);
				$this->dao('/Dao_school', $dao_param)->update($school, $school_id);
				$is_update = TRUE;
			} else {
				$old_school  = array();
				$school_id = $this->dao('/Dao_school', $dao_param)->insert($school);
	            			}

									
			
			//清除缓存
		foreach (array($old_school, array_merge($old_school, $school)) as $v){
			foreach ($this->_mkeys as $key_pattern => $keys){
				$temp = array();
				$has_key = TRUE;
				foreach($keys as $key){
					if (!isset($v[$key])){
						$has_key = FALSE;
						break;
					}
					$temp[$key] = $v[$key];
				}
				if (!$has_key) continue;
				$key  = vsprintf($key_pattern, $temp);

				if (!$this->cache->memcached->del($key)){
				}else{
					$this->log->debug(sprintf('%s: delete key:%s from memcached ok.', __FUNCTION__, $key));
				}
			}
		}
			return $school_id;
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
		$schools = array('num'=>0, 'results'=>array());
		if ($page > 0 && $pagesize > 0) {
			$param['page']      = $page;
			$param['pagesize']  = $pagesize;
		}
		$selected = !empty($param['selected']) ? $param['selected'] : array();
		if (isset($param['_ft_']) && !empty($param['_ft_'])){
			$schools  = $this->dao('public/Dao_searcher', 
					array('active_group'=>$param['_ft_'], 'id'=>0) )
				->search($param);
		}else{
			$key = $this->_get_cache_key($param);     
			if(empty($param['ordersort'])) {
				$param['ordersort']     =  'created_at DESC';
			}
						
			if ($key === FALSE || ($page * $pagesize) > self :: ID_CACHE_NUM ){
				//    $param['ordersort']     =  'created_at DESC';
				$schools = $this->dao('/Dao_school', $dao_param )->search($param);
			}else{ //缓存
				$schools = $this->cache->memcached->get($key);
				if (empty($schools)) {
					$param['page']     = 1;
					$param['pagesize'] = self :: ID_CACHE_NUM;
					$schools = $this->dao('/Dao_school', $dao_param )->search($param);
					if (!$this->cache->memcached->save($key, $schools)){
						//                        $this->log->warn(sprintf('set %s to memcached by key:%s failure..', $this->_model, $key));
						$this->log->push_info('del memcached key:%s fail', array($key));
					}
				}else{
					// $this->log->info(sprintf('search  %s from memcached by key:%s success.', $this->_model, $key));
					$this->log->push_info('(model:%s) (hit key:%s)', array($this->_model, $key));
				}

				if ($page > 0 && $pagesize > 0) {
					// array_slice 获取需要的数据
					$limit = $pagesize;
					if ($schools['num'] < $page* $pagesize){
						$limit = $schools['num'] - ($page-1) * $pagesize;
					}
					$schools['results'] = array_slice($schools['results'], ($page-1) * $pagesize, $limit, TRUE);
				}
			}
			//$schools = $this->dao('/Dao_school', $dao_param )->search($param);
		}
		if ( $schools['num'] > 0) {
			$items = $this->dao('/Dao_school', $dao_param)
				->get_multi(array_keys($schools['results']), $selected);
			foreach ($items as $item) {
				$schools['results'][$item['id']] = $item;
			}
		}
		return  $schools;
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
		$school = $this->dao('/Dao_school', $dao_param)->fetch_one_by_id($id);
		if ($user_id >0 && $school['user_id'] != $user_id){
			throw new Exception(sprintf('function: %s, op:%d has no permission to delete user_id:%d', __FUNCTION__,
						$user_id, $school['user_id']),
					$this->config->item('permission_err_no', 'err_no'));
		}
				$this->dao('/Dao_school', $dao_param)->delete_one_by_id($id);
			foreach($this->_mkeys as $k=>$items){
				$temp = array();
				foreach($items as $item){
					$temp[$item] = $school[$item];
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
		$school = $this->dao('/Dao_school', $dao_param)->fetch_one_by_id($id);
				$this->dao('/Dao_school', $dao_param)->update($param, $id);
		//清除缓存
		foreach (array($school, array_merge($school, $param)) as $v){
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
	 * __call 
	 * 
	 * @param mixed $func 
	 * @param mixed $args 
	 * @access protected
	 * @return mixed
	 */
	function __call($func, $args) {
		$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		return call_user_func_array(array($this->dao('/Dao_school',  $dao_param), $func), $args);
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
				$id  = $this->dao(sprintf('/Dao_%s', 'school'), $dao_param)->update_by_unique($param);
				if (intval($id)<=0){
						$param = array_merge($this->dao(sprintf('/Dao_%s', 'school'), $dao_param)->new_one(), $param);
			$this->dao(sprintf('/Dao_%s', 'school'), $dao_param)->insert($param);
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
	 * 根据学校ID获取学校详情
	 * @param int $id 学校ID
	 * return array
	 */
	public function getDetailBySchoolId($id){
		if(empty($id)) return [];

		$cache_key = 'SCHOOL_' . md5('getDetailBySchoolId_' . $id);
		if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
			$sql = "select s.id,s.type,s.name_cn,s.edudirectly,IFNULL(sr.rank1,9999999999) rank from schools s"
				. " LEFT JOIN school_rank sr on sr.school_id = s.id where s.is_deleted = 'N' and s.id = {$id}";
			$list = $this->getListBysql($sql);

			$result = [];
			if(!empty($list)){
				$result = [
					'id' => $list[0]['id'],
					'type' => $list[0]['type'],
					'school_name' => $list[0]['name_cn'],
					'edudirectly' => intval($list[0]['edudirectly']),
					'rank' => $list[0]['rank'] == $this->none_rank ? 0:$list[0]['rank'],
				];
			}

			$this->cache->memcached->save($cache_key, json_encode($result), 604800);
		}

		return $result;
	}

	/*
	 * 根据学校ID获取学校详情
	 * @param string $name 学校名
	 * @param int $region_id 省份ID
	 * @param int $type 学校类型，0是非211,985 1是211,2是985 3是211 985 其他都是不需要判断
	 * @param int $order 排序 1 录取分从高到低，2 录取分从低到高,不传或其他默认e橙排序
	 * @param int $page 页数
	 * @param int $page_size 每页条数
     * return array
	 */
	public function getListByLikename($name, $region_id, $type = -1, $order = 3, $page = 1, $page_size = 10){
		if(empty($name)) return [];

		$cache_key = 'SCHOOL_' . md5('getListByLikename_' . serialize([$name, $region_id, $type, $order, $page, $page_size]));
		if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
			if(in_array($order, [1,2])){
				$sql = "select s.id,s.type,s.name_cn,s.edudirectly,IFNULL(sr.rank1,9999999999) rank,ss.region_id,ss.year,ss.avg_score,"
					. "ss.max_score,ss.min_score from schools s LEFT JOIN school_score ss on ss.school_id = s.id"
					. " LEFT JOIN school_rank sr on sr.school_id"
					. " = s.id where s.is_deleted = 'N' and s.name_cn like '%{$name}%' and ss.region_id = {$region_id}"
					. " and ss.`year` >= {$this->data_year}";
			}else{
				$sql = "select s.id,s.type,s.name_cn,s.edudirectly,IFNULL(sr.rank1,9999999999) rank from schools s"
					. " LEFT JOIN school_rank sr on sr.school_id"
					. " = s.id where s.is_deleted = 'N' and s.name_cn like '%{$name}%'";
			}

			// 学校类型
			switch($type){
				case 0:
					$sql .= ' and s.type = 0';
					break;
				case 1:
					$sql .= ' and s.type = 1';
					break;
				case 2:
					$sql .= ' and s.type = 2';
					break;
				case 3:
					$sql .= ' and s.type in (1,2)';
					break;
				default:
					$sql .= '';
			}

			// 排序
			switch($order){
				case 1:
					$sql .= ' order by ss.min_score desc';
					break;
				case 2:
					$sql .= ' order by ss.min_score asc';
					break;
				default:
					$sql .= ' order by rank asc';
			}

			$list = $this->getListBysql($sql);

			$data = [];
			if(in_array($order, [1,2])){
				$data_year = [];
				foreach($list as $val){
					$school_key = 'school-' . intval($val['id']);

					// 如果data为空，或者数据不存在，或者数据存在，且年份大于等于当前你那份
					if(empty($data) || !array_key_exists($school_key, $data) || (array_key_exists($school_key, $data) && $val['year'] >= $data_year[$school_key])){
						if(array_key_exists($school_key, $data)){
							unset($data[$school_key]);
							unset($data_year[$school_key]);
						}

						$data_year[$school_key] = $val['year'];
						$data[$school_key] = [
							'school_id'     => $val['id'],
							'school_name'   => $val['name_cn'],
							'type'     		=> $val['type'],
							'edudirectly'   => intval($val['edudirectly']),
							'rank'          => $val['rank'] == $this->none_rank ? 0:$val['rank'],
							'year'          => intval($val['year']),
							'avg_score'     => intval($val['avg_score']),
							'max_score'     => intval($val['max_score']),
							'min_score'     => intval($val['min_score']),
						];
					}
				}
			}else{
				// e橙排序数据
				// 获取每个学校对应最近年份的数据
				foreach($list as $val){
					$school_key = intval($val['id']);
					$data[$school_key] = [
						'school_id'     => $val['id'],
						'school_name'   => $val['name_cn'],
						'type'     		=> $val['type'],
						'edudirectly'   => intval($val['edudirectly']),
						'rank'          => $val['rank'] == $this->none_rank ? 0:$val['rank'],
					];
				}
			}

			$result = [
				'total' => count($data),
				'current_page' => $page,
				'page_size' => $page_size,
				'list' => $this->getPageList($data, $page, $page_size),
			];

			$this->cache->memcached->save($cache_key, json_encode($result), 604800);
		}

		return $result;
	}

	/*
     * 获取分页数据
     * @param array $data 数据
     * @param int $page 页数
     * @param int $page_size 每页条数
     * return array
     */
	public function getPageList($data, $page = 1, $page_size = 10){
		if(empty($data) || !is_array($data)) return [];
		$limit = $page_size;
		$total = count($data);
		if ($total < $page* $page_size){
			$limit = $total - ($page-1) * $page_size;
		}
		return array_values(array_slice($data, ($page-1) * $page_size, $limit));
	}

	/*
     * 根据SQL获取列表
     * @param $string $sql sql
     * return []
     */
	public function getListBysql($sql){
		if(empty($sql)) return [];
		$cache_key = 'SCHOOL_' . md5('getListBysql' . serialize($sql));
		if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
			$list = $this->parse_sql($sql);
			$result = !empty($list) ? $list: [];
			$this->cache->memcached->save($cache_key, json_encode($result), 604800); // 缓存一周
		}

		return $result;
	}

	/*
	 * 添加学校
	 * @param array $param
	 * return array
	 */
	public function add($param){
		if(empty($param)) throw new Exception('参数不能为空，请核实', 100001);
		if(empty($param['name_cn'])) throw new Exception('学校名不能为空，请核实', 100001);

		if($this->checkName($param['name_cn']))  throw new Exception('学校名已存在，请核实', 100001);

		$time = date('Y-m-d H:i:s');
		$data = [
			'name_cn' 		=> $param['name_cn'],
			'created_at' 	=> $time,
			'updated_at' 	=> $time,
		];

		if(isset($param['mid'])) 			$data['mid'] = intval($param['mid']);
		if(isset($param['type'])) 			$data['type'] = intval($param['type']);
		if(isset($param['parent_id'])) 		$data['parent_id'] = intval($param['parent_id']);
		if(isset($param['name_en'])) 		$data['name_en'] = $param['name_en'];
		if(isset($param['alias'])) 			$data['alias'] = $param['alias'];
		if(isset($param['url'])) 			$data['url'] = $param['url'];
		if(isset($param['introduction'])) 	$data['introduction'] = $param['introduction'];
		if(isset($param['baseinfo'])) 		$data['baseinfo'] = $param['baseinfo'];
		if(isset($param['extendinfo'])) 	$data['extendinfo'] = $param['extendinfo'];
		if(isset($param['logo'])) 			$data['logo'] = $param['logo'];
		if(isset($param['status'])) 		$data['status'] = intval($param['status']);
		if(isset($param['edudirectly'])) 	$data['edudirectly'] = intval($param['edudirectly']);

		$this->db->insert($this->table, $data);
		$insert_id = $this->db->insert_id();
		if($insert_id) return [ 'id' => $insert_id];
		throw new Exception('添加失败', 100017);
	}

	/*
	 * 验证名称是否已存在
	 * @param string $name 名称
	 * @param int $id 用于非该Id
	 * return int
	 */
	public function checkName($name, $id = 0){
		$this->db->from($this->table);

		if(empty($name)) return false;

		$this->db->where("is_deleted", 'N');
		$this->db->where("name_cn", $name);

		if(!empty($id)) $this->db->where('id !=', $id);

		return $this->db->count_all_results();
	}


	/*
	 * 验证ID是否已存在
	 * @param int $id 学校ID
	 * return int
	 */
	public function checkId($id){
		$this->db->from($this->table);

		if(empty($id)) return false;

		$this->db->where("is_deleted", 'N');
		$this->db->where("id", $id);

		return $this->db->count_all_results();
	}


	/*
	 * 修改学校
	 * @param array $param
	 * return array
	 */
	public function update($param){
		if(empty($param)) throw new Exception('参数不能为空，请核实', 100001);
		if(empty($param['id'])) throw new Exception('学校ID不能为空，请核实', 100001);
		if(empty($param['name_cn'])) throw new Exception('学校名称不能为空，请核实', 100001);

		if(!$this->checkId($param['id']))  throw new Exception('学校ID不存在，请核实', 100001);
		if($this->checkName($param['name_cn'], $param['id']))  throw new Exception('学校名已存在，请核实', 100001);

		$data = [
			'name_cn' => $param['name_cn'],
			'updated_at' => date('Y-m-d H:i:s'),
		];

		if(isset($param['mid'])) 			$data['mid'] = intval($param['mid']);
		if(isset($param['type'])) 			$data['type'] = intval($param['type']);
		if(isset($param['parent_id'])) 		$data['parent_id'] = intval($param['parent_id']);
		if(isset($param['name_en'])) 		$data['name_en'] = $param['name_en'];
		if(isset($param['alias'])) 			$data['alias'] = $param['alias'];
		if(isset($param['url'])) 			$data['url'] = $param['url'];
		if(isset($param['introduction'])) 	$data['introduction'] = $param['introduction'];
		if(isset($param['baseinfo'])) 		$data['baseinfo'] = $param['baseinfo'];
		if(isset($param['extendinfo'])) 	$data['extendinfo'] = $param['extendinfo'];
		if(isset($param['logo'])) 			$data['logo'] = $param['logo'];
		if(isset($param['status'])) 		$data['status'] = intval($param['status']);
		if(isset($param['edudirectly'])) 	$data['edudirectly'] = intval($param['edudirectly']);

		$this->db->where('id', $param['id']);
		$this->db->update($this->table, $data);

		// 清除缓存
		$key = sprintf('%s_%d',$this->table, $param['id']);
		$this->cache->memcached->del($key);
		return [];
	}

	/*
	 * 删除
	 * @param int $id 学校ID
	 * return array
	 */
	public function delete($id){
		if(empty($id)) throw new Exception('学校ID不能为空，请核实', 100001);

		if(!$this->checkId($id))  throw new Exception('学校ID不存在，请核实', 100001);

		$data = [
			'is_deleted' => 'Y',
			'updated_at' => date('Y-m-d H:i:s'),
		];

		$this->db->where('id', $id);

		try {
			$this->db->update($this->table, $data);
			// 清除缓存
			$key = sprintf('%s_%d',$this->table, $id);
			$this->cache->memcached->del($key);
		}catch(Exception $e){
			throw new Exception('已有重复数据存在，不能进行删除，如有疑问，请联系相关技术人员，谢谢', 23000);
		}

		return [];
	}


	/*
	 * 获取历年学校分数数据
	 * @param int $school_id 学校ID
	 * @param int $region_id 省份ID
	 * @param int $studenttype_id 文理科
	 * @param int $year 年份
     * return array
	 */
	public function getSchoolScores($school_id, $region_id = '', $studenttype_id = '', $year = 2012){
		$cache_key = 'SCHOOL_' . md5('getSchoolScores' . serialize([$school_id, $region_id, $studenttype_id, $year]));
		if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
			$sql = "select * from school_score where school_id = $school_id and `year` >= $year ";

			if(!empty($region_id)) $sql .= " and region_id = $region_id ";
			if($studenttype_id != -1) $sql .= " and studenttype_id = $studenttype_id ";

			$sql .= ' order by `year`';

			$result = $this->getListBysql(	$sql);
			$this->cache->memcached->save($cache_key, json_encode($result), 604800);
		}

		return $result;
	}

	/*
	 * 获取历年省控分数数据
	 * @param int $school_id 学校ID
	 * @param int $region_id 省份ID
	 * @param int $studenttype_id 文理科
	 * @param int $year 年份
     * return array
	 */
	public function getProvinceScores($region_id = '', $studenttype_id = '', $batch_id = ''){
		$cache_key = 'SCHOOL_' . md5('getProvinceScores' . serialize([$region_id, $studenttype_id, $batch_id]));
		if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
			$sql = "select * from school_provincescore ";

			$and = '';
			$where = ' where ';
			if(!empty($region_id)){
				$and = ' and ';
				$sql .= $where . " region_id = $region_id ";
				$where = '';
			}
			if($studenttype_id != -1){
				$sql .= $where . $and . " studenttype_id = $studenttype_id ";
				$where = '';
				$and = ' and ';
			}
			if($batch_id != -1){
				$sql .= $where . $and . " batch_id = $batch_id ";
			}

			$sql .= ' order by `year`';

			$result = $this->getListBysql($sql);

			$this->cache->memcached->save($cache_key, json_encode($result), 604800);
		}

		return $result;
	}
}
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
