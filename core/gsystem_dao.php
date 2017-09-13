<?php

/**
 * chr_dao
 *
 * @uses CI
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2013 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@51chance.com.cn>
 * @license
 */
class Gsystem_dao extends CI_Dao {
    protected $_table;
    protected $_insert_fields = array();
    protected $_update_fields = array();
    protected $_select_fields = array();
    protected $_primary_key   = 'id';
    protected $_foreign_key   = '';
    protected $_dao_prefix    = 'Dao_';
    protected $_tree_daos     = array('architecture'=>1);
    protected $_dao           = '';

    /**
     * 继承父类构造函数
     */
    function __construct($active_group='') {
        parent::__construct();
        // 创建数据库连接实例
        //$this->load->database($active_group, FALSE, TRUE);
        //$this->load->driver('cache', NULL, 'cache');
    }
	/**
	 * __call 
	 * 预处理，把*转换成所有字段
	 * 
	 * @param mixed $name 
	 * @param mixed $args 
	 * @access protected
	 * @return mixed
	 */
	function __call($name, $args) {
        $i = count($args) - 1;

        if(!empty($args[$i]['select']) && $args[$i]['select'] == '*') {
            $field = $this->_select_fields;
            unset($field[0]); 
            $args[$i]['select'] = '`' . implode('`,`', $field) . '`';
        }

        return parent::__call($name, $args);
	}
    //根据dao名称获取表名
    function get_table_name($param){
        $table = $this->config->item(substr($param['virtual_table'], strlen($this->_dao_prefix)), 'tableMap');

        if (empty($table)){
            throw new Exception(sprintf('dao:%s \'table not exists.', $param['virtual_table']),
                    $this->config->item('db_err_no', 'err_no')
                    );
        }

        return $table;

    }

    /**
     * _has_operator 
     * 
     * @param mixed $str 
     * @access protected
     * @return mixed
     */
    protected function _has_operator($str){
        $str = trim($str);
        if ( ! preg_match("/(\s|<|>|!|=|is null|is not null)/i", $str))
        {
            return FALSE;
        }
        return TRUE;
    }
    /**
     * _remove_operator 
     * 
     * @param mixed $str 
     * @access protected
     * @return mixed
     */
    protected function _remove_operator($str){
        preg_match_all("/([a-zA-Z0-9_]+)/i", $str, $matches);
        return $matches[1][0];
    }
    public function get_insert_fields(){
        return $this->_insert_fields;
    }

    /**
     * fetch_one_by_id的无异常版
     */
    function get_by_id($id) {
        try {
            return $this->fetch_one_by_id($id);
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * gen_id_by_unique 
     * 
     * @param array $param 
     * @access public
     * @return mixed
     */
    function gen_id_by_unique($param=array()) {
        if (!is_array($param) || empty($param)) {
            throw new Exception(sprintf('function: %s,table: %s parameters must be array.',
                        __FUNCTION__, $this->_table), $this->config->item('parameter_err_no', 'err_no')
                    );
        }
        $new_param = array();
        foreach ($this->_unique_fields as $field) {
            if ($field == $this->_primary_key) continue;
            if (!isset($param[$field])) {
                throw new Exception(sprintf('function: %s,table: %s parameter: %s not exists.',
                            __FUNCTION__, $this->_table,  $field),$this->config->item('parameter_err_no', 'err_no')
                        );
            }
            $new_param[$field] = $param[$field];
        }
        if (empty($new_param)) { 
            throw new Exception(sprintf('function: %s,table: %s new_param must be not empty array.',
                        __FUNCTION__, $this->_table), $this->config->item('parameter_err_no', 'err_no')
                    );
        }

        $dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
        $criteria  = array();
        foreach ($new_param as $k=>$v) {
            $criteria[] = sprintf("%s='%s'", $k, $this->db->escape_str($v));
        }
        $strCriteria    =  implode(' AND ', $criteria);
        $in_trans       =  $this->db->conn_id->inTransaction();
        $my_trans       =  TRUE;
        try {
            $in_trans ? ( $my_trans = FALSE) : $this->db->conn_id->beginTransaction();
            $sql = sprintf("SELECT %s FROM %s WHERE %s FOR UPDATE", $this->_primary_key, $this->_table, $strCriteria);
            $q = $this->db->query($sql);
            if ($q->num_rows() <= 0) {
                $id = $this->insert_unique($param);
            } else {
                $result = $q->first_row('array');
                $id     = $result[$this->_primary_key];
            }
            $my_trans ?  $this->db->conn_id->commit(): '';
            return $id;
        } catch(Exception $e) {
            $my_trans ?  $this->db->conn_id->rollback() : '';
            throw $e;
        }
    }

    /**
     * insert_unique
     *
     * @param array $param
     * @access public
     * @return mixed
     */
    function insert_unique($param = array()) {
        $new_param = array();
        //原先是只插入唯一组合索引的值，现在改成可以插入相关内容，只判断是否为表字段（可以考虑直接过滤掉非表字段）
        foreach ($param as $key=>$val) {
            if (!in_array($key,$this->_select_fields)){
                throw new Exception(sprintf('function: %s, parameter: %s not exists.', __FUNCTION__, $key),
                        $this->config->item('parameter_err_no', 'err_no')
                        );
            }
            $new_param[$key] = $param[$key];
        }
        
        if ($this->db->insert($this->_table, $new_param) === FALSE){
            throw new Exception(sprintf('insert into table %s error:%s', $this->_table, $this->db_error()),
                    $this->config->item('db_err_no', 'err_no')
                    );

        }
        return empty($new_param[$this->_primary_key]) ? $this->db->insert_id() : $new_param[$this->_primary_key];
    }

    public function test(){
        return $this->db->query("select * from corporations limit 10")->result_array();
    }
}
