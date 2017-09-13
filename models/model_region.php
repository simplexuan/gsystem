<?php
/**
 * Model_region
 *
 * @uses Gsystem
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com>
 * @license
 */
class Model_region extends Gsystem_model {
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
			'Gsystem_Model_region_parent_id:%d' => array('parent_id', ),
			'Gsystem_Model_region_level:%d' => array('level', ),
			 
			'Gsystem_Model_region' => array(),
			);
	/**
	 * _equal_search_items 
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_equal_search_items = array('parent_id'=>'t','level'=>'t',);
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
		$new_region  = array();
		if ($id > 0) {
			$new_region['region'] = $this->dao('/Dao_region', $dao_param)->fetch_one_by_id($id);
		} else {
			$new_region['region']  = $this->dao('/Dao_region', $dao_param)->new_one();
		}
								return $new_region;
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
					$region = $param['region'];
			$region_id = isset($region['id']) ? $region['id'] : 0;
						if ($region_id <= 0) {
				$region['id'] = $this->dao('public/Dao_id_allocator')->allocate(array('ns'=>'Gsystem','table_name'=>substr(__CLASS__, 6)));
			}
									$is_update = FALSE;
			if ($region_id > 0) {
				unset($region['id']);
		        $old_region = $this->dao('/Dao_region', $dao_param)->fetch_one_by_id($region_id);
				//$this->_history($region_id, $region);
				$this->dao('/Dao_region', $dao_param)->update($region, $region_id);
				$is_update = TRUE;
			} else {
				$old_region  = array();
				$region_id = $this->dao('/Dao_region', $dao_param)->insert($region);
	            			}

									
			
			//清除缓存
		foreach (array($old_region, array_merge($old_region, $region)) as $v){
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
			return $region_id;
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
		$regions = array('num'=>0, 'results'=>array());
		if ($page > 0 && $pagesize > 0) {
			$param['page']      = $page;
			$param['pagesize']  = $pagesize;
		}
		$selected = !empty($param['selected']) ? $param['selected'] : array();
		if (isset($param['_ft_']) && !empty($param['_ft_'])){
			$regions  = $this->dao('public/Dao_searcher', 
					array('active_group'=>$param['_ft_'], 'id'=>0) )
				->search($param);
		}else{
			$key = $this->_get_cache_key($param);     
			if(empty($param['ordersort'])) {
				$param['ordersort']     =  'created_at DESC';
			}
						
			if ($key === FALSE || ($page * $pagesize) > self :: ID_CACHE_NUM ){
				//    $param['ordersort']     =  'created_at DESC';
				$regions = $this->dao('/Dao_region', $dao_param )->search($param);
			}else{ //缓存
				$regions = $this->cache->memcached->get($key);
				if (empty($regions)) {
					$param['page']     = 1;
					$param['pagesize'] = self :: ID_CACHE_NUM;
					$regions = $this->dao('/Dao_region', $dao_param )->search($param);
					if (!$this->cache->memcached->save($key, $regions)){
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
					if ($regions['num'] < $page* $pagesize){
						$limit = $regions['num'] - ($page-1) * $pagesize;
					}
					$regions['results'] = array_slice($regions['results'], ($page-1) * $pagesize, $limit, TRUE);
				}
			}
			//$regions = $this->dao('/Dao_region', $dao_param )->search($param);
		}
		if ( $regions['num'] > 0) {
			$items = $this->dao('/Dao_region', $dao_param)
				->get_multi(array_keys($regions['results']), $selected);
			foreach ($items as $item) {
				$regions['results'][$item['id']] = $item;
			}
		}
		return  $regions;
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
		$region = $this->dao('/Dao_region', $dao_param)->fetch_one_by_id($id);
		if ($user_id >0 && $region['user_id'] != $user_id){
			throw new Exception(sprintf('function: %s, op:%d has no permission to delete user_id:%d', __FUNCTION__,
						$user_id, $region['user_id']),
					$this->config->item('permission_err_no', 'err_no'));
		}
				$this->dao('/Dao_region', $dao_param)->delete_one_by_id($id);
			foreach($this->_mkeys as $k=>$items){
				$temp = array();
				foreach($items as $item){
					$temp[$item] = $region[$item];
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
		$region = $this->dao('/Dao_region', $dao_param)->fetch_one_by_id($id);
				$this->dao('/Dao_region', $dao_param)->update($param, $id);
		//清除缓存
		foreach (array($region, array_merge($region, $param)) as $v){
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
		return call_user_func_array(array($this->dao('/Dao_region',  $dao_param), $func), $args);
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
				$id  = $this->dao(sprintf('/Dao_%s', 'region'), $dao_param)->update_by_unique($param);
				if (intval($id)<=0){
						$id    = $param['id'] = $this->dao('public/Dao_id_allocator')->allocate(array('ns'=>'Gsystem','table_name'=>'region'));
						$param = array_merge($this->dao(sprintf('/Dao_%s', 'region'), $dao_param)->new_one(), $param);
			$this->dao(sprintf('/Dao_%s', 'region'), $dao_param)->insert($param);
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
	 * 根据level获取区域
	 * @param array $level 等级
	 * return array
	 */
	public function get_regions_by_level($level){
		if(empty($level)) return [];
		$cache_key = 'GSYSTEM_ALL_REGIONS_' . md5('get_regions_by_level_' . implode('-', $level));
		if(empty($rs = $this->cache->memcached->get($cache_key))) {
			if(count($level) > 1) {
				$levels = implode(',', $level);
				$where_level = '(' . rtrim($levels, ',') . ')';
				$where = "`level` in {$where_level} and `is_deleted` = 'N'";
			}else{
				$where = "`level` = {$level[0]} and `is_deleted` = 'N'";
			}

			$sql = "select * from regions where {$where}";
			$rs = $this->parse_sql($sql);
			$this->cache->memcached->save($cache_key, $rs, 604800);
		}

		return $rs;
	}
}
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */