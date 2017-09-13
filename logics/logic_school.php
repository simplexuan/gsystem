<?php 
class Logic_school extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_school'), $func), $args);
	}
	/**
	 * detail 
	 * 
	 * @param mixed $id 
	 * @access public
	 * @return mixed
	 */
	function detail($param) {
		$c         = array();
		$id        = $param['id'];
		$selected  = isset($param['selected']) ? parse_selected($param['selected']) : array();

		if (!$selected){
			$selected  = $selected + array('school'=>array())
				;
		}

		$c['school'] = $this->model('Model_school')->fetch_one_by_id($id, $selected['school']);
		if (empty($c['school']) ){
			throw new Exception(sprintf('%s: The school id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
		} 


		return $c; 
	}
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 100 : min(abs(intval($param['pagesize'])), 100);
		$param['is_deleted'] = 'N';
		$schools = $this->model('Model_school')->search($param, $param['page'], $param['pagesize']);
		$schools['results'] = array_values($schools['results']);
		return $schools;
	}

	/**
	 * 实现ToB的ToBusiness_School_load
	 */
	function load($param) {
		if(empty($param['id']) || !is_numeric($param['id'])) {
			throw new Exception('no school', 10001);
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		try {
			return $this->format_data($this->model('Model_school')->fetch_one_by_id($param['id']), $select_field);
		} catch (Exception $e) {
			throw new Exception('no school', 10001);
		}
	}
	/**
	 * 实现ToB的ToBusiness_School_loadMulti
	 */
	function loadMulti($param) {
		$return = array();
		if(empty($param['ids'])) {
			return $return;
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		$ids = is_array($param['ids']) ? $param['ids'] : explode(',', $param['ids']);
		foreach($ids as $id) {
			if(empty($id) || !is_numeric($id)) {
				continue;
			}
			try {
				$return[$id] = $this->format_data_multi($this->model('Model_school')->fetch_one_by_id($id), $select_field);
			} catch (Exception $e) {
				continue;
			}
		}

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_School_loadByName
	 */
	function loadByName($param) {
		if(empty($param['name'])) {
			throw new Exception('no school', 10001);
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];

		$rs = $this->model('Model_school')->search(array('name_cn'=>$param['name'],'is_deleted'=>'N'), 1, 1000);

		foreach($rs['results'] as $one) {
			//只返回一个
			return $this->format_data($one, $select_field);
		}

		throw new Exception('no school', 10001);
	}
	/**
	 * 实现ToB的ToBusiness_School_hasDetail
	 */
    public function hasDetail($param) {
		$ids = array();
		$is_one = true;
		$return = array();
		if(empty($param['id']) || !is_numeric($param['id'])) {
			if(!empty($param['ids'])) {
				$ids = is_array($param['ids']) ? $param['ids'] : explode(',', $param['ids']);
				$is_one = false;
			} else {
				return false;
			}
		} else {
			$ids = array($param['id']);
		}
		$rs_es = $this->model('Model_school')->get_multi($ids);
		foreach($rs_es as $rs) {
			$return[$rs['id']] = $rs['status'];
		}

		return $is_one ? reset($return) : $return;
	}
	/**
	 * 格式化loadMulti的单条数据
	 */
	protected function format_data_multi($data, $select_field = array()) {
		if(!empty($data)) {
			$select_field = is_array($select_field) ? $select_field : explode(',',$select_field);

			$data['parent']	= $data['parent_id'];
			$data['name']	= $data['name_cn'];
			$data['ename']	= $data['name_en'];
			$data['createtime'] = substr($data['created_at'], 0, 10);
			unset($data['parent_id'], $data['name_cn'], $data['name_en'], $data['created_at']);

			if(!empty($select_field)) {
				foreach($data as $k=>$v) {
					if(!in_array($k, $select_field)) {
						unset($data[$k]);
					}
				}
			}

			return (object)$data;
		} else {
			return new stdclass;
		}
	}
    //数据格式化
    protected function format_data($data, $select_field = array()) {
        $return = array();
        if (!empty($data)) {            
			$select_field = is_array($select_field) ? $select_field : explode(',',$select_field);
            //格式化名片信息
            $baseinfos = unserialize($data['baseinfo'] ? $data['baseinfo'] : 'a:0:{}');
            $baseinfos = $this->baseinfo($baseinfos);
            foreach ($baseinfos as $ba=>$base) {
                $baseinfo[] = array(
                    'key'   => strip_tags($ba),//过滤掉$key的html标签
                    'value' => $base,
                );
            }
            //扩展信息
            $extendinfos = unserialize($data['extendinfo'] ? $data['extendinfo'] : 'a:0:{}');
            foreach ($extendinfos as $ex=>$v) {
                $extendinfo[] = array(
                    'key'   => $ex,
                    'value' => $v
                );
            }
            $extend = $data['introduction'];
            $return['id']         = $data['id'];
            $return['name']       = $data['name_cn'];
            $return['logo']       = '';//学校logo为空
            $return['mingpian']   = array(
                'introduction'    => !empty($extend) ? $extend : array(),
                'baseinfo'        => !empty($baseinfo) ? $baseinfo : array(),
            );
            $return['extendinfo']  = !empty($extendinfo) ? $extendinfo : array();
            $return['mid']         = $data['mid'];

			if(!empty($select_field)) {
				foreach($return as $k=>$v) {
					if(!in_array($k, $select_field)) {
						unset($return[$k]);
					}
				}
			}
        }
        
        return (object)$return;
    }
    protected function baseinfo($data) {
        $datas = array();
        foreach ($data as $k=>$v) {
            $datas[$k] = str_replace(array($k,"："),'',$v);
        }
        return $datas;
    }


	/*
     * 根据学校ID获取学校详情
     */
	public function get_detail_by_schoolid($param){
		if(empty($param['id'])) throw new Exception('学校ID不能为空', 100001);

		return $this->model('Model_school')->getDetailBySchoolId(intval($param['id']));
	}


	/*
     * 通过文字，联想出相关学校，返回学校id，学校名字，是否985。211 全国排名等学校相关信息
     */
	public function get_list_by_likename($param){
		if(empty($param['name'])) throw new Exception('name不能为空', 100001);

		$region_id 	= isset($param['region_id'])? intval($param['region_id']): -1;
		$type 		= isset($param['type'])? intval($param['type']): -1;
		$order 		= isset($param['order'])? intval($param['order']): 3;
		$page 		= isset($param['page'])? intval($param['page']): 1;
		$page_size 	= isset($param['page_size'])? intval($param['page_size']): 10;

		return $this->model('Model_school')->getListByLikename($param['name'],$region_id, $type, $order, $page, $page_size);
	}

	/*
     * 添加学校
     */
	public function add($param){
		if(empty($param)) throw new Exception('参数不能为空', 100001);

		return $this->model('Model_school')->add($param);
	}

	/*
     * 修改学校
     */
	public function update($param){
		if(empty($param)) throw new Exception('参数不能为空', 100001);

		return $this->model('Model_school')->update($param);
	}

	/*
     * 删除学校
     */
	public function delete($param){
		if(empty($param)) throw new Exception('参数不能为空', 100001);
		if(empty($param['id'])) throw new Exception('ID不能为空', 100001);

		return $this->model('Model_school')->delete(intval($param['id']));
	}

	/*
	 * 获取历年学校分数数据
	 */
	public function get_school_scores($param){
		if(empty($param['school_id'])) throw new Exception('school_id不能为空', 100001);

		$region_id 		= isset($param['region_id'])? intval($param['region_id']): '';
		$studenttype_id	= isset($param['studenttype_id'])? intval($param['studenttype_id']): '-1';
		$year 			= isset($param['year'])? intval($param['year']): 2012;

		return $this->model('Model_school')->getSchoolScores(intval($param['school_id']), $region_id, $studenttype_id, $year);
	}

	/*
	 * 获取历年省控分数数据
	 */
	public function get_province_scores($param){
		$region_id 		= isset($param['region_id'])? intval($param['region_id']): '';
		$studenttype_id	= isset($param['studenttype_id'])? intval($param['studenttype_id']): '-1';
		$batch_id 		= isset($param['batch_id'])? intval($param['batch_id']): '-1';

		return $this->model('Model_school')->getProvinceScores($region_id, $studenttype_id, $batch_id);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
