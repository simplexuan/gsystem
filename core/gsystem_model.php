<?php
class Gsystem_model extends CI_Model{
	const ID_CACHE_NUM=1000;
    /**
     * __construct
     *
     * @access protected
     * @return mixed
     */
    function __construct(){
        parent::__construct();
    }
    /**
     * dao
     *
     * @param mixed $dao
     * @access public_id 17
     * @return mixed 18      */
    public function dao($dao, $param=array('active_group'=>'easyunter', 'id'=>0)){
        $this->load->dao($dao, '', $param);
        if (($last_slash = strrpos($dao, '/')) !== FALSE){
            $dao = substr($dao, $last_slash + 1);
        }

        return $this->$dao;
    }
    function inTransaction(){
        return $this->db->conn_id->inTransaction();
    }
    function beginTransaction(){
        return $this->db->conn_id->beginTransaction();
    }
    function commit(){
        return $this->db->conn_id->commit();
    }
    function rollback(){
        return $this->db->conn_id->rollback();
    }
    protected function _remove_operator($str){
        preg_match_all("/([a-zA-Z0-9_]+)/i", $str, $matches);
        return $matches[1][0];
    }
    function _get_cache_key($param=array()) {
        $new_param = array();
        foreach($param as $k=>$v) {
            if (isset($this->_equal_search_items[$k])) $new_param[$k] = $v;
        }
        foreach ($this->_mkeys as  $k=>$v){
            $diff = array_diff($v, array_keys($new_param));
            if (empty($diff)){
                $temp = array();
                foreach($v as $item){
                    $temp [$item] =   $new_param[$item];
                }
                return vsprintf($k, $temp);
            }
        }
        return FALSE;
    }
    /**
     * _diff
     *
     * @param mixed $old
     * @param mixed $new
     * @param mixed $keys
     * @param array $filter_keys
     * @access protected
     * @return mixed
     */
    function _diff($old, $new, $keys, $filter_keys=array()) {
        $diff = array();
        foreach ($keys as $key) {
            if (in_array($key, array('updated_at', 'user_id', 'user_name', 'id'))) continue;
            if (in_array($key, $filter_keys)) continue;
            if (!isset($new[$key])){
                $diff[$key] =  sprintf('%s: %s =>', $key, $old[$key]);
            }elseif(!isset($old[$key])) {
                $diff[$key] =  sprintf('%s: => %s', $key,  $new[$key]);
            }elseif ($old[$key] != $new[$key]){
                $diff[$key] =  sprintf('%s: %s =>%s', $key, $old[$key], $new[$key]);
            }
        }
        return $diff;
    }
    /**
     * search_all
     * @param $param array search参数
     */
    function search_all($param = array()) {
        $rs = $this->search($param, 1, self::ID_CACHE_NUM);
        $return = $rs['results'];
        if($rs['num'] > self::ID_CACHE_NUM) {
            // $num = ceil($rs['num']/self::ID_CACHE_NUM);
            //只支持5000条
            $num = ceil(min(5000, $rs['num'])/self::ID_CACHE_NUM);
            for($i=1; $i<$num; $i++) {
                $rs = $this->search($param, $i+1, self::ID_CACHE_NUM);
                $return += $rs['results'];
            }
        }

        //ksort($return);

        //return array_values($return);
        return $return;
    }
    /**
     * search_one
     * @param $param array search参数
     */
    function search_one($param = array()) {
        $rs = $this->search($param, 1, 1);

        return reset($rs['results']);
    }


    /**
     * @param $sql string sql语句
     * @param bool $is_select 是否是查询
     * @param bool $is_insert_id    是否需要返回插入id
     * @return mixed 如果是查询返回数组，如果是插入返回插入id，如果是update什么不返回
     * @throws Exception
     */
    protected function parse_sql($sql,$is_select=true,$is_insert_id=false,$db_name='gsystem'){
        if($db_name === 'gsystem'){
            $db = $this->db;
        }else{
            $db = $this->load->database($db_name, true, true);
        }
        
        try{
            if($is_select){
                $results = $db->query($sql)->result_array();
            }else{
                $results = $db->query($sql);
                if($is_insert_id){
                    $results = $db->insert_id();
                }
            }
            if($db_name !== 'gsystem') $db->close();
            return $results;
        }catch (Exception $e){
            throw $e;
        }
    }
    
    
    
}
