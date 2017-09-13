<?php 
class Logic_taxonomy extends Gsystem_logic {
	private $taxonomy = array(
		'nature'	=> 'corporation_nature',
		'size'		=> 'corporation_size',
		'maturity'	=> 'corporation_maturity',
		'importance'=> 'corporation_importance',
		'emploayway'=> 'corporation_rc',
		'title'		=> 'title_category'
	);
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
		return call_user_func_array(array($this->model('Model_function'), $func), $args);
	}
	/**
	 * 实现ToB的ToBusiness_Taxonomy_load
	 */
	function load($param) {
		if(empty($param['tid']) || !is_numeric($param['tid']) || empty($param['bundle']) || empty($this->taxonomy[$param['bundle']])) {
			throw new Exception('Can not found this item', 10001);
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];

		try {
			$rs = $this->model('Model_' . $this->taxonomy[$param['bundle']])->fetch_one_by_id($param['tid']);
			$rs['tid'] = $rs['id'];

			return $this->select_field($rs, $select_field);
		} catch (Exception $e) {
			throw new Exception('Can not found this item', 10001);
		}
	}
	/**
	 * 实现ToB的ToBusiness_Taxonomy_loadMulti
	 */
	function loadMulti($param) {
		$return = array();
		if(empty($param['bundle']) || empty($this->taxonomy[$param['bundle']])) {
			throw new Exception('Can not found this item', 10001);
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];

		if(!empty($param['tids'])) {
			$tids = is_array($param['tids']) ? $param['tids'] : explode(',', $param['tids']);

			foreach($tids as $tid) {
				if(empty($tid) || !is_numeric($tid)) {
					continue;
				}
				try {
					$rs = $this->model('Model_' . $this->taxonomy[$param['bundle']])->fetch_one_by_id($tid);
					$rs['tid'] = $rs['id'];
					$return[$tid] = $this->select_field($rs, $select_field);
				} catch (Exception $e) {
					continue;
				}
			}

			return $return;
		} else {
			$rs = $this->model('Model_' . $this->taxonomy[$param['bundle']])->search_all(array());
			foreach($rs as $one) {
				$one['tid'] = $one['id'];
				$return[$one['id']] = $this->select_field($one, $select_field);
			}
			ksort($return);

			return $return;
		}
	}
	/**
	 * 实现ToB的ToBusiness_Taxonomy_loadAll
	 */
	function loadAll($param) {
		$return = array();
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];

		if(!empty($param['bundle'])) {

			return $this->loadMulti(array('bundle'=>$param['bundle'], 'select_field'=>$select_field));
		} else {
			foreach($this->taxonomy as $type=>$model) {
				$return[$type] = $this->loadMulti(array('bundle'=>$type, 'select_field'=>$select_field));
			}

			return $return;
		}
	}
	/**
	 * 返回指定数据
	 */
	function select_field($rs, $select_field = array()) {
		$select_field = is_array($select_field) ? $select_field : explode(',',$select_field);
		if(!empty($select_field)) {
			foreach($rs as $k=>$v) {
				if(!in_array($k, $select_field)) {
					unset($rs[$k]);
				}
			}
		}

		return (object)$rs;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
