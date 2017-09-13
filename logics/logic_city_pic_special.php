<?php

class Logic_city_pic_special extends Gsystem_logic
{
	private $_table_name = 'city_pic_special';

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
		return call_user_func_array(array($this->model('Model_city_pic_special'), $func), $args);
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
		$id = isset($post_data[$this->_table_name]['id'])?intval($post_data[$this->_table_name]['id']):0;
		$start_time = isset($post_data[$this->_table_name]['start_time']) ? strval($post_data[$this->_table_name]['start_time']) : '';
		$end_time = isset($post_data[$this->_table_name]['end_time']) ? strval($post_data[$this->_table_name]['end_time']) : '';
		if(empty($start_time) || empty($end_time))
		{
			throw new Exception('参数缺少', 400000);
		}
		else
		{
			$start_res = $this->model("Model_city_pic_special")->search_one(array('start_time<='=>$start_time,'end_time>='=>$start_time,'id!='=>$id));
			if(empty($start_res))
			{
				$end_res = $this->model("Model_city_pic_special")->search_one(array('start_time<='=>$end_time,'end_time>='=>$end_time,'id!='=>$id));
				if(!empty($end_res))
				{
					throw new Exception('结束时间已经设定', 400002);
				}
			}
			else
			{
				throw new Exception('开始时间已经设定', 400001);
			}
		}

		$post_data[$this->_table_name]['updated_at'] = date('Y-m-d H:i:s');
		return $this->model('Model_city_pic_special')->save($post_data);
	}

	function delete($post_data)
	{
		return $this->model('Model_city_pic_special')->delete_one_by_id($post_data['id'], $this->uid[getmypid()]);
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
			$selected = $selected + array('city_pic_special' => array())
			;
		}

		$c['city_pic_special'] = $this->model('Model_city_pic_special')->fetch_one_by_id($id, $selected['city_pic_special']);
		if (empty($c['city_pic_special']))
		{
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.', __FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));
		}


		return $c;
	}


	function search($param)
	{
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$city_pic_specials = $this->model('Model_city_pic_special')->search($param,$param['page'],$param['pagesize']);
		$city_pic_specials['results'] = array_values($city_pic_specials['results']);
		return $city_pic_specials;
	}

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
