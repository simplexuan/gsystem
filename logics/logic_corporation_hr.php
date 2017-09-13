<?php 
class Logic_corporation_hr extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_corporation_hr'), $func), $args);
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
			$selected  = $selected + array('corporation_hr'=>array())
				;
		}

		$c['corporation_hr'] = $this->model('Model_corporation_hr')->fetch_one_by_id($id, $selected['corporation_hr']);
		if (empty($c['corporation_hr']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
		} 


		return $c; 
	}
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$corporation_hrs = $this->model('Model_corporation_hr')->search($param, $param['page'], $param['pagesize']);
		$corporation_hrs['results'] = array_values($corporation_hrs['results']);
		return $corporation_hrs;
	}
	/**
	 * 根据公司ID获取公司hr名字和email
	 */
	function get_by_corporation_id($param) {
		$return = array();
		if(empty($param['ids'])) {
			return $return;
		}
		$ids = is_array($param['ids']) ? $param['ids'] : explode(',', $param['ids']);
		foreach($ids as $id) {
			if(empty($id) || !is_numeric($id)) {
				continue;
			}

			$rs = $this->model('Model_corporation_hr')->search_one(array('corporation_id'=>$id));
			if(empty($rs)) {
				$return[$id] = array();
			} else {
				$return[$id] = $rs;
			}
		}

		return $return;
	}
	/**
	 * 根据公司名字获取公司hr名字和email
	 */
	function get_by_corporation_name ($param) {
		if(!empty($param['name'])) {
			//$select_field = empty($param['select_field']) ? array() : $param['select_field'];
			$rs = $this->model('Model_corporation')->search_one(array('name'=>$param['name']));
			if($rs) {
				$rs = $this->model('Model_corporation_hr')->search_one(array('corporation_id'=>$rs['id']));
				if($rs) {
					return $rs;
				}
			}
		}

		return array();
	}

    //保存/更新
    function save($param)
    {
        //校验数据
		$id = isset($param['corporation_hr']['id']) ? intval($param['corporation_hr']['id']) : 0;
        $name = isset($param['corporation_hr']['name']) ? strval($param['corporation_hr']['name']) : '' ;
        $corporation_id = isset($param['corporation_hr']['corporation_id']) ? intval($param['corporation_hr']['corporation_id']) :0;
        $email = isset($param['corporation_hr']['email']) ? strval($param['corporation_hr']['email']) : null;

        $result_corporation = array();
        $result_corporation = $this->model('Model_corporation')->fetch_one_by_id($corporation_id);

        $this->load->helper('email');
        if(!valid_email($email))
        {
            throw new Exception('email is not invalid',405);
        }

        //删除之前所有的corporation_id关联数据
        if($id <= 0 ) //新增
        {
            $this->model('Model_corporation_hr')->delete(array('corporation_id'=>$corporation_id));
        }
        else
        {
            //查询corporation_id是否更新.若更新则删除新corporation_id关联数据
            $result_corporation_hr = $this->model('Model_corporation_hr')->fetch_one_by_id($id);
            if($result_corporation_hr['corporation_id'] != $corporation_id)
            {
                $this->model('Model_corporation_hr')->delete(array('corporation_id'=>$corporation_id));
            }
        }
        $param['corporation_hr']['is_deleted'] = 'N';
        $param['corporation_hr']['updated_at'] = date('Y-m-d H:i:s');
        return  $this->model('Model_corporation_hr')->save($param);
    }

    //删除
    function delete_one($param)
    {
        $id = $param['corporation_hr']['id'];
        return  $this->model('Model_corporation_hr')->delete_one_by_id($id);
    }


}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
