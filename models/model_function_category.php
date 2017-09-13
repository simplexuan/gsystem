<?php
/**
 * Model_function_category
 *
 * @uses Gsystem
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com>
 * @license
 */
class Model_function_category extends Gsystem_model {
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
			 
			'Gsystem_Model_function_category' => array(),
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
		$new_function_category  = array();
		if ($id > 0) {
			$new_function_category['function_category'] = $this->dao('/Dao_function_category', $dao_param)->fetch_one_by_id($id);
		} else {
			$new_function_category['function_category']  = $this->dao('/Dao_function_category', $dao_param)->new_one();
		}
								return $new_function_category;
	}
						/**
	 * save
	 *
	 * @param array $param
	 * @access public
	 * @return mixed
	 */
	function save($param = array()) {
		return [];
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
	 * delete_one_by_id 
	 * 
	 * @param int $id 
	 * @param int $user_id 
	 * @access public
	 * @return mixed
	 */
	function delete_one_by_id($id=0, $user_id=0) {
		return [];
	}
	/**
	 * update_by_id
	 * 
	 * @param array $param 
	 * @param int $id 
	 * @access public
	 * @return mixed
	 */
	function update_by_id($param = array(), $id = 0) {
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
		return call_user_func_array(array($this->dao('/Dao_function_category',  $dao_param), $func), $args);
	}

				/**
	 * update_by_unique 
	 * 
	 * @param mixed $param 
	 * @access public
	 * @return mixed
	 */
	function update_by_unique($param) {
		return [];
	}
}
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
