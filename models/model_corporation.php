<?php
/**
 * Model_corporation
 *
 * @uses Gsystem
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@ifchange.com>
 * @license
 */
class Model_corporation extends Gsystem_model {
				/**
	 * _model 
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_model    = '';
	/**
	 * _mkeys 
	 * 
	 * @var array
	 * @access protected
	 */
	protected $_mkeys = array(
			'Gsystem_Model_corporation_name:%s' => array('name', ),
			'Gsystem_Model_corporation_parent_id:%d' => array('parent_id', ),
			'Gsystem_Model_corporation_status:%d$uid:%d' => array('status', 'uid', ),
			'Gsystem_Model_corporation_uid:%d' => array('uid', ),
			'Gsystem_Model_corporation_status:%d' => array('status', ),
			'Gsystem_Model_corporation' => array(),
		);
	/**
	 * table field
	 * @var array
	 */
	protected $_field = array(
			'parent_id','name','website','city_id','city_name','nature_id','nature_name','size_id','size_name','uid','status','point_to','is_deleted','updated_at','created_at'
		);

	/**
	 * _equal_search_items 
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_equal_search_items = array('name'=>'t','parent_id'=>'t','status'=>'t','uid'=>'t','updated_at'=>'t',);
	/**
	 * __construct
	 *
	 * @access protected
	 * @return mixed
	 */
	function __construct() {
		parent :: __construct();
		$this->_model = substr(__CLASS__, 6);
	}

