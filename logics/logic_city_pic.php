<?php

class Logic_city_pic extends Gsystem_logic
{

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
	 * detail
	 *
	 * @param mixed $param
	 * @access public
	 * @return mixed
	 */
	function detail($param)
	{
		$city_id = isset($param['city_id']) ? intval($param['city_id']) : 0;
		empty($param['date']) && $param['date'] = date('Y-m-d');
		$uri = $this->config->item('img_path');

		//查询是否有全局特殊图片,有则返回
		$response_special = $this->model('Model_city_pic_special')->search_one(array('start_time<=' => $param['date'], 'end_time>=' => $param['date']));

		$result = array();

		if (!empty($response_special))
		{
			$result['flag'] = 1;
			$result['results'] = array($response_special);
		}
		else
		{
			$response_normal = $this->model('Model_city_pic_normal')->search_all(array('city_id'=>$city_id));
			if(!empty($response_normal))
			{
				$result['flag'] = 0;
				$result['results'] = array_values($response_normal);
			}
		}
		if(!empty($result))
		{
			foreach($result['results'] as $key=>$one)
			{
				$result['results'][$key]['pic'] = rtrim($uri, '/') . $one['pic'];
			}
		}
		else
		{
			$result['flag'] = 0;
			$result['results'] = array();
		}

		return $result;
	}

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
