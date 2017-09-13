<?php
/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2017/1/16
 * Time: 14:10
 */

class Logic_traffic extends Gsystem_logic {
    function __construct() {
        parent::__construct();
    }

    /*
     * 根据地址获取交通信息列表
     */
    public function get_traffic_info_by_address($param){
        if(empty($param)) throw new Exception('参数不能为空', 100001);
        try{
            return $model = $this->model('Model_traffic')->get_traffic_info_by_address($param);
        }catch (Exception $e){
            throw $e;
        }
    }

    /*
     * 同步公司地址交通信息
     */
    public function sync_corporation_traffic($param){
        if(empty($param['addresses'])) throw new Exception('参数不能为空', 100001);
        switch($param['type']){
            case 'tobusiness':
                $type = 'tobusiness';
                break;
            case 'gsystem':
                $type = 'gsystem';
                break;
            default:
                throw new Exception('参数不能为空', 100001);
        }

        return $this->model('Model_traffic')->sync_corporation_traffic($param['addresses'], $type);
    }


    /*
     * 获取职位地址交通信息
     */
    public function get_position_traffic($param){
        if(empty($param['position_id'])) throw new Exception('职位ID不能为空', 100001);

        $traffic = $this->model('Model_traffic')->getPositionTraffic(intval($param['position_id']));

        return $traffic;
    }

    /*
     * 获取城市ID，精确到区域
     */
    public function get_region_id($param){
        return $this->model('Model_traffic')->get_region_id($param);
    }

    /*
     * 处理交通重名
     */
    public function handle_traffic_tmp(){
        return $this->model('Model_traffic')->handleTrafficTmp();
    }

}