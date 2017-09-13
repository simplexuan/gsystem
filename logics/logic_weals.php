<?php
/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2017/2/28
 * Time: 15:04
 */

class Logic_weals extends Gsystem_logic {
    function __construct() {
        parent::__construct();
    }

    /*
     * 通过公司ID获取公司福利列表
     */
    public function get_corporation_weals($param){
        if(empty($param)) throw new Exception('参数不能为空', 100001);
        try{
            return $model = $this->model('Model_weals')->get_corporation_weals($param);
        }catch (Exception $e){
            throw $e;
        }
    }

    /*
     * 同步公司地址交通信息
     * @param int id 公司ID
     * @param array weals 公司福利数组
     */
    public function save_corporation_weal($param){
        if(empty($param['id'])) throw new Exception('公司ID为空，请核实', 100001);
        if(empty($param['weals'])) throw new Exception('公司福利为空，请核实', 100001);
        if(!is_array($param['weals'])) throw new Exception('公司福利数据不合法，请核实', 100001);

        try{
            return $model = $this->model('Model_weals')->save_corporation_weal($param);
        }catch (Exception $e){
            throw $e;
        }
    }

}