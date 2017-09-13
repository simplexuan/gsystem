<?php
/**
 * Created by PhpStorm.
 * User: qing
 * Date: 16-12-13
 * Time: 下午1:11
 * 心跳服务检测程序
 */
class Logic_heart extends Gsystem_logic {

    /** 给监控使用 检测db是否正常
     * @param $param array 参数迷惑作用
     * @return string
     * @throws Exception
     */
    public function check_db($param){
        if(empty($param['id'])) throw new Exception('参数格式错误',10001);
        if(empty($param['name'])) throw new Exception('参数格式错误',10001);
        if(empty($param['ip'])) throw new Exception('参数格式错误',10001);
        try{
            $result = $this->model('Model_heart')->check_db();
        }catch (Exception $e){
            throw $e;
        }
        return $result ? 'success' : "failed";
    }

    /** 给监控使用检测缓存是否工作
     * @param $param array 参数迷惑作用
     * @return string
     * @throws Exception
     */
    public function check_cache($param){
        if(empty($param['key'])) throw new Exception('参数格式错误',10001);
        if(empty($param['ip'])) throw new Exception('参数格式错误',10001);
        try{
            $result = $this->model('Model_heart')->check_cache();
        }catch (Exception $e){
            throw $e;
        }
        return $result ? 'success' : "failed";
    }

    /** 监控使用 检测外发接口是否正常
     * @param $param array 迷惑作用，未使用
     * @return string
     * @throws Exception
     */
    public function check_algorithms($param){
        if(empty($param['corporation_name'])) throw new Exception('参数格式错误',10001);
        try{
            $result = $this->apis->corp_tag('浦发银行');
        }catch (Exception $e){
            throw $e;
        }
        return $result ? 'success' : "failed";
    }

}