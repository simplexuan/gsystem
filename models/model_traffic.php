<?php

/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2017/1/16
 * Time: 14:11
 */
class model_traffic extends Gsystem_model
{
    private $baidu_ak = 'bAF9v1ZluIZzI1mT4BmYkPlKGG7ZUN67';
    private $baidu_geocoder_url = 'http://api.map.baidu.com/geocoder/v2/?';
    private $baidu_search_url = 'http://api.map.baidu.com/place/v2/search?';
    const tobusiness    = 'tobusiness';
    const gsystem       = 'gsystem';
    const tobusiness_db = 'tobusiness_traffic';
    const gsystem_db    = 'gsystem_traffic';
    private $address_filter_rule =  [
        '、','。','·','ˉ','ˇ','¨','〃','々','—','～','‖','…','‘','’','“','”','？','：','〔','〕','〈','〉','《','》',
        '「','」','『','』','〖','〗','【','】','±','×','÷','∶','∧','∨','∑','∏','∪','∩','∈','∷','√','⊥','∥',
        '∠','⌒','⊙','∫','∮','≡','≌','≈','∽','∝','≠','≮','≯','≤','≥','∞','∵','∴','♂','♀','°','′','″','℃','＄',
        '¤','￠','￡','‰','§','№','☆','★','○','●','◎','◇','◆','□','■','△','▲','※','→','←','↑','↓','〓','　','！',
        '＂','￥','％','＆','＇','＊','＋','，','－','．','／','；','＜','=','＞','＠','＼','＾','＿','｀','｛','｜','｝',
        '￣',' ','!','@','$','%','^','&','*','[',']','|','?','+','{','}','.','……',' ','\t','\n','\r','\f','\v','/'
    ];

    private $traffic_balck_list = [
        '大田路与山海关路交叉口',
        '四川省成都市武侯区天府大道',
        '闵行区老沪闵路1号(近沪闵路)',
        '金山教育附近',
        '广东省广州市白云区金沙洲路',
        '江苏省苏州市金阊区',
        '北京市海淀区',
        '惠州大道11号佳兆业广场4楼425',
        '布吉街道龙岭社区汇食街裕丰苑1楼102室'
    ];


