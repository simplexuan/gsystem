<?php
/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2016/12/20
 * Time: 14:11
 */

class Logic_corporation_mapping extends Gsystem_logic {
    function __construct() {
        parent::__construct();
    }

    /**
     * 保存接口
     * @param $param
     */

    /**
     * @param $param array([
     *  'company_id'=>      // 公司ID
     *  'pcompany_id'=>     // company_id在depth层级的父ID
     *  'depth'=>           // pcompany_id相对company_id的所在层级
     *  'horizontal'=>      // company_id的所在的水平位置
     *  'stype'=>           // 数据来源:1人工干预，2程序识别, 0未知
     * ])
     * @return mixed
     * @throws Exception
     */
    public function save($param){
        if(empty($param)) throw new Exception('参数不能为空', 100001);
        if(!is_array($param)) throw new Exception('参数格式不合法', 100001);
        try{
            return $model = $this->model('Model_corporation_mapping')->save($param);
        }catch (Exception $e){
            throw $e;
        }
    }

    /*
     * 删除公司映射
     * @param array $param array(
     * 'company_id'=> //公司ID
     * return array
     */
    public function delete($param){
        if(empty($param['company_id'])) throw new Exception('参数不能为空', 100001);
        if(!is_numeric($param['company_id'])) throw new Exception('参数格式不合法', 100001);

        try{
            return $model = $this->model('Model_corporation_mapping')->delete($param['company_id']);
        }catch (Exception $e){
            throw $e;
        }
    }

    /*
     * 查询公司映射
     * @param array $param array(
     * 'company_id'=> //公司ID
     * return array
     */
    public function select($param){
        if(empty($param['company_id'])) throw new Exception('参数不能为空', 100001);
        if(!is_numeric($param['company_id'])) throw new Exception('参数格式不合法', 100001);

        try{
            return $model = $this->model('Model_corporation_mapping')->select($param['company_id']);
        }catch (Exception $e){
            throw $e;
        }
    }

    /*
     * 更新公司映射
     * @param array $param array(
     *  'company_id'=>      // 公司ID
     *  'pcompany_id'=>     // company_id在depth层级的父ID
     *  'depth'=>           // pcompany_id相对company_id的所在层级
     *  'horizontal'=>      // company_id的所在的水平位置
     *  'stype'=>           // 数据来源:1人工干预，2程序识别, 0未知
     * return array
     */
    public function update($param){
        if(empty($param['company_id'])) throw new Exception('参数不能为空', 100001);
        if(!is_numeric($param['company_id'])) throw new Exception('company_id参数格式不合法', 100001);
        if(!is_numeric($param['pcompany_id'])) throw new Exception('pcompany_id参数格式不合法', 100001);
        if(!is_numeric($param['depth'])) throw new Exception('depth参数格式不合法', 100001);
        if(!is_numeric($param['horizontal'])) throw new Exception('horizontal参数格式不合法', 100001);
        if(!is_numeric($param['stype'])) throw new Exception('stype参数格式不合法', 100001);

        try{
            return $model = $this->model('Model_corporation_mapping')->update($param);
        }catch (Exception $e){
            throw $e;
        }
    }
}