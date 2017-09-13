<?php
/**
 * Created by PhpStorm.
 * User: sunjiqing
 * Date: 2017/4/13
 * Time: 10:28
 * Desc: 公司基础信息字典模型
 */
class Model_corporation_baseinfo_dictionary extends Gsystem_model {
	protected $_model    = '';

	/*
	 * 根据类型获取公司基础信息字典
	 * @param int $type_id　基础信息类型：1 融资，2 规模，3 性质，为空查所有
	 * return array
	 */
	public function get_list($type_id = 0){
		$sql = "SELECT id,name,type_id FROM corporations_baseinfos_dictionary";
		if(!empty($type_id)){
			$sql .= " WHERE type_id = $type_id";
		}

		$cache_key = 'GSYSTEM_GET_BASEINGOS_DICTIONARY_LIST' . md5($type_id);
		if(empty($result = $this->cache->memcached->get($cache_key))){
			$result = $this->parse_sql($sql);

			$this->cache->memcached->save($cache_key, $result, 3600); // 缓存1小时
		}

		return $result;
	}
}

