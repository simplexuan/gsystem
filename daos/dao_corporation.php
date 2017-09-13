<?php 
/**
 * Dao_corporation
 * 
 * @uses Gsystem
 * @uses _Dao
 * @package 
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com> 
 * @license 
 */
class Dao_corporation extends Gsystem_dao {
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
    protected $_insert_fields = array('parent_id', 'name', 'website', 'city_id', 'city_name', 'nature_id', 'nature_name', 'size_id', 'size_name', 'uid', 'status', 'point_to', 'updated_at');
    /**
     * _update_fields 
     * 
     * @var array
     * @access protected
     */
    protected $_update_fields = array('id', 'parent_id', 'name', 'website', 'city_id', 'city_name', 'nature_id', 'nature_name', 'size_id', 'size_name', 'uid', 'status', 'point_to', 'is_deleted', 'updated_at');
    /**
     * _select_fields 
     * 
     * @var array
     * @access protected
     */
    protected $_select_fields = array('id', 'parent_id', 'name', 'website', 'city_id', 'city_name', 'nature_id', 'nature_name', 'size_id', 'size_name', 'uid', 'status', 'point_to', 'is_deleted', 'updated_at', 'created_at');
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
    protected $_unique_fields = array('id', 'name');
    /**
     * _index_fields 
     * 
     * @var string
     * @access protected
     */
    protected $_index_fields = array('uidx_name' => array(1 => 'name', ),'idx_parent_id' => array(1 => 'parent_id', ),'idx_status_uid_updated_at' => array(1 => 'status', 2 => 'uid', 3 => 'updated_at', ),'idx_uid_updated_at' => array(1 => 'uid', 2 => 'updated_at', ),'idx_status_updated_at' => array(1 => 'status', 2 => 'updated_at', ),);
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
     * 删除公司
     * @param $id
     * @throws Exception
     */
    public function delete_one_by_id($id){
        $this->log->debug($this->_table." will b delete corporation_id:$id ............");
        try{
            $this->db->query("update {$this->_table} set is_deleted='Y',updated_at=? where id=$id",array(date('Y-m-d H:i:s')));
        }catch (Exception $e){
            throw $e;
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
            $id = 0;
            $in_trans ? ( $my_trans = FALSE) : $this->db->conn_id->beginTransaction();
                        $sql = sprintf("SELECT corporation_id FROM  corporations_alias_names WHERE %s FOR UPDATE", $strCriteria);
            $q = $this->db->query($sql);
            if ($q->num_rows() <=0) {
                $this->load->dao('public/Dao_id_allocator', '', $dao_param);
                $new_param['corporation_id'] = $this->Dao_id_allocator->allocate(array('ns'=>'Gsystem','table_name'=>substr(__CLASS__, 4)));
                $new_param['id'] = $this->Dao_id_allocator->allocate(array('ns'=>'Gsystem','table_name'=>'corporation_alias_name'));
                $id = $this->insert_unique(array('id'=> $new_param['corporation_id'], 'name'=> $new_param['name']));
                $this->load->dao('corporation/Dao_corporation_alias_name', '', $dao_param);
                $this->Dao_corporation_alias_name->db->insert('corporations_alias_names',
                        array('id'=>$new_param['id'], 'name'=>$new_param['name'], 'corporation_id'=>$new_param['corporation_id']));
            } else {
                $result = $q->first_row('array');
                $id     =  $result['corporation_id'];
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
        $equal_search_items = array('name'=>'t','parent_id'=>'t','status'=>'t','uid'=>'t','updated_at'=>'t');
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
    /**
     * search 
     * 
     * @param array $param
     * @access public
     * @return mixed
     */
    public function search_optimize($param)
    {
        if (! empty($param['like']) && is_string($param['like'])) {
            $likes = explode(',', $param['like']);
        }

        $where_fields = array();
        $like_fields = array();
        foreach ($param as $field => $value) {
            if ('page' == $field || 'pagesize' == $field || 'like' == $field || 'selected' == $field || 'ordersort' == $field) continue;
            if (isset($likes) && in_array($field, $likes)) {
                $like_fields[$field] = $param[$field];
            } else {
                $where_fields[$field] = $param[$field];
            }
        }

        $result = array('num' => 0, 'result' => array());

        if (isset($param['distinct'])) {
            $this->db->distinct($param['distinct']);
        }
        foreach ($where_fields as $field => $value) {
            $this->db->where($field, $value);
        }
        foreach ($like_fields as $field => $value) {
            $this->db->like($field, $value);
        }
        if (isset($param['group_by'])) {
            $this->db->group_by($param['group_by']);
        }
        $result['num'] = $this->db->count_all_results($this->_table);

        if (0 < $result['num']) {
            if (isset($param['distinct'])) {
                $this->db->distinct($param['distinct']);
            }
            if (isset($param['select'])) {
                $this->db->select(sprintf('%s', $param['select']));
            } else {
                $this->db->select($this->_primary_key);
            }
            foreach ($where_fields as $field => $value) {
                $this->db->where($field, $value);
            }
            foreach ($like_fields as $field => $value) {
                $this->db->like($field, $value);
            }
            if (isset($param['group_by'])) {
                $this->db->group_by($param['group_by']);
            }
            if (isset($param['ordersort'])) {
                $this->db->order_by($param['ordersort']);
            }
            if (isset($param['page']) && 0 < $param['page'] && isset($param['pagesize']) && 0 < $param['pagesize']) {
                $offset = ($param['page'] - 1) * $param['pagesize'];
                $this->db->limit($param['pagesize'], $offset);
            }

            $q = $this->get($this->_table);
            foreach ($q->result_array() as $row) {
                $result['result'][$row[$this->_primary_key]] = $row;
            }
        }

        return $result;
    }

}
 /* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