		/**
	 * new_c
	 *
	 * @param int $id
	 * @access public
	 * @return mixed
	 */
	function new_c($id = 0) {
		$dao_param      = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		$new_corporation  = array();
		if ($id > 0) {
			$new_corporation['corporation'] = $this->dao('/Dao_corporation', $dao_param)->fetch_one_by_id($id);
		} else {
			$new_corporation['corporation']  = $this->dao('/Dao_corporation', $dao_param)->new_one();
		}
								return $new_corporation;
	}
		/**
	 * save
	 *
	 * @param array $param
	 * @access public
	 * @return mixed
	 */
	function save($param = array()) {
		if ($this->_log->getEffectiveLevel() =='DEBUG'){
			$this->log->debug(var_export($param, TRUE));
		}
		if (empty($param) || !is_array($param)) {
			throw new Exception(sprintf('%s:%s parameters not array()', __CLASS__, __FUNCTION__),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}
		if (!isset($param[$this->_model])) {
			throw new Exception(sprintf('%s:%s input parameters not exists.', __CLASS__, __FUNCTION__),
					$this->config->item('parameter_err_no', 'err_no')
					);
		}
								$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
					$corporation = $param['corporation'];
			$corporation_id = isset($corporation['id']) ? $corporation['id'] : 0;
									$is_update = FALSE;
			if ($corporation_id > 0) {
				unset($corporation['id']);
		        $old_corporation = $this->dao('/Dao_corporation', $dao_param)->fetch_one_by_id($corporation_id);
				$this->dao('/Dao_corporation', $dao_param)->update($corporation, $corporation_id);
				$is_update = TRUE;
			} else {
				$old_corporation  = array();
				$corporation_id = $this->dao('/Dao_corporation', $dao_param)->insert($corporation);
			}

									
			
			//清除缓存
		foreach (array($old_corporation, array_merge($old_corporation, $corporation)) as $v){
			foreach ($this->_mkeys as $key_pattern => $keys){
				$temp = array();
				$has_key = TRUE;
				foreach($keys as $key){
					if (!isset($v[$key])){
						$has_key = FALSE;
						break;
					}
					$temp[$key] = $v[$key];
				}
				if (!$has_key) continue;
				$key  = vsprintf($key_pattern, $temp);

				if (!$this->cache->memcached->del($key)){
				}else{
					$this->log->debug(sprintf('%s: delete key:%s from memcached ok.', __FUNCTION__, $key));
				}
			}
		}
			return $corporation_id;
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
	function search($param = array(), $page = 0, $pagesize = 0) {
		$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		$corporations = array('num'=>0, 'results'=>array());
		if ($page > 0 && $pagesize > 0) {
			$param['page']      = $page;
			$param['pagesize']  = $pagesize;
		}
		$selected = !empty($param['selected']) ? $param['selected'] : array();
		if (isset($param['_ft_']) && !empty($param['_ft_'])){
			$corporations  = $this->dao('public/Dao_searcher', 
					array('active_group'=>$param['_ft_'], 'id'=>0) )
				->search($param);
		}else{
			$key = $this->_get_cache_key($param);     
			if(empty($param['ordersort'])) {
				$param['ordersort']     =  'created_at DESC';
			}

			if ($key === FALSE || ($page * $pagesize) > self :: ID_CACHE_NUM ){
				//    $param['ordersort']     =  'created_at DESC';
				$corporations = $this->dao('/Dao_corporation', $dao_param )->search($param);
			}else{ //缓存
				$corporations = $this->cache->memcached->get($key);
				if (empty($corporations)) {
					$param['page']     = 1;
					$param['pagesize'] = self :: ID_CACHE_NUM;
					$corporations = $this->dao('/Dao_corporation', $dao_param )->search($param);
					if (!$this->cache->memcached->save($key, $corporations)){
						//                        $this->log->warn(sprintf('set %s to memcached by key:%s failure..', $this->_model, $key));
						$this->log->push_info('del memcached key:%s fail', array($key));
					}
				}else{
					// $this->log->info(sprintf('search  %s from memcached by key:%s success.', $this->_model, $key));
					$this->log->push_info('(model:%s) (hit key:%s)', array($this->_model, $key));
				}

				if ($page > 0 && $pagesize > 0) {
					// array_slice 获取需要的数据
					$limit = $pagesize;
					if ($corporations['num'] < $page* $pagesize){
						$limit = $corporations['num'] - ($page-1) * $pagesize;
					}
					$corporations['results'] = array_slice($corporations['results'], ($page-1) * $pagesize, $limit, TRUE);
				}
			}
			//$corporations = $this->dao('/Dao_corporation', $dao_param )->search($param);
		}
		if ( $corporations['num'] > 0) {
			$items = $this->dao('/Dao_corporation', $dao_param)
				->get_multi(array_keys($corporations['results']), $selected);
			foreach ($items as $item) {
				$corporations['results'][$item['id']] = $item;
			}
		}
		return  $corporations;
	}
	/**
	 * delete_one_by_id 
	 * 
	 * @param int $id 
	 * @param int $user_id 
	 * @access public
	 * @return mixed
	 */
	function delete_one_by_id($id=0, $user_id=0) {
		$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		$id = intval($id);
		if ($id <= 0) {
			throw new Exception(sprintf('function: %s, parameter: id must greater than 0', __FUNCTION__),
					$this->config->item('parameter_err_no', 'err_no'));

		}
		$corporation = $this->dao('/Dao_corporation', $dao_param)->fetch_one_by_id($id);
		if ($user_id >0 && $corporation['user_id'] != $user_id){
			throw new Exception(sprintf('function: %s, op:%d has no permission to delete user_id:%d', __FUNCTION__,
						$user_id, $corporation['user_id']),
					$this->config->item('permission_err_no', 'err_no'));
		}
		$this->dao('/Dao_corporation', $dao_param)->delete_one_by_id($id);
			foreach($this->_mkeys as $k=>$items){
				$temp = array();
				foreach($items as $item){
					$temp[$item] = $corporation[$item];
				}
				$key = vsprintf($k, $temp);
				if (!$this->cache->memcached->del($key)){
					//  $this->log->warn(sprintf('%s: delete key:%s from memcached failure.', __FUNCTION__, $key));
					$this->log->push_info('del memcached key:%s fail', array($key));
				}
			}
		return TRUE;
	}

	/**
	 * [update_by_id 修改公司表信息]
	 * @param  array   $param [表字段 值 ]
	 * @param  integer $id    [主键id]
	 * @return [int]         [返回主键id]
	 */
	public function update_by_id($param = array(), $id = 0) {


		$id = intval($id);
		if ($id <= 0) {
			throw new Exception(sprintf('function: %s, parameter: id must greater than 0', __FUNCTION__),$this->config->item('parameter_err_no', 'err_no'));
		}

		$res = $this->fetch_one_by_id($id);
		if(empty($res)) throw new Exception("该公司不存在！", 100002);
		
		//过滤字段
		//
		$set='';
		foreach($param as $field=>$value){
			if(!in_array($field,$this->_field)) throw new Exception("$field 超出范围！！！", 100002);
			$set .= "`{$field}`='{$value}',";
		}
		$set = rtrim($set,',');
		$sql = "update corporations set $set where id='$id'";
		$this->parse_sql($sql,false);
		
		// $corporation = $this->dao('/Dao_corporation', $dao_param)->fetch_one_by_id($id);
		// $this->dao('/Dao_corporation', $dao_param)->update($param, $id);
		// //清除缓存
		// foreach (array($corporation, array_merge($corporation, $param)) as $v){
		// 	foreach ($this->_mkeys as $key_pattern => $keys){
		// 		$temp = array();
		// 		foreach($keys as $key){
		// 			$temp[$key] = $v[$key];
		// 		}
		// 		$key  = vsprintf($key_pattern, $temp);
		// 		if (!$this->cache->memcached->del($key)){
		// 			//                    $this->log->warn(sprintf('%s: delete key:%s from memcached failure.', __FUNCTION__, $key));
		// 			$this->log->push_info('del memcached key:%s fail', array($key));
		// 		}
		// 	}
		// }
		$this->cache->memcached->del("corporations_$id");
		return $id;
	}


	
	/**
	 * __call 
	 * 
	 * @param mixed $func 
	 * @param mixed $args 
	 * @access protected
	 * @return mixed
	 */
	function __call($func, $args) {
		$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		return call_user_func_array(array($this->dao('/Dao_corporation',  $dao_param), $func), $args);
	}

	/**
	 * update_by_unique 
	 * 
	 * @param mixed $param 
	 * @access public
	 * @return mixed
	 */
	function update_by_unique($param) {
		$dao_param = array('active_group'=>$this->config->item('active_group'), 'id'=>0);
		unset($param['id']);
		$id  = $this->dao(sprintf('/Dao_%s', 'corporation'), $dao_param)->update_by_unique($param);
		if (intval($id)<=0){
						$param = array_merge($this->dao(sprintf('/Dao_%s', 'corporation'), $dao_param)->new_one(), $param);
			$this->dao(sprintf('/Dao_%s', 'corporation'), $dao_param)->insert($param);
		}else{
			$param['id'] = $id;

		}
		foreach ($this->_mkeys as $key_pattern => $keys){
			$temp = array();  
			foreach($keys as $key){
				$temp[$key] = $param[$key];
			}                 
			$key  = vsprintf($key_pattern, $temp);
			if (!$this->cache->memcached->del($key)){
				//                $this->log->warn(sprintf('%s: delete key:%s from memcached failure.', __FUNCTION__, $key));
				$this->log->push_info('del memcached key:%s fail', array($key));
			}                 
		}
		return $id;
	}
    /**
     * search优化版，支持like搜索
     * @TODO 结果集不使用缓存，因为目前的缓存系统不支持
     *
     * @param array $param
     * @access public
     * @return mixed
     */
    public function search_optimize($param = array()) {
        $dao_param = array('active_group' => $this->config->item('active_group'), 'id' => 0);
        $selected = empty($param['selected']) ? array() : $param['selected'];
        if (empty($param['ordersort'])) {
            $param['ordersort'] = 'created_at DESC';
        }

        $result = $this->dao('/Dao_corporation', $dao_param)->search_optimize($param);
        if (0 < $result['num']) {
            $corporations = $this->dao('/Dao_corporation', $dao_param)->get_multi(array_keys($result['result']), $selected);
            $result['result'] = $corporations;
        }
        return $result;
    }

    /** 修改公司指向
     * @param $param array [
     *      from    要改的公司id
     *      to      指向的公司id
     * ]
     * @return bool
     * @throws Exception
     */
    public function point($param){
    	$from = (int)$param['from'];
    	if($from < 0){
    		throw new Exception(sprintf('function: %s, parameter: from must greater than 0', __FUNCTION__),$this->config->item('parameter_err_no', 'err_no'));
    	}

    	$to = (int)$param['to'];
    	if($to < 0){
    		throw new Exception(sprintf('function: %s, parameter: to must greater than 0', __FUNCTION__),$this->config->item('parameter_err_no', 'err_no'));
    	}

        $res = $this->fetch_one_by_id($to);
        if(empty($res)) throw new Exception("指向公司不存在！",10002);
        $res = $this->fetch_one_by_id($from);
        if(empty($res)) throw new Exception("被指向的公司不存在！",10002);

        $this->parse_sql("update corporations set point_to='{$param['to']}' where id='{$param['from']}'",false);
        $this->cache->memcached->del("corporations_{$param['to']}");
        return true;
    }


    /** 修改公司信息
     * @param $param array [
     *      'uid'      => 1,                              // 用户ID
     *      'uname'    => 'kai.wang',                     // 用户名
     *      'id'       => 1,                              // 公司ID
     *      'alias'    => array('公司别名1', '公司别名2')   // 公司别名数组
     *      'industry' => array(1, 13)                    // 公司行业ID数组
     *      'status'   => 0,                              // 公司状态，0表示不启用,1表示启用
     *      'ka_top'   => 0                               // 公司重要度，0表示普通，1表示KA，2表示TOP
     *      'industry_main'   => 8                        // 主营行业ID 为空不处理
     *      'address'   => '地址'                         // 公司地址
	 *      'priority_type'   => 88                      // 该字段适用于修改行业，当该字段为88时，可修改type_id=3的行业数据为主营
     * ]
     */
    public function update_info($param){

        $corporation_id = (int) $param['id'];
        $status = (int) $param['status'];
        $ka_top = (int) $param['ka_top'];
        $industry_main = (int)$param['industry_main'];
        $time=date('Y-m-d H:i:s');


        /**
         * 修改别名
         */
        $corporation_aliases = $this->parse_sql("select * from corporations_aliases where corporation_id='$corporation_id' and is_deleted != 'Y'");
        //将旧的不在新的中的删掉
        foreach ($corporation_aliases as $k=>$old_alias){
            $delete_flag = true;
            foreach($param['alias'] as $new_alias){
                if($new_alias == $old_alias['alias']) $delete_flag=false;
            }
            if($delete_flag){
                $this->parse_sql("delete from corporations_aliases where is_deleted='Y' and corporation_id='$corporation_id' and alias='{$old_alias['alias']}'",false); //清洗已经被删除的重名的脏数据
                $this->parse_sql("update corporations_aliases set is_deleted='Y',updated_at='$time' where id='{$old_alias['id']}'",false);
                unset($corporation_aliases[$k]);
            }
        }
        //将新的不在旧的中添加
        foreach($param['alias'] as $sort=>$new_alias){
            $add_flag = true;
            foreach($corporation_aliases as $old_alias){
                if($new_alias == $old_alias['alias']) $add_flag=false;
            }
            if($add_flag){
                $this->parse_sql("insert into corporations_aliases(`type_id`,`corporation_id`,`sort`,`alias`) VALUES (1,$corporation_id,$sort,'$new_alias')",false);
            }
        }

        /**
         * 修改状态
         */
        $this->parse_sql("update corporations set status=$status,updated_at='$time' where id='{$corporation_id}'",false);

        /**
         * 修改地址
         */
        if (!empty($param['address'])) {
            $corporation_addresses = $this->parse_sql("select * from corporations_addresses where is_deleted='N' and corporation_id='$corporation_id'");
            $this->load->model('model_traffic');
            if(empty($corporation_addresses)){
                $this->parse_sql("insert into corporations_addresses(`type_id`,`corporation_id`,`address`) VALUES (1,$corporation_id,'{$param['address']}')",false);
                $this->model_traffic->sync_corporation_traffic([[ 'corporation_id' => $corporation_id, 'address' => $param['address']]], 'gsystem');
            }else{
                foreach ($corporation_addresses as $corporation_address) {
                    if($corporation_address['address'] !== $param['address']){
                        $this->parse_sql("delete from corporations_addresses where is_deleted='Y' and corporation_id=$corporation_id and address='{$corporation_address['address']}'",false);
                        $this->parse_sql("update corporations_addresses set is_deleted='Y',updated_at='$time' where id='{$corporation_address['id']}'",false);
                        $this->parse_sql("insert into corporations_addresses(`type_id`,`corporation_id`,`address`) VALUES (1,$corporation_id,'{$param['address']}')",false);

						// 同步gsystem交通信息
						
						$this->model_traffic->sync_corporation_traffic([[ 'corporation_id' => $corporation_id, 'address' => $param['address']]], 'gsystem');
					}
                }
            }

        }

        /**
         * 修改行业
         */
        //将行业id转换成行业名
        if (! empty($param['industry'])) {
            $industies_ids = implode(',', $param['industry']);
            $industies_res = $this->parse_sql("select `id`,`name` from industries where id in($industies_ids)");
        }else{
            $industies_res = array();
        }

        //获取目前生效的公司行业
        $corporation_industries = $this->parse_sql("select * from corporations_industries where is_deleted='N' and corporation_id='$corporation_id'");

        //将旧的没有存在新的中的删除
        foreach ($corporation_industries as $k=>$old_corporation_industry) {
            if(!in_array($old_corporation_industry['industry_id'],$param['industry'])){
                $this->parse_sql("delete from corporations_industries where is_deleted='Y' and corporation_id=$corporation_id and industry_id='{$old_corporation_industry['industry_id']}'",false);
                $this->parse_sql("update corporations_industries set is_deleted='Y',status=0,updated_at='$time' where id='{$old_corporation_industry['id']}'",false);
                unset($corporation_industries[$k]);
            }
        }
        //将新的没有存在的添加
        foreach($industies_res as $k=>$new_corporation_industry){
            $add_flag=true;
            foreach($corporation_industries as $old){
                if($new_corporation_industry['id'] == $old['industry_id']){
                    $add_flag=false;
                }
                $status = $industry_main == (int)$old['industry_id'] ? 1 : 0;
                $sort = $k+1;
				$where = isset($param['priority_type']) && $param['priority_type'] == 88 ? "id='{$old['id']}'": "type_id=1 and id='{$old['id']}'";
                $this->parse_sql("update corporations_industries set status=$status,`sort`=$sort,updated_at='$time' where {$where}",false);
            }
            if($add_flag){
                $status = $industry_main == (int)$new_corporation_industry['id'] ? 1 : 0;
                $sort = $k+1;
                $this->parse_sql("insert into corporations_industries(`type_id`,`corporation_id`,`sort`,`industry_id`,`industry_name`,`status`) VALUES (1,$corporation_id,$sort,{$new_corporation_industry['id']},'{$new_corporation_industry['name']}',$status)",false);
            }
        }

        /**
         * 修改KA/TOP
         */
        if (0 < $ka_top) {
            if (1 === $ka_top) {
                $is_ka = 1;
                $is_top = 0;
            } else {
                $is_ka = 0;
                $is_top = 1;
            }

            $res = $this->parse_sql("select * from corporations_tags where id=$corporation_id");
            if($res){
                $this->parse_sql("update corporations_tags set is_deleted='N',is_ka=$is_ka,is_top=$is_top,updated_at='$time' where id=$corporation_id",false);
            }else{
                $this->parse_sql("insert into corporations_tags(`id`,`is_ka`,`is_top`) VALUES ($corporation_id,$is_ka,$is_top)",false);
            }
        } else {
            $this->parse_sql("update corporations_tags set is_deleted='Y',updated_at='$time' where id=$corporation_id",false);
        }
    }

    /**
     * 重构单条查询 走sql缓存
     * @param  [int] $id [公司id]
     * @return [array]     [查询到的一条公司信息数据]   
     */
    public function fetch_one_by_id($id){
    	$id = (int)$id;
    	if($id < 0) {
    		throw new Exception(sprintf('function: %s, parameter: id must greater than 0', __FUNCTION__),$this->config->item('parameter_err_no', 'err_no'));
    	}
    	$result = $this->parse_sql("select * from corporations where id='$id'");
    	return $result[0];
    	// $cache_key = "corporations_$id";
     //    $cache_value = $this->cache->memcached->get($cache_key);
     //    if(empty($cache_value)){
     //        $result = $this->parse_sql("select * from corporations where id='$id'");
     //        $cache_value = json_encode($result[0]);
     //        $this->cache->memcached->save($cache_key,$cache_value);
     //        return $result[0];
     //    }else{
     //       	return json_decode($cache_value,true);
     //    }
    }

	/*
	 * 根据公司ID查询出其所有相关子公司
	 * @param $params['id'] 公司ID
	 * return array
	 */
	public function get_subsidiary_ids($id){
		$cache_key = "get_subsidiary_corporations_$id";
		if(empty($results = $this->cache->memcached->get($cache_key))){
			$corporations = $this->parse_sql("select * from corporations where parent_id={$id}");

			$results = [];
			foreach ($corporations as $corporation) {
				$corporation_id = (int) $corporation['id'];
				if ($id == $corporation_id) {
					continue;
				}

				$temp = [];
				$temp['sid'] = $corporation_id;
				$temp['sub_list'] = $this->get_subsidiary_ids($corporation_id);
				$results[] = $temp;
			}
			$this->cache->memcached->save($cache_key,json_encode($results),600);
			return $results;
		}
		return json_decode($results, true);
	}

	/*
	 * 根据公司ID、层级 查询出对应父类公司
	 * @param int id 公司ID
	 * @param int $depth 层级 1为顶级
	 * return int
	 */
	public function get_parent_corporation_id($id, $depth){
		$cache_key = "get_parent_corporation_id_{$id}_{$depth}";
		if(empty($results = $this->cache->memcached->get($cache_key))){
			$parent_ids = [];
			$this->get_parent_ids($id, $parent_ids);
			$reverse_results = array_reverse($parent_ids);
			if(empty($reverse_results)) $reverse_results = [$id];
			$results = array_key_exists($depth - 1, $reverse_results) ? $reverse_results[$depth - 1]: 0;
			$this->cache->memcached->save($cache_key,$results,600);
		}
		return $results;
	}

	/*
	 * 根据公司ID获取所有父类
	 * @param int $id 公司ID
	 * @param array $result 结果集
	 */
	public function get_parent_ids($id, &$result){
		$cache_key = "get_parent_ids_{$id}";
		if(empty($corporation = $this->cache->memcached->get($cache_key))){
			$corporation = $this->parse_sql("select * from corporations where id={$id}");
			$corporation = json_encode($corporation);
			$this->cache->memcached->save($cache_key,$corporation,600);
		}
		$corporation = json_decode($corporation, true);

		if(!empty($corporation = $corporation[0])){
			// 递归获取父类
			if($corporation['parent_id'] != $id && $corporation['parent_id'] > 0) {
				if(!in_array($corporation['parent_id'], $result)){
					$result[] = (int)$corporation['parent_id'];
					$this->get_parent_ids($corporation['parent_id'], $result);
				}else{
					array_pop($result);
				}
			}
		}
	}

	/*
	 * 根据公司ID获取所有相关父公司及子公司
	 * @param int $id 公司ID
	 * return array
	 */
	public function getCorporationSidsById($id){
		if(empty($id)) return [];

		$cache_key = "get_corporation_sids_by_id_{$id}";
		if(empty($results = $this->cache->memcached->get($cache_key))){
			$parent_id = $this->get_parent_corporation_id($id, 1); // 获取顶级父类
			$sids = [];
			$this->get_sids($parent_id, $sids);
			$results = array_merge([$parent_id], $sids);
			$results = array_values(array_unique($results));
			if(empty($results)) $results = [$id];
			$this->cache->memcached->save($cache_key,$results);
		}
		return $results;
	}

	/*
	 * 根据公司ID查询出其所有相关子公司
	 * @param int $id 公司ID
	 * return array
	 */
	public function get_sids($id, &$data){
		$cache_key = "get_sids_$id";
		if(empty($results = $this->cache->memcached->get($cache_key))){
			$corporations = $this->parse_sql("select * from corporations where parent_id={$id}");

			foreach ($corporations as $corporation) {
				$corporation_id = (int) $corporation['id'];
				if(in_array($corporation_id,$data)) continue;
				$data[] = $corporation_id;
				$temp['sub_list'] = $this->get_sids($corporation_id, $data);
			}
			$this->cache->memcached->save($cache_key,$results);
		}
	}

	/*
	 * 根据公司ID获取公司基础信息
	 * @param int $id 公司ID
	 * return array
	 */
	public function getCorporationBaseinfos($id){
		$cache_key = 'GET_CORPORATION_BASEINFOS_' . md5('getCorporationBaseinfos' . $id);
		if(empty($result = $this->cache->memcached->get($cache_key))){
			$detail_sql = "select t1.id,t1.name,t1.website,t2.address,t3.introduction,t7.name financing,t8.name sacle,t9.name nature from corporations t1 "
				. "LEFT JOIN corporations_addresses t2 on t2.corporation_id = t1.id and t2.is_deleted = 'N' "
				. "LEFT JOIN corporations_baikes t3 on t3.corporation_id = t1.id and t3.is_deleted = 'N' "
				. "LEFT JOIN corporations_baseinfos_mapping t4 on t4.corporation_id = t1.id and t4.type_id = 1 "
				. "LEFT JOIN corporations_baseinfos_mapping t5 on t5.corporation_id = t1.id and t5.type_id = 2 "
				. "LEFT JOIN corporations_baseinfos_mapping t6 on t6.corporation_id = t1.id and t6.type_id = 3 "
				. "LEFT JOIN corporations_baseinfos_dictionary t7 on t7.id = t4.baseinfos_dictionary_id "
				. "LEFT JOIN corporations_baseinfos_dictionary t8 on t8.id = t5.baseinfos_dictionary_id "
				. "LEFT JOIN corporations_baseinfos_dictionary t9 on t9.id = t6.baseinfos_dictionary_id "
				. "where t1.id = {$id} "
				. "GROUP BY t1.id";
			$detail = $this->parse_sql($detail_sql);
			$result = [];
			if(!empty($detail)){
				$result = [
					'id' 			=> $detail[0]['id'],
					'name' 			=> $detail[0]['name'],
					'website' 		=> !empty($detail[0]['website']) ? $detail[0]['website']: '',
					'address' 		=> !empty($detail[0]['address']) ? $detail[0]['address']: '',
					'introduction' 	=> !empty($detail[0]['introduction']) ? $detail[0]['introduction']: '',
					'financing' 	=> !empty($detail[0]['financing']) ? $detail[0]['financing']: '',
					'sacle' 		=> !empty($detail[0]['sacle']) ? $detail[0]['sacle']: '',
					'nature' 		=> !empty($detail[0]['nature']) ? $detail[0]['nature']: '',
					'industries'	=> [],
					'aliases'		=> [],
					'weals'			=> [],
					'products'		=> []
				];

				// 行业数据
				$industries_sql = "select industry_id,industry_name,status from corporations_industries where corporation_id = {$id} and is_deleted = 'N' order by id";
				$industries_list = $this->parse_sql($industries_sql);
				if(!empty($industries_list)){
					foreach($industries_list as $val){
						$temp = [ 'id' => $val['industry_id'], 'name' => $val['industry_name']];
						if($val['status'] == 1){
							$result['industries'] = [$temp];
							break;
						}
						$result['industries'][] = $temp;
					}
				}

				// 简称
				$aliases_sql = "select id,alias from corporations_aliases where corporation_id = {$id} and is_deleted = 'N' order by id";
				$aliases_list = $this->parse_sql($aliases_sql);
				if(!empty($aliases_list)){
					foreach($aliases_list as $val){
						$temp = [ 'id' => $val['id'], 'name' => $val['alias'] ];
						$result['aliases'][] = $temp;
					}
				}

				// 福利
				$aliases_sql = "select t1.id,t2.name,t2.status from corporations_weal_map t1 INNER JOIN corporations_weal t2 on t2.id = t1.weal_id where t1.corporation_id = {$id} and t1.is_deleted = 'N' order by t1.id";
				$aliases_list = $this->parse_sql($aliases_sql);
				if(!empty($aliases_list)){
					foreach($aliases_list as $val){
						$temp = [ 'id' => $val['id'], 'name' => $val['name'], 'status' => $val['status']];
						$result['weals'][] = $temp;
					}
				}

				// 产品
				$aliases_sql = "select id,title,pic_url,description from corporations_product_cluster where corporation_id = {$id} and is_deleted = 'N' order by id";
				$aliases_list = $this->parse_sql($aliases_sql);
				if(!empty($aliases_list)){
					foreach($aliases_list as $val){
						$temp = [ 'id' => $val['id'], 'name' => $val['title'], 'pic_url' => $val['pic_url'], 'description' => $val['description'], ];
						$result['products'][] = $temp;
					}
				}
			}

			$this->cache->memcached->save($cache_key,$result);
		}
		return $result;
	}
}
