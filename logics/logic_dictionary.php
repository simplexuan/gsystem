<?php 
class Logic_dictionary extends Gsystem_logic {
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
        return call_user_func_array(array($this->model('Model_dictionary'), $func), $args);
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
        return $this->model('Model_dictionary') ->save($adjust_data);
    }
    /**
     * @return mixed
     */
    protected function _adjust(&$post_data) {
        $new_one = $this->model('Model_dictionary')->new_one();
        $post_data['dictionary'] = array_merge($new_one, array_intersect_key($post_data['dictionary'], $new_one));
        return $post_data;
    }
    function search($param){
        $param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
        $param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
        $dictionarys = $this->model('Model_dictionary')->search($param, $param['page'], $param['pagesize']);
        $dictionarys['results'] = array_values($dictionarys['results']);
        return $dictionarys;
    }

    function all($params) {
		return $this->config->item('dictionaries');
    }
}