    /*
     * 根据地址获取交通信息
     * @param string $address 地址
     * @param boolean $traffic_status 是否需要交通信息
     * return array
     */
    public function get_traffic_info_by_address($address, $traffic_status = true){
        $this->log->debug('根据地址获取交通信息，开始处理地址...');
        $traffic_status_key = $traffic_status ? 1: 2;
        $cache_key = 'CORPORATION_TRAFFIC_' . md5('get_traffic_info_by_address_' . $address. '____' . $traffic_status_key);
        if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))){
            $result = [];
            $curl_address = urlencode($address);
            // 获取地址的经纬度
            $address_url = $this->baidu_geocoder_url . 'address=' . $curl_address . '&output=json&ak=' . $this->baidu_ak;
            $address_res = send_curl($address_url, 'GET');

            $this->log->info("根据地址获取经纬度的结果集：" . json_encode($address_res, JSON_UNESCAPED_UNICODE));
            if($address_res['status'] == 0 && !empty($address_res['result'])){
                $result = [
                    'address' => $address,
                    'location' => [
                        'lng' => isset($address_res['result']['location']['lng']) ? $address_res['result']['location']['lng'] : 0,
                        'lat' => isset($address_res['result']['location']['lat']) ? $address_res['result']['location']['lat'] : 0,
                    ],
                    'city_name' => '',
                    'province' => '',
                    'district' => '',
                    'metro_list' => [],
                    'bus_list' => [],
                ];

                if(!empty($result['location']['lng']) && !empty($result['location']['lat'])){
                    // 根据经度和纬度获取城市名称
                    $city_url = $this->baidu_geocoder_url . 'ak=' . $this->baidu_ak. '&location=' . $result['location']['lat']. ','. $result['location']['lng']. '&output=json&pois=0';
                    $city_res = send_curl($city_url, 'GET');
                    $result['city_name'] = isset($city_res['result']['addressComponent']['city']) ? $city_res['result']['addressComponent']['city'] : '';
                    $result['province'] = isset($city_res['result']['addressComponent']['province']) ? $city_res['result']['addressComponent']['province'] : '';
                    $result['district'] = isset($city_res['result']['addressComponent']['district']) ? $city_res['result']['addressComponent']['district'] : '';

                    $this->log->info('根据经纬度获取城市的结果集...');

                    // 获取地址周围地铁、公交数据
                    $traffic_url = $this->baidu_search_url . 'query=地铁$公交&radius=1000&page_size=50&page_num=0&scope=2&location='. $result['location']['lat'] . ',' . $result['location']['lng'] . '&output=json&ak=' . $this->baidu_ak;
                    $traffic_res = send_curl($traffic_url, 'GET');

                    if($traffic_status){
                        $this->log->info('获取地址周围地铁、公交数据的结果集...');
                        if($traffic_res['status'] == 0 && $traffic_res['total'] > 0){
                            foreach ($traffic_res['results'] as $val){
                                $list_name = strpos($val['address'], '地铁') === false ? 'bus_list': 'metro_list';
                                $result[$list_name][] = [
                                    'name'      => isset($val['name']) ? $val['name'] : '',
                                    'lng'       => isset($val['location']['lng']) ? $val['location']['lng'] : '',
                                    'lat'       => isset($val['location']['lat']) ? $val['location']['lat'] : '',
                                    'address'   => isset($val['address']) ? $val['address'] : '',
                                    'distance'  => isset($val['detail_info']['distance']) ? $val['detail_info']['distance'] : '',
                                ];
                            }
                        }
                    }
                }
            }
            $this->cache->memcached->save($cache_key, json_encode($result), 2592000); // 缓存30天
        }
        $this->log->debug('根据地址获取交通信息处理完毕...');
        return $result;
    }

    /*
     * 同步公司交通信息
     * @param array $param公司地址信息 [ ['id'=>'1','address'=>'地址'] ]
     * @param string $type 数据类型
     * return bollean
     */
    public function sync_corporation_traffic($param, $type){
        $this->log->debug('开始同步公司信息...');

        $success_num = 0;
        $error_list = [];
        $db = $type == self::tobusiness ? self::tobusiness_db: self::gsystem_db;
        foreach($param as $val){
            if(empty($val['corporation_id']) || empty($val['address'])){
                $error_list[] = $val;
                continue;
            }

            if($type == self::tobusiness && !is_numeric($val['address_id']) && $val['address_id'] < 1){
                $error_list[] = $val;
                continue;
            }

            $this->log->debug('开始同步公司信息：公司ID->' . $val['corporation_id'] . '，公司地址->' . $val['address']);
            // 根据地址获取交通信息
            $traffic_info = $this->get_traffic_info_by_address($val['address']);

            if(empty($traffic_info)){
                $this->log->debug($val['address'] . '该地址无效...');
                continue;
            }

            $traffic_info['city_id'] = $this->get_region_id($traffic_info); // 获取区域ID

            $this->log->debug('开始同步公司信息...');
            // 更新公司交通信息
            $this->operate_corporation_traffic($traffic_info, $val, $db);
            $success_num++;
        }

        return [
            'total_num' => count($param),
            'success_num' => $success_num,
            'error_list' => [
                'total' => count($error_list),
                'list' => $error_list,
            ],
        ];
    }

    /*
     * 获取城市ID，精确到区域
     * @param array $area city_name,province,district
     * @param boolean $selected_status 是否返回所有字段
     * return int
     */
    public function get_region_id($area, $selected_status = false){
        if(empty($area)) return 0;

        $selected_status_key = $selected_status ? 1: 2;
        $cache_key = "get_region_id_" . md5(serialize($area) . '_' . $selected_status_key);
        if(empty($result = $this->cache->memcached->get($cache_key))) {
            $province_region = [];
            if (!empty($area['province'])) {
                $province = str_replace(['省', '市', '壮族自治区', '自治区'], '', $area['province']);
                $sql = $province_sql = "select id from regions_area where level = 1 and id > 1000000 and name = '{$province}'";
                $province_region = $this->parse_sql($sql);
            }

            $city_region = [];
            if (!empty($area['city_name'])) {
                $sql = "select id from regions_area where level = 2 and id > 1000000 and name = '{$area['city_name']}'";
                if (isset($province_sql)) {
                    $sql .= " and parent_id = ($province_sql)";
                }
                $city_region = $this->parse_sql($sql);
            }

            $district_region = [];
            if (!empty($area['district']) && !empty($area['city_name'])) {
                $sql = "select * from regions_area where level = 3 and id > 1000000 and name = '{$area['district']}' and parent_id = ($sql)";
                $district_region = $this->parse_sql($sql);
            }

            $region = !empty($district_region) ? $district_region: (!empty($city_region)? $city_region: $province_region);
            if($selected_status){
                $result = !empty($region) ? $region[0]: [];
            }else{
                $result =  $region[0]['id'] > 0 ? $region[0]['id'] : 0;
            }

            $this->cache->memcached->save($cache_key,$result, 604800); // 缓存一周
        }
        return $result;
    }


    /*
     * 操作数据库的公司交通信息
     * @param array $data 城市地址数据
     * @param array $address 地址原始数据
     * @param string $db 数据库名
     * return boolean
     */
    public function operate_corporation_traffic($data, $address, $db){
        if(empty($data['address'])) return false;
        $address_id = $address['address_id'] > 0 ? intval($address['address_id']): 0;

        // 删除公司地址相关交通信息
        $this->delete_address_traffic($address['corporation_id'], $address_id, $db);

        // 新增公司地址关联信息
        $traffic_address = str_replace($this->address_filter_rule,'',$data['address']);
        $address_values = "(" . $address['corporation_id'] . "," .$data['city_id'] . ",'" . addslashes($traffic_address)  . "'," . $address_id . ")";
        $traffic_address_id = $this->parse_sql("insert INTO corporations_traffic_address (`corporation_id`,`city_id`,`address`,`original_address_id`) VALUES $address_values",false,true,$db);

        $this->log->debug('公司地址关联关系插入成功...');

        $address_data = [
            'cid' => $address['corporation_id'],
            'address' => $data['address'],
            'location' => $data['location'],
            'address_id' => $traffic_address_id,
        ];

        // 新增公司地铁信息
        foreach($data['metro_list'] as $metro){
            $this->add_corporations_traffic($metro, $address_data, 0, $db);
        }
        // 新增公司公交信息
        foreach($data['bus_list'] as $bus){
            $this->add_corporations_traffic($bus, $address_data, 1, $db);
        }
        return true;
    }

    /*
     * @param int $corporation_id 公司ID
     * @param int $address_id 地址ID
     * @param string $db 数据库名
     */
    public function delete_address_traffic($corporation_id, $address_id, $db){
        // 删除交通地址表对应数据
        $traffic_address = $this->parse_sql("select id from corporations_traffic_address where `corporation_id` = {$corporation_id} and `original_address_id` = '{$address_id}'", true, false, $db);
        $address_ids = '(';
        foreach($traffic_address as $val){
            $address_ids .= $val['id'] . ',';
        }
        $address_ids = rtrim($address_ids, ',') . ')';

        if(empty($traffic_address)) return true;
        $this->parse_sql("delete from corporations_traffic_address where `id` in {$address_ids}",false, false, $db);

        // 删除交通信息表数据
        $traffic_list = $this->parse_sql("select id from corporations_traffic where `address_id` in {$address_ids}", true, false, $db);

        if(empty($traffic_list)) return true;
        $traffic_ids = '(';
        foreach($traffic_list as $val){
            $traffic_ids .= $val['id'] . ',';
        }
        $traffic_ids = rtrim($traffic_ids, ',') . ')';

        $this->parse_sql("delete from corporations_traffic where `address_id` in {$address_ids}", false, false, $db);
        $this->parse_sql("delete from corporations_traffic_relation where `tid` in {$traffic_ids}", false, false, $db);
        return true;
    }

    /*
     * 新增bi 公司交通信息
     * @param array $traffic_data 交通信息
     * @param array $address_data 地址信息
     * @param int $type 交通类型 0：地铁 1：公交
     * @param string $db 数据库
     */
    public function add_corporations_traffic($traffic_data, $address_data, $type = 0, $db){
        $traffic_values  = "(" . $address_data['cid'] . ",'";
        $traffic_values .= $address_data['location']['lng'] . "','";
        $traffic_values .= $address_data['location']['lat'] . "','";
        $traffic_values .= $traffic_data['name'] . "','";
        $traffic_values .= $traffic_data['lng'] . "','";
        $traffic_values .= $traffic_data['lat'] . "',";
        $traffic_values .= $address_data['address_id'] . ",'";
        $traffic_values .= $type . "','";
        $traffic_values .= $traffic_data['distance'] . "')";

        $traffic_id = $this->parse_sql("insert into corporations_traffic(`corporation_id`,`c_lng`,`c_lat`,`station_name`,`s_lng`,`s_lat`,`address_id`,`transportation`,`distance`) VALUES $traffic_values", false, true, $db);

        $traffic__relation_values = '';
        $address_arr = explode(';', $traffic_data['address']);
        foreach($address_arr as $a){
            if($this->checkTrafficBlackList($a)) continue;
            $traffic__relation_values .= "('".$traffic_id."','".$a."'),";
        }
        $traffic__relation_values = rtrim($traffic__relation_values,',');
        $this->parse_sql("insert into corporations_traffic_relation(`tid`,`traffic_name`) VALUES $traffic__relation_values", false, false, $db);
        $this->log->debug('公司交通信息插入成功...');
        return true;
    }

    /*
     * 检查交通信息名是否在黑名单中
     * @param string $name 名字
     * return boolean
     */
    public function checkTrafficBlackList($name){
        return in_array($name, $this->traffic_balck_list) ? true: false;
    }

    /*
     * 获取职位地址交通信息
     * @param int $id 职位ID
     * return array
     */
    public function getPositionTraffic($id){
        if(empty($id)) return [];
        $cache_key = 'TRAFFIC_' . md5('getPositionTraffic' . $id);
        if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
            $sql = "select pta.corporation_id,pta.position_id,ad.`name`,ad.region_id,`at`.* from position_traffic_address pta "
                . "inner join address_dictionary ad on ad.id = pta.address_id INNER JOIN address_traffic at "
                . "on `at`.address_id = ad.id where pta.position_id = {$id}";

            $traffic = $this->parse_sql($sql, true, false, self::gsystem_db);

            $result = [];
            if (!empty($traffic)) {
                $result = [
                    'corporation_id'    => $traffic[0]['corporation_id'],
                    'position_id'       => $traffic[0]['position_id'],
                    'address'           => $traffic[0]['name'],
                    'region_id'         => $traffic[0]['region_id'],
                    'address_lng'       => $traffic[0]['c_lng'],
                    'address_lat'       => $traffic[0]['c_lat'],
                    'traffic'           => [],
                ];
                foreach ($traffic as $val) {
                    $temp = [
                        'station_name'      => $val['station_name'],
                        's_lng'             => $val['s_lng'],
                        's_lat'             => $val['s_lat'],
                        'transportation'    => $val['transportation'],
                        'distance'          => $val['distance'],
                    ];
                    $temp['traffic_info'] = $this->getAddressTrafficRelationByTid($val['id']);
                    $result['traffic'][] = $temp;
                }
                $result['traffic_convenient'] = !empty($result['traffic']) ? 1: 0;
            }
            $this->cache->memcached->save($cache_key, json_encode($result), 3600);
        }

        return $result;
    }

    /*
     * 根据traffic_id获取交通信息
     * @param int $id 交通ID
     * return array
     */
    public function getAddressTrafficRelationByTid($id){
        if(empty($id)) return [];
        $cache_key = 'TRAFFIC_' . md5('getAddressTrafficRelationByTid' . $id);
        if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
            $sql = "select traffic_id tid,metro_id from traffic_metros where traffic_id = {$id}";

            $traffic_realation = $this->parse_sql($sql, true, false, self::gsystem_db);
            $result = !empty($traffic_realation) ? $traffic_realation: [];
            $this->cache->memcached->save($cache_key, json_encode($result), 3600);
        }

        return $result;
    }


    /*
     * 获取公司地址交通信息
     * @param int $id 公司ID
     * @param int $position_id 职位ID
     * return array
     */
    public function getCorporationTraffic($id, $position_id){
        if(empty($id)) return [];
        $cache_key = 'TRAFFIC_' . md5('getCorporationTraffic' . $id);
        if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
            $sql = "select cta.city_id,cta.address,ct.* from corporations_traffic_address cta INNER JOIN "
                . "corporations_traffic ct ON ct.address_id = cta.id where cta.corporation_id = {$id}";

            $traffic = $this->parse_sql($sql, true, false, self::gsystem_db);

            $result = [];
            if (!empty($traffic)) {
                $result = [
                    'corporation_id'    => $traffic[0]['corporation_id'],
                    'position_id'       => $position_id,
                    'address'           => $traffic[0]['address'],
                    'region_id'         => $traffic[0]['city_id'],
                    'address_lng'       => $traffic[0]['c_lng'],
                    'address_lat'       => $traffic[0]['c_lat'],
                    'traffic'           => [],
                ];
                foreach ($traffic as $val) {
                    $temp = [
                        'station_name'      => $val['station_name'],
                        's_lng'             => $val['s_lng'],
                        's_lat'             => $val['s_lat'],
                        'transportation'    => $val['transportation'],
                        'distance'          => $val['distance'],
                    ];
                    $temp['traffic_info'] = $this->getCorporationAddressTrafficRelationByTid($val['id']);
                    $result['traffic'][] = $temp;
                }
            }
            $this->cache->memcached->save($cache_key, json_encode($result), 3600);
        }

        return $result;
    }


    /*
     * 根据traffic_id获取交通信息
     * @param int $id 交通ID
     * return array
     */
    public function getCorporationAddressTrafficRelationByTid($id){
        if(empty($id)) return [];
        $cache_key = 'TRAFFIC_' . md5('getCorporationAddressTrafficRelationByTid' . $id);
        if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
            $sql = "select * from corporations_traffic_relation where tid = {$id}";

            $traffic_realation = $this->parse_sql($sql, true, false, self::gsystem_db);
            $result = !empty($traffic_realation) ? $traffic_realation: [];
            $this->cache->memcached->save($cache_key, json_encode($result), 3600);
        }

        return $result;
    }

    /*
     * 处理交通重名
     */
    public function handleTrafficTmp(){
        $sql = "select tmt.name,tmt.traffic_id,tmt.city_id,ma.metro_id,pta.position_id from traffic_metros_tmp tmt INNER JOIN address_traffic at on at.id = tmt.traffic_id INNER JOIN position_traffic_address pta on pta.address_id = `at`.address_id INNER JOIN metro_aliases ma on ma.city_id = tmt.city_id and ma.`name` = tmt.`name`";
        $tmps = $this->parse_sql($sql, true, false, 'gsystem_traffic');

        if(empty($tmps)){
            return '无数据处理';
        }

        $result = [];
        foreach($tmps as $val){
            $tmp_where = " name = '{$val['name']}' and traffic_id = {$val['traffic_id']} and city_id = {$val['city_id']} ";

            // 验证是否已存在交通地铁关联数据（traffic_metros）
            $traffic_metros_detial = $this->parse_sql("select * from traffic_metros where traffic_id = {$val['traffic_id']} and metro_id = {$val['metro_id']}", true, false, 'gsystem_traffic');
            // 如果已存在 则删除临时数据
            if(!empty($traffic_metros_detial)){
                $this->parse_sql("delete from traffic_metros_tmp where $tmp_where", false, false, 'gsystem_traffic');
                continue;
            }

            // 不存在 则新增关联数据
            $this->parse_sql("insert into traffic_metros (`traffic_id`, `metro_id`) values ({$val['traffic_id']}, {$val['metro_id']})", false, false, 'gsystem_traffic');
            $this->parse_sql("delete from traffic_metros_tmp where $tmp_where", false, false, 'gsystem_traffic'); // 删除临时数据

            $traffic_detail = $this->getPositionTraffic($val['position_id']);

            $push_response = $this->apis->update_address_by_pid($traffic_detail, $val['position_id'], 1);
            $result[] = [
                $val,
                'push_response' => $push_response
            ];
        }
        return $result;
    }

    /*
     * 根据地址获取区域ID及父ID
     * @param string $address 地址
     * return array
     */
    public function getRegionIdByAddress($address){
        if(empty($address)) return [];

        // 根据地址获取地址字典信息数据  如果不为空则获取对应区域数据整合返回，否则查询LBS并插入地址字典
        $address_dictionary = $this->getAddressDictionaryByaddress($address);
        if(!empty($address_dictionary)){
            return $this->getRegionIds($address_dictionary['region_id']);
        }

        // 查询LBS数据 并插入地址字典
        // 获取地址LBS数据
        $address_list = $this->get_traffic_info_by_address($address, false);
        if(empty($address_list)) return [];

        // 获取区域数据
        $region = $this->get_region_id($address_list, true);
        if(empty($region)) return [];

        $this->addAddressDictionary($address, $region['id']);
        return [ $region['parent_id'], $region['id']];
    }

    /*
     * 根据地址获取地址字典信息数据
     * @param string $address 地址
     * return []
     */
    public function getAddressDictionaryByaddress($address){
        if(empty($address)) return [];

        $sql = "select * from address_dictionary where name = '{$address}'";

        $address_dictionary = $this->parse_sql($sql, true, false, self::gsystem_db);
        return !empty($address_dictionary) ? $address_dictionary[0]: [];
    }

    /*
     * 根据ID返回对应ID及父类ID
     */
    public function getRegionIds($id){
        if(empty($id)) return [];

        $cache_key = 'TRAFFIC_' . md5('getRegionIds' . $id);
        if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
            $sql = "select id,parent_id from regions_area where id = {$id}";

            $region = $this->parse_sql($sql);
            $result = !empty($region)? [$region[0]['id'], $region[0]['parent_id']]: [];
            $this->cache->memcached->save($cache_key, json_encode($result), 86400);
        }
        return $result;
    }

    /*
     * 插入地址字典
     * @param string $address　地址
     * @param int $region_id 区域ID
     * return boolean
     */
    public function addAddressDictionary($address, $region_id){
        if(empty($address) || empty($region_id)) return false;

        $sql = "select * from address_dictionary where name = '{$address}'";

        $address_dictionary = $this->parse_sql($sql, true, false, self::gsystem_db);

        if(empty($address_dictionary)){
            $this->parse_sql("insert into address_dictionary (`name`, `region_id`) values ('{$address}', {$region_id})", false, false, 'gsystem_traffic');
        }
        return true;
    }

}