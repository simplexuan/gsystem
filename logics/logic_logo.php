<?php
/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2016/12/20
 * Time: 10:50
 */

class Logic_logo extends Gsystem_logic {
    function __construct() {
        parent::__construct();
    }

    /*
     * 批量保存logo
     */
    public function save($param){
        if(empty($param)) throw new Exception('参数不能为空', 100001);
        if(!is_array($param)) throw new Exception('参数格式不合法', 100001);
        if(!isset($param['type'])) throw new Exception('type字段必传', 100001);
        if(!isset($param['src_no'])) throw new Exception('src_no字段必传', 100001);
        if(!is_numeric($param['type'])) throw new Exception('type字必须为数字', 100001);
        if(!is_numeric($param['src_no'])) throw new Exception('src_no字必须为数字', 100001);
        if(!isset($param['data'])) throw new Exception('data字段必传', 100001);
        try{
            return $model = $this->model('Model_logo')->save($param);
        }catch (Exception $e){
            throw $e;
        }
    }

    /*
     * 获取logo数据（单、多条查询）
     */
    public function get($param){
        if(empty($param)) throw new Exception('参数不能为空', 100001);
        if(empty($param['ids'])) throw new Exception('ids字段不能为空', 100001);
        if(!isset($param['type'])) throw new Exception('type字段必传', 100001);
        if(!isset($param['src_no'])) throw new Exception('src_no字段必传', 100001);
        if(!is_numeric($param['type'])) throw new Exception('type字必须为数字', 100001);
        if(!is_numeric($param['src_no'])) throw new Exception('src_no字必须为数字', 100001);
        try{
            return $model = $this->model('Model_logo')->get($param);
        }catch (Exception $e){
            throw $e;
        }
    }

    /*
     * 修改logo
     */
    public function update($param){
        if(empty($param)) throw new Exception('参数不能为空', 100001);
        if(!is_array($param)) throw new Exception('参数格式不合法', 100001);
        if(empty($param['id'])) throw new Exception('id字段必传', 100001);
        if(empty($param['logo'])) throw new Exception('logo字段必传', 100001);
        if(!isset($param['type'])) throw new Exception('type字段必传', 100001);
        if(!is_numeric($param['type'])) throw new Exception('type字必须为数字', 100001);
        try{
            return $model = $this->model('Model_logo')->update($param);
        }catch (Exception $e){
            throw $e;
        }
    }

    /*
     * 获取追溯公司logo
     */
    public function get_ascend_logo($param){
        if(empty($id = intval($param['id']))) throw new Exception('id字段必传', 100001);
        return $model = $this->model('Model_logo')->get_ascend_logo($id);
    }
}