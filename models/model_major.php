<?php
/**
 * Model_school
 *
 * @uses Gsystem
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author jiqing Sun
 * @license
 */
class Model_major extends Gsystem_model {

	protected $table = 'majors';

	/*
	 * 添加
	 * @param array $param
	 * return array
	 */
	public function add($param){
		if(empty($param)) throw new Exception('参数不能为空，请核实', 100001);
		if(empty($param['name'])) throw new Exception('名称不能为空，请核实', 100001);

		$time = date('Y-m-d H:i:s');
		$data = [
			'name' 			=> $param['name'],
			'created_at' 	=> $time,
			'updated_at'	=> $time,
		];

		if(isset($param['parent_id'])) 		$data['parent_id'] = intval($param['parent_id']);
		if(isset($param['depth'])) 			$data['depth'] = intval($param['depth']);

		$this->db->insert($this->table, $data);
		$insert_id = $this->db->insert_id();
		if($insert_id) return [ 'id' => $insert_id];
		throw new Exception('添加失败', 100017);
	}

	/*
	 * 验证ID是否已存在
	 * @param int $id 学校ID
	 * return int
	 */
	public function checkId($id){
		$this->db->from($this->table);

		if(empty($id)) return false;

		$this->db->where("is_deleted", 'N');
		$this->db->where("id", $id);

		return $this->db->count_all_results();
	}


	/*
	 * 修改
	 * @param array $param
	 * return array
	 */
	public function update($param){
		if(empty($param)) throw new Exception('参数不能为空，请核实', 100001);
		if(empty($param['id'])) throw new Exception('ID不能为空，请核实', 100001);
		if(empty($param['name'])) throw new Exception('名称不能为空，请核实', 100001);

		if(!$this->checkId($param['id']))  throw new Exception('ID不存在，请核实', 100001);

		$data = [
			'name' 			=> $param['name'],
			'updated_at' 	=> date('Y-m-d H:i:s'),
		];

		if(isset($param['parent_id'])) 		$data['parent_id'] = intval($param['parent_id']);
		if(isset($param['depth'])) 			$data['depth'] = intval($param['depth']);

		$this->db->where('id', $param['id']);
		$this->db->update($this->table, $data);

		return [];
	}

	/*
	 * 删除
	 * @param int $id 学校ID
	 * return array
	 */
	public function delete($id){
		if(empty($id)) throw new Exception('ID不能为空，请核实', 100001);

		if(!$this->checkId($id))  throw new Exception('ID不存在，请核实', 100001);

		$data = [
			'is_deleted' => 'Y',
			'updated_at' => date('Y-m-d H:i:s'),
		];

		$this->db->where('id', $id);
		$this->db->update($this->table, $data);

		return [];
	}


	/*
	 * 详情
	 * @param int $id 专业ID
	 * return array
	 */
	public function detail($id){
		if(empty($id)) throw new Exception('ID不能为空，请核实', 100001);

		$result = [];
		$query = $this->db->get_where($this->table, array('id' =>$id));
		if ($query->num_rows() > 0)
		{
			$result =  $query->row_array();
		}
		return $result;
	}


	/*
	 * 根据名称模糊搜索
	 * @param string $name 名称
	 * return array
	 */
	public function searchByLikeName($name){
		if(empty($name)) throw new Exception('名称不能为空，请核实', 100001);

		$this->db->where("is_deleted", 'N');
		$this->db->like("name", $name);

		$query = $this->db->get($this->table);
		$list = [];
		if ($query->num_rows() > 0)
		{
			$list = $query->result_array();
		}
		return [ 'total' => count($list), 'list' => $list];
	}
}
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
