<?php
/**
 * Created by PhpStorm.
 * User: qing
 * Date: 16-12-13
 * Time: 下午2:00
 */
class Model_heart extends Gsystem_model {

    /** 简单验证数据是否畅通
     * @return bool
     */
    public function check_db(){
        $res = $this->parse_sql("select * from corporations order by id desc limit 1");
        return empty($res) ? false : true;
    }

    /** 简单验证缓存是否正常
     * @return bool
     */
    public function check_cache(){
        $key = "gsystem_heart_check_test";
        $this->cache->memcached->save($key,'this is cache heart...');
        $res = $this->cache->memcached->get($key);
        return empty($res) ? false : true;
    }
}