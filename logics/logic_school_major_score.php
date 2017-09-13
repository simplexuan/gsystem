<?php 
class Logic_school_major_score extends Gsystem_logic {
	/**
	 * __construct 
	 * 
	 * @access protected
	 * @return mixed
	 */
	function __construct() {
		parent::__construct();
	}

	/*
     * 按分数，省份id，是否985，211查询能录取的学校，返回学校详情列表
     */
	public function get_school_list($param){
		if(empty($param['region_id'])) throw new Exception('region_id不能为空', 100001);
		if(empty($param['score'])) throw new Exception('score不能为空', 100001);

		$type = isset($param['type'])? intval($param['type']): -1;
		$studenttype_id = isset($param['studenttype_id'])? intval($param['studenttype_id']): 1;
		$order = isset($param['order'])? intval($param['order']): 3;
		$page = isset($param['page'])? intval($param['page']): 1;
		$page_size = isset($param['page_size'])? intval($param['page_size']): 10;

		return $this->model('Model_school_major_score')->getSchoolList(intval($param['region_id']), intval($param['score']), $type, $studenttype_id, $order, $page, $page_size);
	}

	/*
     * 学校id，分数，省份id查询能录取的专业个数，和专业详情列表
     */
	public function get_major_list($param){
		if(empty($param['school_id'])) throw new Exception('school_id不能为空', 100001);

		$score = isset($param['score']) ? intval($param['score']): '-10000000';
		$region_id = isset($param['region_id']) ? intval($param['region_id']): '-10000000';
		$studenttype_id = isset($param['studenttype_id'])? intval($param['studenttype_id']): 1;
		return $this->model('Model_school_major_score')->getMajorList($region_id, $score, intval($param['school_id']), $studenttype_id);
	}

	/*
     * 按分数，省份id，是否985，211查询能录取的学校，返回学校详情列表
     */
	public function get_schools_by_majorid($param){
		if(empty($param['major_id'])) throw new Exception('major_id不能为空', 100001);

		$order = isset($param['order'])? intval($param['order']): 1;
		$page = isset($param['page'])? intval($param['page']): 1;
		$page_size = isset($param['page_size'])? intval($param['page_size']): 10;

		return $this->model('Model_school_major_score')->getSchoolsByMajorId(intval($param['major_id']), $order, $page, $page_size);
	}


	/*
	 * 获取历年学校专业分数数据
	 */
	public function get_school_major_scores($param){
		if(empty($param['school_id'])) throw new Exception('school_id不能为空', 100001);
		if(empty($param['major_id'])) throw new Exception('major_id不能为空', 100001);

		$region_id 		= isset($param['region_id'])? intval($param['region_id']): '';
		$studenttype_id	= isset($param['studenttype_id'])? intval($param['studenttype_id']): '-1';
		$year 			= isset($param['year'])? intval($param['year']): 2012;

		return $this->model('Model_school_major_score')->getSchoolMajorScores(intval($param['school_id']), intval($param['major_id']), $region_id, $studenttype_id, $year);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
