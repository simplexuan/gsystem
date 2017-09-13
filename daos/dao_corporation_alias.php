<?php 
/**
 * Dao_corporation_alias
 * 
 * @uses Gsystem
 * @uses _Dao
 * @package 
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com> 
 * @license 
 */
class Dao_corporation_alias extends Gsystem_dao {
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
    protected $_insert_fields = array('type_id', 'corporation_id', 'sort', 'alias');
    /**
     * _update_fields 
     * 
     * @var array
     * @access protected
     */
    protected $_update_fields = array('id', 'type_id', 'corporation_id', 'sort', 'alias', 'is_deleted', 'updated_at');
    /**
     * _select_fields 
     * 
     * @var array
     * @access protected
     */
    protected $_select_fields = array('id', 'type_id', 'corporation_id', 'sort', 'alias', 'is_deleted', 'updated_at', 'created_at');
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
    protected $_index_fields = array('idx_corporation_id_type_id' => array(1 => 'corporation_id', 2 => 'type_id', ),'idx_alias' => array(1 => 'alias', ),);
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
        $equal_search_items = array('corporation_id'=>'t','type_id'=>'t','alias'=>'t');
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

    public function fetch_one_by_id($id){
        $this->db->where('is_deleted','N');
        $this->db->where('id',$id);
        $q = $this->db->get($this->_table);
        return $q->row(0,'array');
    }

    /**
     * 删除 公司别名
     * @param $id
     */
    public function delete_one_by_id($id){
        $this->log->debug($this->_table." will be delete corporation_id:$id ............");
        try{
            $this->db->query("update {$this->_table} set is_deleted='Y',updated_at=? where id=$id",array(date('Y-m-d H:i:s')));
        }catch (Exception $e){
            throw $e;
        }
    }

}
 /* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
