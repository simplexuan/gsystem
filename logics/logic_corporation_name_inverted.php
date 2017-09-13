<?php 
class Logic_corporation_name_inverted extends Gsystem_logic {
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
        return call_user_func_array(array($this->model('Model_corporation_name_inverted'), $func), $args);
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
		return $this->model('Model_corporation_name_inverted') ->save($adjust_data);
	}
    /**
     * @return mixed
     */
    protected function _adjust(&$post_data) {
                                		$new_one = $this->model('Model_corporation_name_inverted')->new_one();
		$post_data['corporation_name_inverted'] = array_merge($new_one, array_intersect_key($post_data['corporation_name_inverted'], $new_one));
        return $post_data;
    }
		function delete($post_data) {
		return $this->model('Model_corporation_name_inverted') ->delete_one_by_id($post_data['id'], $this->uid[getmypid()]);
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
			$selected  = $selected + array('corporation_name_inverted'=>array())
								;
		}

        $c['corporation_name_inverted'] = $this->model('Model_corporation_name_inverted')->fetch_one_by_id($id, $selected['corporation_name_inverted']);
		if (empty($c['corporation_name_inverted']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
						__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
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
		$c['corporation_name_inverted'] = $this->model('Model_corporation_name_inverted')->fetch_one_by_id($id);
		if (empty($c['corporation_name_inverted']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
						__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
		} 
                
        return $c; 
    }
		function search($param, $page, $pagesize){
		$param['page']     = $page;
		$param['pagesize'] = $pagesize;
		$corporation_name_inverteds = $this->model('Model_corporation_name_inverted')->search($param, $page, $pagesize);
		$corporation_name_inverteds['results'] = array_values($corporation_name_inverteds['results']);
		return $corporation_name_inverteds;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
