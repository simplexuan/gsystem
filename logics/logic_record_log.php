<?php

class Logic_record_log extends Gsystem_logic {

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
		return call_user_func_array(array($this->model('Model_record_log'), $func), $args);
	}

	/**
	 * save
	 *
	 * @param mixed $post_data
	 * @access public
	 * @return mixed
	 */
	function save($post_data) {
		$adjust_data = $this->_adjust($post_data);
		return $this->model('Model_record_log')->save($adjust_data);
	}

	function delete($post_data) {
		return $this->model('Model_record_log')->delete_one_by_id($post_data['id'], $this->uid[getmypid()]);
	}

	/**
	 * @return mixed
	 */
	protected function _adjust(&$post_data) {
		$post_data['record_log']['old_data'] = json_encode($post_data['record_log']['old_data']);
		$post_data['record_log']['new_data'] = json_encode($post_data['record_log']['new_data']);
		$post_data['record_log']['is_deleted'] = 'N';
		$post_data['record_log']['updated_at'] = date('Y-m-d H:i:s');
		return $post_data;
	}

	/**
	 * detail
	 *
	 * @param mixed $id
	 * @access public
	 * @return mixed
	 */
	function detail($param) {
		$c = array();
		$id = $param['id'];
		$selected = isset($param['selected']) ? parse_selected($param['selected']) : array();
		if (!$selected) {
			$selected = $selected + array('record_log' => array())
			;
		}

		$c['record_log'] = $this->model('Model_record_log')->fetch_one_by_id($id, $selected['record_log']);
		if (empty($c['record_log'])) {
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.', __FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));
		}


		return $c;
	}

	/**
	 * edit
	 *
	 * @param mixed $id
	 * @access public
	 * @return mixed
	 */
	function edit($id) {
		$c = array();
		$c['record_log'] = $this->model('Model_record_log')->fetch_one_by_id($id);
		if (empty($c['record_log'])) {
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.', __FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));
		}

		return $c;
	}

	function search($param, $page, $pagesize) {
		$param['_ft_'] = 'edps';
		$param['page'] = $page;
		$param['pagesize'] = $pagesize;
		$record_logs = $this->model('Model_record_log')->search($param, $page, $pagesize);
		$record_logs['results'] = array_values($record_logs['results']);
		return $record_logs;
	}

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
