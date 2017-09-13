<?php 
/**
 * Dao_industry
 * 
 * @uses Gsystem
 * @uses _Dao
 * @package 
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com> 
 * @license 
 */
class Dao_industry extends Gsystem_dao {
    /**
     * _table 
     * 
     * @var mixed
     * @access protected
     */
    protected $_table;
    /**
     * _insert_fields 
     * 
     * @var array
     * @access protected
     */
    protected $_insert_fields = array('name', 'parent_id', 'weight', 'depth', 'autosub', 'p1', 'p2', 'p3', 'p4', 'uid', 'status', 'updated_at');
    /**
     * _update_fields 
     * 
     * @var array
     * @access protected
     */
    protected $_update_fields = array('id', 'name', 'parent_id', 'weight', 'depth', 'autosub', 'p1', 'p2', 'p3', 'p4', 'uid', 'status', 'is_deleted', 'updated_at');
    /**
     * _select_fields 
     * 
     * @var array
     * @access protected
     */
    protected $_select_fields = array('id', 'name', 'parent_id', 'weight', 'depth', 'autosub', 'p1', 'p2', 'p3', 'p4', 'uid', 'status', 'is_deleted', 'updated_at', 'created_at');
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
    protected $_unique_fields = array('id', 'parent_id', 'name');
    /**
     * _index_fields 
     * 
     * @var string
     * @access protected
     */
    protected $_index_fields = array('uidx_parent_id_name' => array(1 => 'parent_id', 2 => 'name', ),'idx_name' => array(1 => 'name', ),'idx_p1' => array(1 => 'p1', ),'idx_p2' => array(1 => 'p2', ),'idx_p3' => array(1 => 'p3', ),'idx_p4' => array(1 => 'p4', ),);
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
    protected $_auto_increment =  TRUE ;
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
            $id = 0;
            $in_trans ? ( $my_trans = FALSE) : $this->db->conn_id->beginTransaction();
                        $sql = sprintf("SELECT %s FROM %s WHERE %s FOR UPDATE", $this->_primary_key, $this->_table, $strCriteria);
            $q = $this->db->query($sql);
            if ($q->num_rows() <= 0) {
                $this->load->dao('public/Dao_id_allocator', '', $dao_param);
                $new_param[$this->_primary_key] = $this->Dao_id_allocator->allocate(array('ns'=>'Gsystem','table_name'=> substr(__CLASS__, 4)));
                $id = parent :: insert_unique($new_param);
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
     * search 
     * 
     * @param array $param 
     * @param int $page 
     * @param int $pagesize 
     * @access public
     * @return mixed
     */
    function search($param = array()) {
        $res = array('num'=>0, 'results'=>array());
        $equal_search_items = array('parent_id'=>'t','name'=>'t','p1'=>'t','p2'=>'t','p3'=>'t','p4'=>'t');
        $like_search_items  = array();
        $like2_search_items = array();

        foreach ($param as $column=>$v) {
            if (!isset($equal_search_items[$this->_remove_operator($column)])) continue;
            $this->db->where($column, $v);
        }
        foreach ($like_search_items as $column=>$t) {
            if (!isset($param[$column])) continue;
            $this->db->like($column, $param[$column]);
        }
        isset($param['group_by']) ? $this->db->group_by($param['group_by']) : '';
        isset($param['distinct']) ? $this->db->distinct($param['distinct']) : '';
        $res['num'] = $this->db->count_all_results($this->_table);
        if ($res['num'] > 0) {
            $res['results'] = array();
            foreach ($param as $column=>$v) {
                if (!isset($equal_search_items[$this->_remove_operator($column)])) continue;
                $this->db->where($column, $v);
            }
            foreach($like_search_items as $column=>$t) {
                if (!isset($param[$column])) continue;
                $this->db->like($column, $param[$column]);
            }
            if (isset($param['page']) && $param['page'] >0 && isset($param['pagesize']) && $param['pagesize']>0) {
                $page       = $param['page'];
                $pagesize   = $param['pagesize'];
                $start      = (min(max(ceil($res['num']*1.0/$pagesize), 1), $page)-1)*$pagesize;
                $this->db->limit($pagesize, $start);
            }

            isset($param['ordersort']) ? $this->db->order_by($param['ordersort']) : '';
            isset($param['group_by']) ? $this->db->group_by($param['group_by']) : '';
            isset($param['select']) ? $this->db->select(sprintf('%s', $param['select'])) :  $this->db->select($this->_primary_key);

            $q = $this->get($this->_table);
            foreach ($q->result_array() as $result) {
                $res['results'][$result[$this->_primary_key]] = $result;
            }
        }
        return $res;
    }

}
 /* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
