<?php 
class Logic_corporation_logo extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_corporation_logo'), $func), $args);
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
			$selected  = $selected + array('corporation_logo'=>array())
				;
		}

		$c['corporation_logo'] = $this->model('Model_corporation_logo')->fetch_one_by_id($id, $selected['corporation_logo']);
		if (empty($c['corporation_logo']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
		} 


		return $c; 
	}
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$corporation_logos = $this->model('Model_corporation_logo')->search($param, $param['page'], $param['pagesize']);
		$corporation_logos['results'] = array_values($corporation_logos['results']);
		return $corporation_logos;
	}
    /**
     * 返回logo给2C，ToB的logo优先于BDList
     */
    function get_for_2c($param) {
        $return = array();
        $ids = is_array($param['ids']) ? $param['ids'] : explode(',', $param['ids']);
        foreach($ids as $id) {
            if(empty($id) || !is_numeric($id)) {
                continue;
            }
            $return[$id] = $this->get_logo($id);
        }

        return $return;
    }

    /**
     * 目前公司关系树为3层，从叶子节点往上遍历查找LOGO
     * 若ID为第三层公司ID，会取同一个第二层父节点的兄弟节点LOGO，也会取到所有第二层的节点和第一层根节点的LOGO，没有则返回空字符串
     * 若ID为第二层公司ID，会取所有第二层的兄弟节点和第一层父节点的LOGO，没有则返回空字符串
     * 若ID为第一层公司ID，只取自己的LOGO，没有就返回空字符串
     */
    function get_logo ($id) {
        try {
            $img_path = $this->config->item('img_path');

            $self_baseinfo = $this->model('Model_corporation_baseinfo')->search_one(array('corporation_id' => $id, 'type_id' => 1));
            if (! empty($self_baseinfo['logo'])) {
                return sprintf('%s%s', $img_path, $self_baseinfo['logo']);
            }

            $self_corporation = $this->model('Model_corporation')->fetch_one_by_id($id);
            $parent_id = $self_corporation['parent_id'];
            if (0 != $parent_id && $id != $parent_id) {
                $sibling_corporations = $this->model('Model_corporation')->search_all(array('parent_id' => $parent_id));
                unset($sibling_corporations[$id]);
                foreach ($sibling_corporations as $sibling_corporation) {
                    $sibling_baseinfo = $this->model('Model_corporation_baseinfo')->search_one(array('corporation_id' => $sibling_corporation['id'], 'type_id' => 1));
                    if (! empty($sibling_baseinfo['logo'])) {
                        return sprintf('%s%s', $img_path, $sibling_baseinfo['logo']);
                    }
                }
                return $this->get_logo($parent_id);
            }
        } catch (Exception $e) {
        }
        return '';
    }
}
