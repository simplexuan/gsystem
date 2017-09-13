<?php
/**
 * Model_title
 *
 * @uses Gsystem
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com>
 * @license
 */
class Model_title extends Gsystem_model {
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
			'Gsystem_Model_title_name:%s' => array('name', ),

			'Gsystem_Model_title' => array(),
			);
	/**
	 * _equal_search_items
	 *
	 * @var string
	 * @access protected
	 */
	protected $_equal_search_items = array('name'=>'t',);
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
	 * new_c
	 *
	 * @param int $id
	 * @access public
	 * @return mixed
	 */
	function new_c($id = 0) {
		$dao_param      = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		$new_title  = array();
		if ($id > 0) {
			$new_title['title'] = $this->dao('/Dao_title', $dao_param)->fetch_one_by_id($id);
		} else {
			$new_title['title']  = $this->dao('/Dao_title', $dao_param)->new_one();
		}
								return $new_title;
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
					$title = $param['title'];
			$title_id = isset($title['id']) ? $title['id'] : 0;
									$is_update = FALSE;
			if ($title_id > 0) {
				unset($title['id']);
		        $old_title = $this->dao('/Dao_title', $dao_param)->fetch_one_by_id($title_id);
				//$this->_history($title_id, $title);
				$this->dao('/Dao_title', $dao_param)->update($title, $title_id);
				$is_update = TRUE;
				$operation_type = Model_record_log::RECORD_LOG_TYPE_UPDATE;
				$this->model_record_log->save_operation_log($old_title, $param, $title_id, $this->_model,$operation_type);
			} else {
				$old_title  = array();
				$title_id = $this->dao('/Dao_title', $dao_param)->insert($title);
				$operation_type = Model_record_log::RECORD_LOG_TYPE_INSERT;
				$this->model_record_log->save_operation_log($old_title, $param, $title_id, $this->_model,$operation_type);
	            			}



			//清除缓存
		foreach (array($old_title, array_merge($old_title, $title)) as $v){
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
			return $title_id;
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
		$titles = array('num'=>0, 'results'=>array());
		if ($page > 0 && $pagesize > 0) {
			$param['page']      = $page;
			$param['pagesize']  = $pagesize;
		}
		$selected = !empty($param['selected']) ? $param['selected'] : array();
		if (isset($param['_ft_']) && !empty($param['_ft_'])){
			$titles  = $this->dao('public/Dao_searcher',
					array('active_group'=>$param['_ft_'], 'id'=>0) )
				->search($param);
		}else{
			$key = $this->_get_cache_key($param);
			if(empty($param['ordersort'])) {
				$param['ordersort']     =  'created_at DESC';
			}

			if ($key === FALSE || ($page * $pagesize) > self :: ID_CACHE_NUM ){
				//    $param['ordersort']     =  'created_at DESC';
				$titles = $this->dao('/Dao_title', $dao_param )->search($param);
			}else{ //缓存
				$titles = $this->cache->memcached->get($key);
				if (empty($titles)) {
					$param['page']     = 1;
					$param['pagesize'] = self :: ID_CACHE_NUM;
					$titles = $this->dao('/Dao_title', $dao_param )->search($param);
					if (!$this->cache->memcached->save($key, $titles)){
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
					if ($titles['num'] < $page* $pagesize){
						$limit = $titles['num'] - ($page-1) * $pagesize;
					}
					$titles['results'] = array_slice($titles['results'], ($page-1) * $pagesize, $limit, TRUE);
				}
			}
			//$titles = $this->dao('/Dao_title', $dao_param )->search($param);
		}
		if ( $titles['num'] > 0) {
			$items = $this->dao('/Dao_title', $dao_param)
				->get_multi(array_keys($titles['results']), $selected);
			foreach ($items as $item) {
				$titles['results'][$item['id']] = $item;
			}
		}
		return  $titles;
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
		$title = $this->dao('/Dao_title', $dao_param)->fetch_one_by_id($id);
		if ($user_id >0 && $title['user_id'] != $user_id){
			throw new Exception(sprintf('function: %s, op:%d has no permission to delete user_id:%d', __FUNCTION__,
						$user_id, $title['user_id']),
					$this->config->item('permission_err_no', 'err_no'));
		}
				$this->dao('/Dao_title', $dao_param)->delete_one_by_id($id);
				$operation_type = Model_record_log::RECORD_LOG_TYPE_DELETE;
				$new_data = array();
				$this->model_record_log->save_operation_log($title, $new_data, $id, $this->_model, $operation_type);
			foreach($this->_mkeys as $k=>$items){
				$temp = array();
				foreach($items as $item){
					$temp[$item] = $title[$item];
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
		$title = $this->dao('/Dao_title', $dao_param)->fetch_one_by_id($id);
				$this->dao('/Dao_title', $dao_param)->update($param, $id);
		//清除缓存
		foreach (array($title, array_merge($title, $param)) as $v){
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
		return call_user_func_array(array($this->dao('/Dao_title',  $dao_param), $func), $args);
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
				$id  = $this->dao(sprintf('/Dao_%s', 'title'), $dao_param)->update_by_unique($param);
				if (intval($id)<=0){
						$param = array_merge($this->dao(sprintf('/Dao_%s', 'title'), $dao_param)->new_one(), $param);
			$this->dao(sprintf('/Dao_%s', 'title'), $dao_param)->insert($param);
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
}
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
