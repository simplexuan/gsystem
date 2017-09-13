<?php

class Logic_city_pic_normal extends Gsystem_logic
{

	private $_table_name = 'city_pic_normal';

	/**
	 * __construct
	 *
	 * @access protected
	 * @return mixed
	 */
	function __construct()
	{
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
	function __call($func, $args)
	{
		return call_user_func_array(array($this->model('Model_city_pic_normal'), $func), $args);
	}

	/**
	 * save
	 *
	 * @param mixed $post_data
	 * @access public
	 * @return mixed
	 */
	function save($post_data)
	{
		$post_data[$this->_table_name]['updated_at'] = date('Y-m-d H:i:s');
		return $this->model('Model_city_pic_normal')->save($post_data);
	}

	function delete($post_data)
	{
		return $this->model('Model_city_pic_normal')->delete_one_by_id($post_data['id'], $this->uid[getmypid()]);
	}

	/**
	 * detail
	 *
	 * @param mixed $id
	 * @access public
	 * @return mixed
	 */
	function detail($param)
	{
		$c = array();
		$id = $param['id'];
		$selected = isset($param['selected']) ? parse_selected($param['selected']) : array();
		if (!$selected)
		{
			$selected = $selected + array('city_pic_normal' => array())
			;
		}

		$c['city_pic_normal'] = $this->model('Model_city_pic_normal')->fetch_one_by_id($id, $selected['city_pic_normal']);
		if (empty($c['city_pic_normal']))
		{
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.', __FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));
		}


		return $c;
	}

	function search($param)
	{
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$city_pic_normals = $this->model('Model_city_pic_normal')->search($param,$param['page'], $param['pagesize']);
		$city_pic_normals['results'] =  array_values($city_pic_normals['results']);
		return $city_pic_normals;
	}

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
