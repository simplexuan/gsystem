<?php
/**
 * Model_function_category_new
 *
 * @uses Gsystem
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com>
 * @license
 */
class Model_function_category_new extends Gsystem_model {
    /**
     * _model
     *
     * @var string
     * @access protected
     */
    protected $_model    = '';
    /**
     * _mkeys
     *
     * @var array
     * @access protected
     */
    protected $_mkeys = array(
        'Gsystem_Model_function_category_new' => array(),
    );
    /**
     * _equal_search_items
     *
     * @var string
     * @access protected
     */
    protected $_equal_search_items = array();
    /**
     * __construct
     *
     * @access protected
     * @return mixed
     */
    function __construct() {
        parent :: __construct();
        $this->_model = substr(__CLASS__, 6);
    }

    /**
     * new_c
     *
     * @param int $id
     * @access public
     * @return mixed
     */
    function new_c($id = 0) {
        $dao_param      = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
        $new_function_category_new  = array();
        if ($id > 0) {
            $new_function_category_new['function_category_new'] = $this->dao('/Dao_function_category_new', $dao_param)->fetch_one_by_id($id);
        } else {
            $new_function_category_new['function_category_new']  = $this->dao('/Dao_function_category_new', $dao_param)->new_one();
        }
        return $new_function_category_new;
    }

    /**
     * search
     *
     * @param array $param
     * @param int $page
     * @param int $pagesize
     * @access public
     * @return mixed
     */
    function search($param = array(), $page = 0, $pagesize = 0) {
        return [];
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
        $dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
        return call_user_func_array(array($this->dao('/Dao_function_category_new',  $dao_param), $func), $args);
    }
}
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
