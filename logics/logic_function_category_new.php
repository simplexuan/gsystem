<?php 
class Logic_function_category_new extends Gsystem_logic {
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
        return call_user_func_array(array($this->model('Model_function_category_new'), $func), $args);
    }
    /**
     * detail
     *
     * @param mixed $id
     * @access public
     * @return mixed
     */
    function detail($param) {
        return [];
    }

    function search($param, $page=0, $pagesize=0){
        return [];
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
