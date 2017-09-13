<?php
/**
 * Dao_function
 *
 * @uses Gsystem
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com>
 * @license
 */
class Dao_function_old extends Gsystem_dao {
    /**
     * _table
     *
     * @var mixed
     * @access protected
     */
    protected $_table;
    /**
     * _select_fields
     *
     * @var array
     * @access protected
     */
    protected $_select_fields = array('id', 'name', 'parent_id');
    /**
     * _primary_key
     *
     * @var mixed
     * @access protected
     */
    protected $_primary_key = 'id';
    /**
     * _unique_fields
     *
     * @var array
     * @access protected
     */
    protected $_unique_fields = array('id');
    /**
     * _index_fields
     *
     * @var string
     * @access protected
     */
    protected $_index_fields = array();
    /**
     * _dao
     *
     * @var string
     * @access protected
     */
    protected $_dao = '';
    /**
     * _auto_increment
     *
     * @var mixed
     * @access protected
     */
    protected $_auto_increment =  FALSE ;
    /**
     * __construct
     *
     * @access protected
     * @return mixed
     */
    public function __construct($param=array()) {
        parent::__construct($param['active_group']);
        $this->_table = $this->get_table_name(array('virtual_table'=>__CLASS__, 'id'=>$param['id']));
        $this->_dao = substr(__CLASS__, 4);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
