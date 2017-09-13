<?php
/**
 * Model_corporation_baseinfo
 *
 * @uses Gsystem
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com>
 * @license
 */
class Model_corporation_baseinfo extends Gsystem_model {
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
			'Gsystem_Model_corporation_baseinfo_corporation_id:%d$type_id:%d' => array('corporation_id', 'type_id', ),
			'Gsystem_Model_corporation_baseinfo_corporation_id:%d' => array('corporation_id', ),
			 
			'Gsystem_Model_corporation_baseinfo' => array(),
			);
	/**
	 * _equal_search_items 
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_equal_search_items = array('corporation_id'=>'t','type_id'=>'t',);

	private $baseinfos_extend_field = ['id','corporation_id','financing','scale','scale_min','scale_max','status','is_deleted','updated_at','created_at'];
	private $insert_baseinfos_extend_field = ['corporation_id','financing','scale'];

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
		$new_corporation_baseinfo  = array();
		if ($id > 0) {
			$new_corporation_baseinfo['corporation_baseinfo'] = $this->dao('/Dao_corporation_baseinfo', $dao_param)->fetch_one_by_id($id);
		} else {
			$new_corporation_baseinfo['corporation_baseinfo']  = $this->dao('/Dao_corporation_baseinfo', $dao_param)->new_one();
		}
								return $new_corporation_baseinfo;
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
					$corporation_baseinfo = $param['corporation_baseinfo'];
			$corporation_baseinfo_id = isset($corporation_baseinfo['id']) ? $corporation_baseinfo['id'] : 0;
									$is_update = FALSE;
			if ($corporation_baseinfo_id > 0) {
				unset($corporation_baseinfo['id']);
		        $old_corporation_baseinfo = $this->dao('/Dao_corporation_baseinfo', $dao_param)->fetch_one_by_id($corporation_baseinfo_id);
				//$this->_history($corporation_baseinfo_id, $corporation_baseinfo);
				$this->dao('/Dao_corporation_baseinfo', $dao_param)->update($corporation_baseinfo, $corporation_baseinfo_id);
				$is_update = TRUE;
			} else {
				$old_corporation_baseinfo  = array();
				$corporation_baseinfo_id = $this->dao('/Dao_corporation_baseinfo', $dao_param)->insert($corporation_baseinfo);
	            			}

									
			
			//清除缓存
		foreach (array($old_corporation_baseinfo, array_merge($old_corporation_baseinfo, $corporation_baseinfo)) as $v){
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
			return $corporation_baseinfo_id;
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
		$corporation_baseinfos = array('num'=>0, 'results'=>array());
		if ($page > 0 && $pagesize > 0) {
			$param['page']      = $page;
			$param['pagesize']  = $pagesize;
		}
		$selected = !empty($param['selected']) ? $param['selected'] : array();
		if (isset($param['_ft_']) && !empty($param['_ft_'])){
			$corporation_baseinfos  = $this->dao('public/Dao_searcher', 
					array('active_group'=>$param['_ft_'], 'id'=>0) )
				->search($param);
		}else{
			$key = $this->_get_cache_key($param);     
			if(empty($param['ordersort'])) {
				$param['ordersort']     =  'created_at DESC';
			}
						
			if ($key === FALSE || ($page * $pagesize) > self :: ID_CACHE_NUM ){
				//    $param['ordersort']     =  'created_at DESC';
				$corporation_baseinfos = $this->dao('/Dao_corporation_baseinfo', $dao_param )->search($param);
			}else{ //缓存
				$corporation_baseinfos = $this->cache->memcached->get($key);
				if (empty($corporation_baseinfos)) {
					$param['page']     = 1;
					$param['pagesize'] = self :: ID_CACHE_NUM;
					$corporation_baseinfos = $this->dao('/Dao_corporation_baseinfo', $dao_param )->search($param);
					if (!$this->cache->memcached->save($key, $corporation_baseinfos)){
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
					if ($corporation_baseinfos['num'] < $page* $pagesize){
						$limit = $corporation_baseinfos['num'] - ($page-1) * $pagesize;
					}
					$corporation_baseinfos['results'] = array_slice($corporation_baseinfos['results'], ($page-1) * $pagesize, $limit, TRUE);
				}
			}
			//$corporation_baseinfos = $this->dao('/Dao_corporation_baseinfo', $dao_param )->search($param);
		}
		if ( $corporation_baseinfos['num'] > 0) {
			$items = $this->dao('/Dao_corporation_baseinfo', $dao_param)
				->get_multi(array_keys($corporation_baseinfos['results']), $selected);
			foreach ($items as $item) {
				$corporation_baseinfos['results'][$item['id']] = $item;
			}
		}
		return  $corporation_baseinfos;
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
		$corporation_baseinfo = $this->dao('/Dao_corporation_baseinfo', $dao_param)->fetch_one_by_id($id);
		if ($user_id >0 && $corporation_baseinfo['user_id'] != $user_id){
			throw new Exception(sprintf('function: %s, op:%d has no permission to delete user_id:%d', __FUNCTION__,
						$user_id, $corporation_baseinfo['user_id']),
					$this->config->item('permission_err_no', 'err_no'));
		}
				$this->dao('/Dao_corporation_baseinfo', $dao_param)->delete_one_by_id($id);
			foreach($this->_mkeys as $k=>$items){
				$temp = array();
				foreach($items as $item){
					$temp[$item] = $corporation_baseinfo[$item];
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
		$corporation_baseinfo = $this->dao('/Dao_corporation_baseinfo', $dao_param)->fetch_one_by_id($id);
				$this->dao('/Dao_corporation_baseinfo', $dao_param)->update($param, $id);
		//清除缓存
		foreach (array($corporation_baseinfo, array_merge($corporation_baseinfo, $param)) as $v){
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
		return call_user_func_array(array($this->dao('/Dao_corporation_baseinfo',  $dao_param), $func), $args);
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
				$id  = $this->dao(sprintf('/Dao_%s', 'corporation_baseinfo'), $dao_param)->update_by_unique($param);
				if (intval($id)<=0){
						$param = array_merge($this->dao(sprintf('/Dao_%s', 'corporation_baseinfo'), $dao_param)->new_one(), $param);
			$this->dao(sprintf('/Dao_%s', 'corporation_baseinfo'), $dao_param)->insert($param);
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


	/** 查询公司扩展信息  单/多
	 * @param array $param [
					'id' => 0, // 主键ID
					'corporation_id' => 1, //公司ID
					'page' => 1, //页码
					'page_size' => 10, //每页条数
					'field' => 'corporation_id,financing,scale',//查询字段
					]
	 * @return array
	 */
	public function get_baseinfos_extend($param){

		if(isset($param['field'])){
			$field_arr = explode(',',$param['field']);
			foreach($field_arr as $field){
				if(!in_array($field,$this->baseinfos_extend_field)){
					throw new Exception("$field 字段不存在！", 100001);
				}
			}
			$field = $param['field'];
		}else{
			$field = '*';
		}

		$where = " `is_deleted` = 'N'";
		// 如果id为空 则多条查询，否则为单条查询
		if(empty($param['id'])){
			if(!empty($param['corporation_id'])){
				$where .=  " and `corporation_id` = " . $param['corporation_id'];
			}
			$page_size = isset($param['page_size']) ? intval($param['page_size']) : 10;
			$page = isset($param['page']) ? (intval($param['page']) > 0 ? intval($param['page']): 1) : 1;
			$result = $this->parse_sql("select count(1) as 'total' from corporations_baseinfos_extend where $where");

			$total_page = ceil($result[0]['total']/$page_size);

			$page = $page >= $total_page ? ($total_page > 0 ? $total_page : 1) : $page;

			$sql = "SELECT $field FROM corporations_baseinfos_extend WHERE id>= (SELECT id FROM corporations_baseinfos_extend where $where ORDER BY id asc LIMIT ". ($page-1)*$page_size .", 1) and $where ORDER BY id asc LIMIT $page_size";
			$results = $this->parse_sql($sql);

			return [
				'page'          => $page,
				'page_size'     => $page_size,
				'total'         => $result[0]['total'],
				'list'          => $results
			];
		}else{ // one
			$where .=  " and `id` = " . $param['id'];
			$result = $this->parse_sql("select $field from corporations_baseinfos_extend where $where");
			return !empty($result[0]) ? $result[0]: [];
		}
	}

	/**
	 * 添加公司扩展信息
	 * @param array $param [
	            "corporation_id" => 1, 		// 公司ID
	            "financing" => "B轮融资",	  // 公司融资信息
	            "scale" => "100人以上"         // 公司规模
	        ]
	 */
	public function add_baseinfos_extend($param){
		L('添加公司扩展信息 数据校验... param:' . json_encode($param, JSON_UNESCAPED_UNICODE),4);
		foreach($param as $key => $val){
			if(!in_array($key, $this->insert_baseinfos_extend_field)){
				throw new Exception("$key 字段不符合规则，请查阅接口文档！", 100001);
			}
			if($key == 'corporation_id' && empty($val)){
				throw new Exception("$key 字段不能为空！", 100001);
			}
		}

		$scale_min = $scale_max = 0;
		if(!empty($param['scale'])){
			$scale = explode('-', $param['scale']);
			$scale_min = !empty($scale[0]) ? intval($scale[0]): 0;
			$scale_max = !empty($scale[1]) ? intval($scale[1]): 0;
		}

		// 删除对应扩展信息
		$this->delete_baseinfos_extend($param['corporation_id']);

		if(!empty($detail[0])) throw new Exception("{$detail[0]['id']} 当前数据已存在", 100001);
		$values  = "(" . $param['corporation_id'] . ",'";
		$values .= $param['financing'] . "','";
		$values .= $param['scale'] . "',";
		$values .= $scale_min . ",";
		$values .= $scale_max . ")";
		L('添加公司扩展信息 插入数据... param:' . json_encode($param, JSON_UNESCAPED_UNICODE),4);
		$insert_id = $this->parse_sql("insert into corporations_baseinfos_extend(`corporation_id`,`financing`,`scale`,`scale_min`,`scale_max`) VALUES $values", false, true);

		if(empty($insert_id)){
			throw new Exception("添加失败", 100003);
		}
		return ['id' => $insert_id];
	}

	/*
	 * 删除公司扩展信息
	 * @param int $corporation_id 公司ID
	 * return array
	 */
	public function delete_baseinfos_extend($corporation_id){
		$this->parse_sql("delete from corporations_baseinfos_extend where corporation_id = $corporation_id", false);
		return [ 'status' => 0, 'msg' => '请求成功'];
	}
}

