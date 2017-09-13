<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Dao Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/config.html
 */
class CI_Dao {
	protected $_table;
	protected $_insert_fields = array();
	protected $_update_fields = array();
	protected $_select_fields = array();
	protected $_primary_key   = 'id';
	protected $_foreign_key   = '';
	protected $_dao_prefix    = 'Dao_';
	protected $_tree_daos     = array('architecture'=>1);
	protected $_dao           = '';
	protected $_unique_fields = array();
	protected $_auto_increment = FALSE;
	/**
	 * Constructor
	 *
	 * @access public
	 */
	function __construct(){
            L("Dao Class Initialized",6);
	}

	/**
	 * __get
	 *
	 * Allows models to access CI's loaded classes using the same
	 * syntax as controllers.
	 *
	 * @param	string
	 * @access private
	 */
	function __get($key)
	{
		$CI =& get_instance();
		if ($key =='db' && !isset($CI->$key)){ 
			$this->load->database('', FALSE, TRUE);

			//$CI->db->conn_id->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			//$CI->db->conn_id->query('set names utf8');
			return $CI->$key;
		}
		elseif($key=='cache' && !isset($CI->$key)){ 
			$this->load->driver('cache', NULL, 'cache');
		}
		return $CI->$key;
	}
	/**
	 * fetch_one_by_id
	 *
	 * @param int $id
	 * @access public
	 * @return mixed
	 */
	function fetch_one_by_id($param = 0, $selected=array()) {
		$id   = is_array($param) ? $param['id'] : $param;
		if ($id <= 0){
			throw new Exception(sprintf('function: %s, table:%s parameter: id %s must greater than 0',
						__FUNCTION__, $this->_table, $id),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}
		$key = sprintf('%s_%d',$this->_table, $id);
		$selected_key = sprintf('%s_%d_%se',$this->_table, $id, md5(json_encode($selected,true)));

		$get_cache_key = empty($selected)? $key: $selected_key;

		if ($result = $this->cache->memcached->get($get_cache_key)){
			$this->_log->push_info('(hit:%s)', array($get_cache_key));
			return $result;
		}

		$sql = sprintf('SELECT  %s FROM %s WHERE %s=%d', implode(',', $this->_select_fields), $this->_table, $this->_primary_key, $id);
		$results = $this->db->query($sql);
		if ($results->num_rows()<= 0){
			throw new Exception(sprintf('function: %s, id:%d table:%s not exists. sql:%s', __FUNCTION__, $id, $this->_table, $sql),
					$this->config->item('row_not_exists_err_no', 'err_no')
					);
		}

		$result = $results->first_row('array');
		if (!$this->cache->memcached->save($key, $result)){
			$this->_log->warn(sprintf('set %s from memcached by key:%s failure.%s', $this->_dao, $key,
				$this->cache->memcached->error_message()));
		}
		if($this->_log->getEffectiveLevel() =='DEBUG'){
			$this->_log->push_info('%s:%s', array(__FUNCTION__, $this->db->last_query()));
		}
        $selected = is_array($selected) ? $selected :  (strpos($selected, ',') ===FALSE ? array($selected) : explode(',',  $selected)); 
        if ($selected){
            $sub_result = array();
            foreach ($selected as $field){
                if (!isset($result[$field])) continue;
                $sub_result[$field] = $result[$field];
            }
			$this->cache->memcached->save($selected_key, $sub_result);
			return $sub_result;
        }

		return $result;
	}

	/**
	 * insert_batch
	 *
	 * @param mixed $param
	 * @access public
	 * @return mixed
	 */
	function insert_batch($params =array()) {
		$new_params = array();
		foreach ($params as $param) {
			foreach ($this->_insert_fields as $field){
				if (!isset($param[$field])){
					throw new Exception(sprintf('function:%s,table:%s parameter: %s not exists.', __FUNCTION__, $this->_table, $field),
							$this->config->item('parameter_err_no', 'err_no')
							);
				}
				$new_param[$field] = $param[$field];
			}
			$new_params[] = $new_param;
		}
		if ($this->db->insert_batch($this->_table, $new_params) === FALSE){
			throw new Exception(sprintf('insert into table %s error:%s sql: %s',
						$this->_table, $this->db_error(), $this->db->last_query()),
					$this->config->item('db_err_no', 'err_no')
					);

		}
		if($this->_log->getEffectiveLevel() =='DEBUG'){
			$this->_log->push_info('%s:%s', array(__FUNCTION__, $this->db->last_query()));
		}
		return $this->db->affected_rows();
	}

	/**
	 * insert_unique
	 *
	 * @param array $param
	 * @access public
	 * @return mixed
	 */
	function insert_unique($param = array()) {
		if (!isset($param['user_id'])) {
			$new_param = array('user_id'=>$this->uid[getmypid()], 'user_name'=>$this->user_info[getmypid()]['user_name']);
		} else {
			$new_param = array('user_id'=>$param['user_id']);
		}
		foreach ($this->_unique_fields as $field) {
			if (!isset($param[$field])){
				throw new Exception(sprintf('function: %s, parameter: %s not exists.', __FUNCTION__, $field),
						$this->config->item('parameter_err_no', 'err_no')
						);
			}
			$new_param[$field] = $param[$field];
		}
		if ($this->db->insert($this->_table, $new_param) === FALSE){
			throw new Exception(sprintf('insert into table %s error:%s', $this->_table, $this->db_error()),
					$this->config->item('db_err_no', 'err_no')
					);

		}
		return $new_param[$this->_primary_key];
	}

	/**
	 * insert
	 *
	 * @param array $param
	 * @access public
	 * @return mixed
	 */
	function insert($param = array()) {
		$new_param = array();
		foreach ($this->_insert_fields as $field){
			if (!isset($param[$field])){
				throw new Exception(sprintf('function:%s,table:%s parameter: %s not exists.',
							__FUNCTION__, $this->_table, $field),
						$this->config->item('parameter_err_no', 'err_no')
						);
			}
			$new_param[$field] = $param[$field];
		}

		if ($this->db->insert($this->_table, $new_param) === FALSE){
			throw new Exception(sprintf('insert into table %s error:%s sql: %s',
						$this->_table, $this->db_error(), $this->db->last_query()),
					$this->config->item('db_err_no', 'err_no')
					);

		}
		if($this->_log->getEffectiveLevel() =='DEBUG'){
			$this->_log->push_info('%s:%s', array(__FUNCTION__, $this->db->last_query()));
		}
		return $this->_auto_increment ? $this->db->insert_id(): $new_param[$this->_primary_key];
	}

	/**
	 * replace
	 *
	 * @param array $param
	 * @access public
	 * @return mixed
	 */
	function replace($param = array()) {
		$new_param = array();
		foreach ($this->_insert_fields as $field) {
			if (!isset($param[$field])){
				throw new Exception(sprintf('function: %s, table:%s parameter: %s not exists.', __FUNCTION__, $this->_table,$field),
						$this->config->item('parameter_err_no', 'err_no')
						);
			}
			$new_param[$field] = $param[$field];
		}

		if ($rs = $this->db->replace($this->_table, $new_param) === FALSE) {
			throw new Exception(sprintf('insert into table %s error:%s sql: %s',
						$this->_table, $this->db_error(), $this->db->last_query()),
					$this->config->item('db_err_no', 'err_no')
					);

		}
		$key = sprintf('%s_%d',$this->_table, $this->_primary_key);
		if (!$this->cache->memcached->del($key)){
			$this->_log->warn(sprintf('%s: delete key:%s from memcached failure. %s', __FUNCTION__, $key,
						$this->cache->memcached->error_message()
						));
		}
		if($this->_log->getEffectiveLevel() =='DEBUG'){
			$this->_log->push_info('%s:%s', array(__FUNCTION__, $this->db->last_query()));
		}

		return $new_param[$this->_primary_key];
	}
	/**
	 * update_by_unique 
	 * 
	 * @param mixed $param 
	 * @access public
	 * @return mixed
	 */
	function update_by_unique($param) {
		$unique_params = array();
		foreach($this->_unique_fields as $k){
			if ($k=='id') continue;
			if (!isset($param[$k])){
				throw new Exception(sprintf('%s:%s parameters %s unique key not exists', __CLASS__, __FUNCTION__, $k),
						$this->config->item('parameter_err_no', 'err_no')
						);
			}
			$unique_params[$k] = $param[$k];
		}

		$new_param = array();
		foreach ($param as $k=>$v){
			if (!in_array($k, $this->_update_fields)){
				throw new Exception(sprintf('function: %s, parameter: %s  must not in fields', __FUNCTION__, $k),
						$this->config->item('parameter_err_no', 'err_no')
						);
			}
			$new_param[$k] = $v;
		}

		$this->db->select($this->_primary_key);
		$this->db->where($unique_params);
		$q = $this->db->get($this->_table);
		if ($q->num_rows()>0){
			$row = $q->first_row('array');
			$this->db->update($this->_table, $param, $row);
			$id = $row['id'];
			$key = sprintf('%s_%d',$this->_table, $id);
			if (!$this->cache->memcached->del($key)){
				$this->_log->warn(sprintf('%s: delete key:%s from memcached failure.%s', __FUNCTION__, $key,
							$this->cache->memcached->error_message()
							));
			}else{
				$this->_log->debug(sprintf('%s: delete key:%s from memcached ok.', __FUNCTION__, $key));
			}
			return $id;
		}
		return FALSE;
	}
	/**
	 * update_by_where 
	 * 
	 * @param array $param 
	 * @param array $arr_where 
	 * @access public
	 * @return mixed
	 */
	function update_by_where($param=array(), $arr_where=array()){
		return $this->delete_by_markup($param, $arr_where);
	}
	/**
	 * delete_by_markup 
	 * 
	 * @param array $param 
	 * @param array $arr_where 
	 * @access public
	 * @return mixed
	 */
	function delete_by_markup($param=array(), $arr_where=array()){
		if (empty($arr_where) || !is_array($arr_where)){
			throw new Exception(sprintf('function: %s, table:%s parameter: arr_where must array',
						__FUNCTION__, $this->_table),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}

		if (empty($param) || !is_array($param)){
			throw new Exception(sprintf('function: %s, parameter: param  is empty or is not array.', __FUNCTION__),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}

		$new_param = array();
		foreach ($param as $k=>$v){
			if (!in_array($k, $this->_update_fields)){
				throw new Exception(sprintf('function: %s, parameter: %s  must not in fields', __FUNCTION__, $k),
						$this->config->item('parameter_err_no', 'err_no')
						);
			}
			$new_param[$k] = $v;
		}

		if (empty($new_param)){
			throw new Exception(sprintf('function: %s, parameter is emtpy', __FUNCTION__),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}
		$this->db->select($this->_primary_key);
		foreach ($arr_where as $key=>$where){
			if (is_array($where)){
				$this->db->where_in($key, $where);
			}else{
				$this->db->where($key, $where);
			}
		}
		$q = $this->get($this->_table);
		if ($q->num_rows()> 0) {
			foreach($q->result_array() as $result){
				$this->update($new_param, $result[$this->_primary_key]);
				if($this->_log->getEffectiveLevel() =='DEBUG'){
					$this->_log->push_info('%s:%s', array(__FUNCTION__, $this->db->last_query()));
				}
			}
			return TRUE;
		}else{
			throw new Exception(sprintf('update table %s error:%s id:%s', $this->_table,
						$this->db_error(), var_export($arr_where, TRUE)),
					$this->config->item('db_err_no', 'err_no')
					);
		}
	}
	/**
	 * update
	 *
	 * @param array $param
	 * @param int $id
	 * @access public
	 * @return mixed
	 */
	function update($param=array(), $id=0){
		$id = intval($id);
		if ($id <= 0){
			throw new Exception(sprintf('function: %s, table:%s parameter: id must greater than 0',
						__FUNCTION__, $this->_table),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}

		if (empty($param) || !is_array($param)){
			throw new Exception(sprintf('function: %s, parameter: param  is empty or is not array.', __FUNCTION__),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}

		$new_param = array();
		foreach ($param as $k=>$v){
			if (!in_array($k, $this->_update_fields)){
				throw new Exception(sprintf('function: %s, parameter: %s  must not in fields table:%s', __FUNCTION__, $k, $this->_table),
						$this->config->item('parameter_err_no', 'err_no')
						);
			}
			$new_param[$k] = $v;
		}

		if (empty($new_param)){
			throw new Exception(sprintf('function: %s, parameter is emtpy', __FUNCTION__),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}

		$this->db->where(sprintf('%s=%d', $this->_primary_key, $id));
		$res = $this->db->update($this->_table, $new_param);
		if ($res === FALSE){
			throw new Exception(sprintf('update table %s error:%s id:%d', $this->_table,
						$this->db_error(), $id),
					$this->config->item('db_err_no', 'err_no')
					);
		}
		$key = sprintf('%s_%d',$this->_table, $id);
		if (!$this->cache->memcached->del($key)){
			$this->_log->warn(sprintf('%s: delete key:%s from memcached failure. %s ', __FUNCTION__, $key, $this->cache->memcached->error_message()));
		}else{
			$this->_log->debug(sprintf('%s: delete key:%s from memcached ok.', __FUNCTION__, $key));
		}
		if($this->_log->getEffectiveLevel() =='DEBUG'){
			$this->_log->push_info('%s:%s', array(__FUNCTION__, $this->db->last_query()));
		}
		return $id;
	}
	/**
	 * delete_one_by_id
	 *
	 * @param mixed $id
	 * @access public
	 * @return mixed
	 */
	function delete_one_by_id($id) {
		$id = intval($id);
		if ($id <= 0) {
			throw new Exception(sprintf('function: %s, parameter: id must greater than 0', __FUNCTION__),
					$this->config->item('parameter_err_no', 'err_no')
					);

		}
		$res =  $this->db->delete($this->_table, array($this->_primary_key=>$id));
		$key = sprintf('%s_%d',$this->_table, $id);
		if (!$this->cache->memcached->del($key)){
			$this->_log->warn(sprintf('%s: delete %s from memcached failure.%s', __FUNCTION__, $key, $this->cache->memcached->error_message()));
		}else{
			$this->_log->debug(sprintf('%s: delete key:%s from memcached ok.', __FUNCTION__, $key));
		}
		if($this->_log->getEffectiveLevel() =='DEBUG'){
			$this->_log->push_info('%s:%s', array(__FUNCTION__, $this->db->last_query()));
		}
		return $res;
	}
	/**
	 * new_one
	 *
	 * @access public
	 * @return mixed
	 */
	function new_one(){
		$one = array();
		foreach ($this->_insert_fields as $field){
			$one[$field] = '';
		}
		return $one;
	}

	/**
	 * get 
	 * 
	 * @access public
	 * @return mixed
	 */
	function get(){
		return $this->db->get($this->_table);
	}
	/**
	 * getMulti 
	 * 
	 * @param array $param 
	 * @access public
	 * @return mixed
	 */
	function get_multi($param=array(), $selected=array()) {
		$new_keys= array();
		foreach($param as $id){
			$new_keys[] =  sprintf('%s_%d', $this->_table, $id);
		}
		$results = $this->cache->memcached->getMulti($new_keys);
		$keys = array_keys($results);
		$not_exists_keys = array_diff($new_keys, $keys);
		if (empty($not_exists_keys)){
			$this->_log->push_info('get results  memcached by keys %s ok.', array(implode(',',
							array_keys($results))));
		}else{
			$ids = array();
			foreach($not_exists_keys as $key){
				$ids[] = substr($key, strlen($this->_table.'_'));
			}
			if (empty($ids)) return array();
			$this->db->where_in($this->_primary_key, $ids);
			$q =   $this->db->get($this->_table);
			foreach($q->result_array() as $row) {
				$key = sprintf('%s_%d', $this->_table, $row[$this->_primary_key]);
				$results[$key] = $row;
			}

			if ($q->num_rows()>0) {
				if (!$this->cache->memcached->setMulti($results)){
					$this->_log->warn(sprintf('set keys %s to memcache failure. %s ', implode(',', array_keys($results)),
								$this->cache->memcached->error_message()));
				}
			}
		}
		//$items = array();
		//foreach($param as $k){
		//	$new_key =  sprintf('%s_%d', $this->_table, $k);
		//	if (isset($results[$new_key])){
		//		$items[$k] = $results[$new_key];
		//	}
		//}
		if($this->_log->getEffectiveLevel() =='DEBUG'){
			$this->_log->push_info('%s:%s', array(__FUNCTION__, $this->db->last_query()));
		}
        $items = array();
        $selected = is_array($selected) ? $selected :  (strpos($selected, ',') ===FALSE ? array($selected) : explode(',',  $selected)); 
        foreach($param as $k){
            $new_key =  sprintf('%s_%d', $this->_table, $k);
            if (isset($results[$new_key])){
                if ($selected){
                    foreach ($selected as $field){
                        if (!isset($results[$new_key][$field])) continue;
                        $items[$k][$field] = $results[$new_key][$field];
                    }
                }else{
                    $items[$k] = $results[$new_key];
                }
            }
        }
		return $items;

	}
	/**
	 * delete 
	 * 
	 * @param array $param 
	 * @access public
	 * @return mixed
	 */
	function delete($param=array()) {
		if (!is_array($param) || empty($param)){
			throw new Exception(sprintf('function:%s,table:%s parameter must be not empty array.', __FUNCTION__, $this->_table),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}
		$new_param = array();
		foreach($param as $k=>$v) {
			if (in_array($this->_remove_operator($k), $this->_select_fields)){
				$new_param[$k] = $v; 
			}
		}
		if (empty($new_param)){
			throw new Exception(sprintf('function:%s,table:%s new param must be not empty array.', __FUNCTION__, $this->_table),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}

		return $this->db->delete($this->_table, $new_param);
	}
	/**
	 * fetch 
	 * 
	 * @param array $param 
	 * @access public
	 * @return mixed
	 */
	function fetch($param=array()) {
		if (!is_array($param) || empty($param)){
			throw new Exception(sprintf('function:%s,table:%s parameter must be not empty array.', __FUNCTION__, $this->_table),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}
		$new_param = array();
		foreach($param as $k=>$v) {
			if (in_array($k, $this->_select_fields)){
				$new_param[$k] = $v; 
			}
		}
		if (empty($new_param)){
			throw new Exception(sprintf('function:%s,table:%s new param must be not empty array.', __FUNCTION__, $this->_table),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}

		$q = $this->db->get_where($this->_table, $new_param);
		return $q->result_array();
	}
	protected function _is_sub_array($arr, $arr_set){
		$is_sub_array = FALSE;
		$new_arr = array_values($arr);
		foreach($arr_set as $k =>$v){
			$new_v = array_values($v);
			if (sizeof($new_v) < sizeof($new_arr)) continue;
			for($i=0; $i<sizeof($new_arr); $i++) {
				if ($new_v[$i]!== $new_arr[$i]){
					break;
				}   
			}   
			if ($i==sizeof($new_arr)) {
				$is_sub_array = TRUE;
				break;
			}   
		}   
		return $is_sub_array ;
	}
	/**
	 * __call 
	 * get_by_user_name_first(a,b) get_by_phone(array('','','')) get_by_phone__user_id_first() 
	 * get_by_user_id('1', array('limit'=>9));
	 * @param mixed $name 
	 * @param mixed $args 
	 * @access protected
	 * @return mixed
	 */
	function __call($name, $args) {
		$first = FALSE;
		if (strpos($name, 'get')===0){
			$field = substr($name, 7);
			if (substr($name,-6,strlen($field)) == '_first' ){
				$field = str_replace('_first', '', $field);
				$first = TRUE;
				$this->db->limit(1);
			}
			$this->db->select($this->_primary_key);
			// underscore _ as AND in sql            
			if(strpos($field, '__')!==false){
				$field = explode('__', $field);
			}
			if(is_string($field)){
				if ($this->_is_sub_array(array($field), $this->_index_fields)){
					is_array($args[0])? $this->db->where_in($field, $args[0]): $this->db->where($field, $args[0]);
					isset($args[1]['limit']) ? $this->db->limit($args[1]['limit']): '';
					isset($args[1]['ordersort']) ? $this->db->order_by($args[1]['ordersort']): '';
					isset($args[1]['select']) ? $this->db->select($args[1]['select']): $this->db->select($this->_primary_key);
					$first = ($first == FALSE && isset($args[1]['limit']) && $args[1]['limit'] ==1) ? TRUE : $first;
				}else{
					throw new Exception(sprintf('function:%s field:%s must by in index fields', $name, $field, $this->_table),
							$this->config->item('parameter_err_no', 'err_no')
							);
				}
			}else{
				if ($this->_is_sub_array($field, $this->_index_fields)){
					$i = 0;
					foreach($field as $f){
						is_array($args[$i])? $this->db->where_in($f, $args[$i]): $this->db->where($f, $args[$i]);
						$i++;
					}

					if (sizeof($args) > $i){
						isset($args[$i]['limit']) ? $this->db->limit($args[$i]['limit']): '';
						isset($args[$i]['ordersort']) ? $this->db->order_by($args[$i]['ordersort']): '';
						isset($args[$i]['select']) ? $this->db->select($args[$i]['select']) : $this->db->select($this->_primary_key);
						$first = ($first == FALSE && isset($args[$i]['limit']) && $args[$i]['limit'] ==1) ? TRUE : $first;
					}
				}else{
					throw new Exception(sprintf('function:%s field:%s must by in index fields', $name, implode(',',$field), $this->_table),
							$this->config->item('parameter_err_no', 'err_no')
							);
				}
			}
			$q =  $this->get($this->_table);
			if($this->_log->getEffectiveLevel() =='DEBUG'){
				$this->_log->push_info('%s:%s', array(__FUNCTION__, $this->db->last_query()));
			}
			return $first ? $q->first_row('array') : $q->result_array();
		}
		throw new Exception(sprintf('function:%s must be begin with get_by_,table:%s', $name, $this->_table),
				$this->config->item('parameter_err_no', 'err_no')
				);
	}
	function db_error() {
		return $this->db->_error_message();
	}
	protected function _remove_operator($str){
		preg_match_all("/([a-zA-Z0-9_]+)/i", $str, $matches);
		return $matches[1][0];
	}

}
// END Dao Class

/* End of file Dao.php */
/* Location: ./system/core/Dao.php */
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
