<?php
class Logic_region extends Gsystem_logic {
	/**
	 * __construct
	 *
	 * @access protected
	 * @return mixed
	 */
	function __construct() {
		parent::__construct();
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
		return call_user_func_array(array($this->model('Model_region'), $func), $args);
	}
	/**
	 * detail
	 *
	 * @param mixed $id
	 * @access public
	 * @return mixed
	 */
	function detail($param) {
		$c         = array();
		$id        = $param['id'];
		$selected  = isset($param['selected']) ? parse_selected($param['selected']) : array();
		if (!$selected){
			$selected  = $selected + array('region'=>array())
				;
		}

		$c['region'] = $this->model('Model_region')->fetch_one_by_id($id, $selected['region']);
		if (empty($c['region']) ){
			throw new Exception(sprintf('%s: The contact id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));
		}


		return $c;
	}
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$regions = $this->model('Model_region')->search($param, $param['page'], $param['pagesize']);
		$regions['results'] = array_values($regions['results']);
		//获取城市下是否有城市图片
		if(1 == $param['check_city_pic'])
		{
			foreach($regions['results'] as $key=>$one)
			{
				$city_pic_num = 0;
				$city_id = $one['id'];
				$response = $this->model('model_city_pic_normal')->search_all(array('city_id'=>$city_id));
				if(!empty($response))
				{
				   $city_pic_num = count($response);
				}
				$regions['results'][$key]['city_pic_num']= intval($city_pic_num);
			}
		}

		return $regions;
	}
    /**
     * @get_province  获取省份
     *
     * @param none
     * @access public
     * @return array
     */
    function get_province(){
		$cache_key = 'GSYSTEM_GET_PROVINCE_' . md5('get_province');
		if(empty($rs = $this->model('Model_region')->cache->memcached->get($cache_key))){
			$param = [
				'parent_id' => '1',
				'ordersort' => 'displayorder asc',
			];
			$rs = $this->model('Model_region')->search_all($param);
			$rs = array_values($rs);
			$this->model('Model_region')->cache->memcached->save($cache_key, $rs, 86400); // 缓存列表24小时
		}

		return $rs;
    }
    /**
     * @city_kv 获取所有城市
     *
     * @param none
     * @access public
     * @return array
     */
    function city_kv(){
		$data = array();
        $param1['level'] = '1';
		$param1['ordersort'] = 'id asc';
        $city1 = $this->model('Model_region')->search_all($param1);
		foreach ($city1 as $key => $list) {
			$data[$key] = $list['name'];
		}
        $param2['level'] = '2';
		$param2['ordersort'] = 'id asc';
        $city2 = $this->model('Model_region')->search_all($param2);
		foreach ($city2 as $key => $list) {
			$data[$key] = $list['name'];
		}
		return $data;
	}
    /**
     *
     * @根据ids 返回需要字段
     * @param array(ids=>array,'selected'=>array)
     *
     */
    function city_byids($param) {
        if (!isset($param['ids']) || !is_array($param['ids'])) return false;
        $multis = $result = $bakdata = array();
        foreach ($param['ids'] as $id) {
            if (intval($id) > 0 ) {
                $multis[] = intval($id);
            }
        }
        $result = $this->model('Model_region')->get_multi($multis);
        if (!empty($param['selected'])) {
            foreach ($result as $cid => $clist) {
                $rs = array();
                foreach ($param['selected'] as $field) {
                    $rs[$field] = isset($clist[$field]) ? $clist[$field] : '';
                }
                $bakdata[$cid] = $rs;
            }

            unset($result,$clist,$rs);
            return $bakdata;
        } else {

            return $result;
        }
	}
    /**
     *
     * @定向获取城市数据接口
     * @param array('loadtype'=>'All,province,city','selected'=>array(id,name))
     * @All 全部记录对应List
     * @province 全部省级单位记录对应List
     * @city 全部市单位记录对应List
     */
    function Loadcitys($param) {
        if (!isset($param['loadtype']) || !in_array($param['loadtype'],array('All','province','city'))) return false;
        $multis = $result = $bakdata = array();
        $type = trim($param['loadtype']);
        switch ($type) {
            case 'All':
                $tmpdata = $this->city_kv();
                $cids = array_keys($tmpdata);
                unset($tmpdata);
                break;
            case 'province':
                $provinces = $this->model('Model_region')->search_all(array('level'=>'1', 'ordersort'=>'id asc'));
                foreach ($provinces as $k=>$v) {
                    $cids[] = $k;
                }
                unset($provinces);
                break;
            case 'city';
                $citys = $this->model('Model_region')->search_all(array('level'=>'2', 'ordersort'=>'id asc'));
                foreach ($citys as $k=>$v) {
                    $cids[] = $k;
                }
                unset($citys);
                break;
            default :
                $cids = array();
                break;
        }

        $result = $this->model('Model_region')->get_multi($cids);
        if (isset($param['selected']) && !empty($param['selected'])) {
            foreach ($result as $cid => $clist) {
                $rs = array();
                foreach ($param['selected'] as $field) {
                    $rs[$field] = isset($clist[$field]) ? $clist[$field] : '';
                }
                $bakdata[$cid] = $rs;
            }

            unset($result,$clist,$rs);
            return $bakdata;
        } else {

            return $result;
        }
	}
    /**
     * @get_city  返回该省份下的所有城市/单个城市
     *
     * @param array('province_id','city_id')
     * @access public
     * @return array
     */
    function get_city($param){
		//过滤的二级市名
		$outcitys = array(
				'2'=>'33',		//北京
				'3'=>'34',		//天津
				'23'=>'267',    //重庆
				'10'=>'105',	//上海
				'3905'=>'3906', //澳门
				'3907'=>'3908', //香港
				'3954'=>'3955', //台湾
				'3956'=>'3957',	//海外
			);
		if(isset($param['province_id'])) {
			$province_id = intval($param['province_id']);
			if ($province_id < 1 || empty($param)) {
				throw new Exception(sprintf('%s:%s input parameters province_id is error.',
								__CLASS__, __FUNCTION__),
							$this->config->item('parameter_err_no', 'err_no'));
			}
			$data['parent_id'] = $province_id;
			$data['is_deleted'] = 'N';
			$data['ordersort'] = 'id asc';
			$citys = $this->model('Model_region')->search_all($data);
			if (empty($citys)) {
				$citys = $this->model('Model_region')->fetch_one_by_id($province_id);
				$citys = array('num'=>'1','results'=> array($province_id => $citys));
			}

			if (isset($outcitys[$province_id]) && isset($param['model_type']) && $param['model_type']=='high_search') {
				$outcityid = $outcitys[$province_id];
			} else {
				$outcityid = 0;
			}

			foreach ($citys as $k => $list) {
				if ($list['id'] != $outcityid) {
					$cs[] = $list;
				}
			}
			$citys = $cs;
		} else {
			$city_id = intval($param['city_id']);
			if ($city_id < 1) {
				throw new Exception(sprintf('%s:%s input parameters city_id is error.',
								__CLASS__, __FUNCTION__),
							$this->config->item('parameter_err_no', 'err_no'));
			}

			$citys = $this->model('Model_region')->fetch_one_by_id($city_id);

		}

		return $citys;
	}
    /**
     * @ 根据省份、城市id 返回二级id
     * @ param array('2','4','201','55')
     * @ return array;
     *
     */
    function loadcitiesid($param){
        $loadtmp = $cids = $ids = $parentids = $province = $parentids = $cities =array();
        $oversea = array('3905','3907','3954','3956');
        if (empty($param)) return false;
        foreach ($param as $cid) {
            if (intval($cid) <= 1) continue;
            if (!isset($loadtmp[$cid]) && (intval($cid) < 33 || in_array($cid, $oversea))) {
                //此为一级id 只需获取二级城市id
                $province[$cid] =$this->get_city(array('province_id'=>$cid));
            } elseif (!isset($loadtmp[$cid]) && intval($cid) > 1) {
                $cities[] = $tmpc = $this->get_city(array('city_id'=>$cid));
                $parentids[] = $tmpc['parent_id'];
                $tmpc = '';
            }
            $loadtmp[$cid] = true;
        }
        array_unique($parentids);
        //过滤掉不要的二级地区
        foreach ($parentids as $pid) {
            if (isset($province[$pid])) {
                unset($province[$pid]);
            }
        }
        foreach ($province as $pi => $plist) {
            foreach ($plist as $pl) {
                if (!in_array($pl['id'],$ids)) {
                    $ids[] = $pl['id'];
                }
            }
            $ids[] = $pi;
        }
        foreach ($cities as $clist) {
            if (!in_array($clist['id'],$ids)) {
                $ids[] = $clist['id'];
            }
        }
        unset($loadtmp ,$cids ,$parentids ,$province ,$parentids ,$cities);
        return $ids;
	}
	/**
	 * @ city_ids
	 * 返回城市id 逗号分割
	 */

	function city_ids($citys) {
		if(!is_string($citys)) {
			throw new Exception('参数错误', 100001);
		}
		//缓存同一请求 24小时
		$cache_key = 'GSYSTEM_CITY_IDS_' . md5('city_ids_' . $citys);
		if(empty($cids = $this->model('Model_region')->cache->memcached->get($cache_key))) {
			$cid = []; // 城市ID集
			$cids = ''; // 城市ID字符串

			// 过滤特殊字符 如果过滤完后为空 直接return
			$old_citys = $citys;
			$citys = str_replace(array('?','+','*','[',']','{','}','|','.','^','$','(',')','省','市','区'),'',$citys);
			if(empty($citys)) return $cids;

			// 缓存level为2的城市列表
			$all_citys = $this->get_regions_by_level([2,3]);
			if(!empty($citys)) {
				// 城市列表中匹配

				// 白名单
				$white_list = ['朝阳区'];
				$white_identity = false;
				if(in_array($old_citys, $white_list)){
					$white_identity = true;
				}

				$wight = strrpos($citys, '北京') !== false && strrpos($citys, '朝阳') !== false;//白名单 北京 朝阳
				foreach($all_citys as $cids) {
					if($wight && $cids['name'] == '朝阳市') continue;

					// 如果是白名单用户 则直接匹配
					if($white_identity) {
						if ($cids['name'] == $old_citys) {
							array_push($cid, $cids['id'], $cids['parent_id']);
							break;
						}
						continue;
					}

					$tmp_city_name = str_replace(array('省','市','区'), '', $cids['name']);
					// 如果当前城市名于请求参数相同 or 当前城市名存在于请求参数中 or 请求参数存在于当前城市名中 则array_push至$cid，从请求参数中过滤掉当前城市名
					if($citys == $tmp_city_name || strrpos($citys, $tmp_city_name) !== false || strrpos($tmp_city_name, $citys) !== false) {
						array_push($cid, $cids['id'], $cids['parent_id']);
						$citys = str_replace($tmp_city_name, '', $citys);
					}
				}

				// 如果在城市列表中没有匹配到，则在省列表匹配
				if(empty($cid)) {
					$provinces = $this->get_province(); // 获取省列表
					foreach($provinces as $pids) {
						$tmp_provinces_name = str_replace(array('省','市','区'), '', $pids['name']);
						if($citys == $tmp_provinces_name || strrpos($citys, $tmp_provinces_name) !== false || strrpos($tmp_provinces_name, $citys) !== false) {
							array_push($cid, $pids['id']);
							$citys = str_replace($tmp_provinces_name, '', $citys);
						}
					}
				}
				$cid = array_unique($cid);
				sort($cid);
				$cids = implode(',',$cid);
			}
			$this->model('Model_region')->cache->memcached->save($cache_key, $cids, 86400);
		}

		return $cids;
	}

	/*
	 * 根据level获取区域
	 * @param array $level 等级
	 * return array
	 */
	public function get_regions_by_level($level){
		if(empty($level)) return [];
		return $this->model('Model_region')->get_regions_by_level($level);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
