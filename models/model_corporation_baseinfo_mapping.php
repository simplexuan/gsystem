<?php
/**
 * Created by PhpStorm.
 * User: sunjiqing
 * Date: 2017/3/29
 * Time: 10:32
 * Desc: 公司基础信息映射模型
 */
class Model_corporation_baseinfo_mapping extends Gsystem_model {
	protected $_model    = '';

	//基础信息类型
	const TYPE_FINANCING 	= 1;
	const TYPE_SCALE 		= 2;
	const TYPE_NATURE 		= 3;

	// 性质白名单映射列表
	private $white_nature_list = [
		'私营' 				=> '民营',
		'个人企业' 			=> '民营',
		'股份制' 			=> '股份合作制企业',
		'股份制企业' 			=> '股份合作制企业',
		'私营·股份制企业' 	=> '民营',
		'民营公司' 			=> '民营',
		'国有' 				=> '国企',
		'外资·合资' 			=> '合资',
		'私营·民营企业' 		=> '民营',
		'中外合资/合作' 		=> '合资',
		'外商独资/办事处' 	=> '外企代表处',
		'事业单位' 			=> '事业单位',
		'合资' 				=> '事业单位',
		'国有企业' 			=> '国企',
		'外商独资' 			=> '外资',
		'非营利·事业单位' 	=> '事业单位',
		'中外合营(合资·合作)' => '合资',
		'国家机关' 			=> '政府机关',
		'民营公司' 			=> '民营',
		'民营企业' 			=> '民营',
		'外商独资·外企办事处' 	=> '外企代表处',
		'外资欧美' 			=> '外资（欧美）',
		'外资非欧美' 			=> '外资（非欧美）',
		'非营利机构' 			=> '非盈利性机构',
	];

	// 融资白名单映射列表
	private $white_financing_list = [
		'Nofinancing' 	=> '不需要融资',
		'Around' 		=> '未融资',
		'A-' 			=> 'A轮',
		'A-轮' 			=> 'A轮',
		'A轮-' 			=> 'A轮',
		'A+' 			=> 'A轮',
		'A+轮' 			=> 'A轮',
		'A轮+' 			=> 'A轮',
		'B-' 			=> 'B轮',
		'B-轮' 			=> 'B轮',
		'B轮-' 			=> 'B轮',
		'B+' 			=> 'B轮',
		'B+轮' 			=> 'B轮',
		'B轮+' 			=> 'B轮',
		'C-' 			=> 'C轮',
		'C-轮' 			=> 'C轮',
		'C轮-' 			=> 'C轮',
		'C+' 			=> 'C轮',
		'C+轮' 			=> 'C轮',
		'C轮+' 			=> 'C轮',
		'D-' 			=> 'D轮及以上',
		'D-轮' 			=> 'D轮及以上',
		'D轮-' 			=> 'D轮及以上',
		'D+' 			=> 'D轮及以上',
		'D+轮' 			=> 'D轮及以上',
		'D轮+' 			=> 'D轮及以上',
		'D轮' 			=> 'D轮及以上',
		'IPO上市' 		=> '上市公司',
	];

	// 规模正则
	private $scale_regular =  [
		"/.*(保密).*/" => '保密',
		"/.*(人以下|少于|10\-15|15\-30|1\-49|1\-50|15\-50|30\-50).*/" => '少于50人',
		"/.*(50\-99|20\-99|50\-100|50\-150|100\-150).*/" => '50-150人',
		"/.*(100\-499|150\-300|150\-500|150\-499|300\-500).*/" => '150-500人',
		"/.*(500\-1000|500\-999).*/" => '500-1000人',
		"/.*(1000\-9999|1000\-5000|1000\-2000|2000\-5000|1000\-4999|500\-2000|1000人以上|2000人以上).*/" => '1000-5000人',
		"/.*(5000\-10000|5000\-9999|10000|10000人以上).*/" => '5000人以上'
	];

	private $black_scale_list = [
		'暂无'
	];
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

	/*
	 * 操作基础信息映射关系
	 */
	public function operate_baseinfos_mapping($param){
		foreach($this->scale_regular as $key => $val){
			if(preg_match($key, $param['scale'], $matches)){
				break;
			}
		}

		$corporation_id = $param['corporation_id'];
		$content = '';
		// 处理融资
		if(!empty($param['financing'])){
			$financing = str_replace(' ','',$param['financing']);
			$financing = explode('|', $financing);
			$content .= '处理融资成功；';
			$this->operate_financing($corporation_id, $financing);
		}

		// 处理规模
		if(!empty($param['scale'])){
			$content .= '处理规模成功；';
			$scale = str_replace(' ','',$param['scale']);
			$this->operate_scale($corporation_id, $scale);
		}

		// 处理性质
		if(!empty($param['nature'])){
			$content .= '处理性质成功；';
			$nature = str_replace(' ','',$param['nature']);
			$this->operate_nature($corporation_id, $nature);
		}
		return $content;
	}

	/*
	 * 根据类型获取公司基础信息字典
	 * @param int $type_id　基础信息类型：1 融资，2 规模，3 性质
	 * return array
	 */
	public function get_corporations_baseinfos_dictionary($type_id = 0){
		if(empty($type_id)) return [];

		$cache_key = 'GSYSTEM_GET_BASEINGOS_DICTIONARY' . md5($type_id);
		if(empty($result = $this->cache->memcached->get($cache_key))){
			$sql = "SELECT id,name FROM corporations_baseinfos_dictionary WHERE type_id = $type_id";
			$list = $this->parse_sql($sql);

			$result = [];
			foreach($list as $val){
				$result[$val['name']] = $val['id'];
			}
			$this->cache->memcached->save($cache_key, $result, 86400*7); // 缓存7天
		}

		return $result;
	}

	/*
	 * 操作融资
	 * @param int $corporation_id 公司ID
	 * @param array $data
	 * return id
	 */
	public function operate_financing($corporation_id, $data){
		// 获取当前映射关系
		$now_mapping = $this->get_baseinfos_mapping($corporation_id, self::TYPE_FINANCING);

		$now_baseinfos_dictionary_id = !empty($now_mapping) ?$now_mapping['baseinfos_dictionary_id']: 0;

		// 获取公司基础信息字典
		$dictionary = $this->get_corporations_baseinfos_dictionary(self::TYPE_FINANCING);

		// 获取数据对应字典，如果没有则直接查询字典，如果还是没有，则加入临时表，待后续处理
		$baseinfos_dictionary_id = 0;
		foreach($data as $val){
			$content = $val;
			// 判断是否在白名单映射列表中
			if(array_key_exists($val, $this->white_financing_list)){
				$content = $this->white_financing_list[$val];
			}

			// 判断是否在字典表中
			if(array_key_exists($content, $dictionary)){
				// 比较优先级，如果大于当前映射优先级，或大于已重置优先级，则保留 后续新增
				if($dictionary[$content] > $baseinfos_dictionary_id){
					$baseinfos_dictionary_id = $dictionary[$content];
				}
			}else{
				$this->add_tmp($corporation_id, self::TYPE_FINANCING, $content);
			}
		}

		// 如果预设优先级大于当前映射关系优先级，则新增或修改
		if($baseinfos_dictionary_id > $now_baseinfos_dictionary_id){
			$this->operate_mapping($corporation_id, $baseinfos_dictionary_id, self::TYPE_FINANCING);
		}
		return true;
	}

	/*
	 * 获取公司基础信息映射关系
	 * @param int $type_id　基础信息类型：1 融资，2 规模，3 性质
	 * return array
	 */
	public function get_baseinfos_mapping($corporation_id, $type_id =0){
		$sql = "SELECT * FROM corporations_baseinfos_mapping WHERE corporation_id = $corporation_id and type_id = $type_id";
		$result = $this->parse_sql($sql);
		return !empty($result) ?$result[0]: [];
	}

	/*
	 * 添加至临时数据
	 * @param int $corporation_id 公司ID
	 * @param int $type_id 类型ID
	 * @param string $data 数据
	 * return boolean;
	 */
	public function add_tmp($corporation_id, $type_id, $data){
		$sql = "select count(1) as 'total' from corporations_baseinfos_tmp where `corporation_id` = $corporation_id and `type_id` = $type_id and `content` = '$data'";
		$result = $this->parse_sql($sql);
		if($result[0]['total'] > 0){
			L('公司基础信息临时表已经存在该数据... param:' . json_encode([$corporation_id,$type_id,$data], JSON_UNESCAPED_UNICODE),4);
			return true;
		}

		$values  = "(" . $corporation_id . ",";
		$values .= $type_id . ",'";
		$values .= $data . "')";

		$insert_id = $this->parse_sql("insert into corporations_baseinfos_tmp(`corporation_id`,`type_id`,`content`) VALUES $values", false, true);
		return $insert_id;
	}

	/*
	 * 操作mapping表
	 * @param int $corporation_id 公司ID
	 * #param int $baseinfos_dictionary_id 字典ID
	 * @param int $type_id 类型ID
	 * return id
	 */
	public function operate_mapping($corporation_id, $baseinfos_dictionary_id, $type_id){
		$result = $this->parse_sql("select * from corporations_baseinfos_mapping where `corporation_id` = $corporation_id and `type_id` = $type_id");

		if(empty($result)){
			$values  = "(" . $corporation_id . ",";
			$values .= $baseinfos_dictionary_id . ",";
			$values .= $type_id . ")";
			$id = $this->parse_sql("insert into corporations_baseinfos_mapping(`corporation_id`,`baseinfos_dictionary_id`,`type_id`) VALUES $values", false, true);
		}else{
			$id = $result[0]['id'];
			$time = date('Y-m-d H:i:s',time());
			$sql = "update corporations_baseinfos_mapping set `baseinfos_dictionary_id`=$baseinfos_dictionary_id,`updated_at`='{$time}' where `id` = $id";
			$update_sql = "update corporations_baseinfos_mapping set `baseinfos_dictionary_id`=$baseinfos_dictionary_id,`updated_at`='{$time}' where `id` = $id";
			$this->parse_sql($update_sql,false);
		}
		return $id;
	}


	/*
	 * 操作性质
	 * @param int $corporation_id 公司ID
	 * @param array $data
	 * return id
	 */
	public function operate_nature($corporation_id, $data){
		// 获取当前映射关系
		$now_mapping = $this->get_baseinfos_mapping($corporation_id, self::TYPE_NATURE);

		$now_baseinfos_dictionary_id = !empty($now_mapping) ?$now_mapping['baseinfos_dictionary_id']: 0;

		// 获取公司基础信息字典
		$dictionary = $this->get_corporations_baseinfos_dictionary(self::TYPE_NATURE);

		// 获取数据对应字典，如果没有则直接查询字典，如果还是没有，则加入临时表，待后续处理
		// 判断是否在白名单映射列表中
		if(array_key_exists($data, $this->white_nature_list)){
			$data = $this->white_nature_list[$data];
		}

		// 判断是否在字典表中
		if(array_key_exists($data, $dictionary)){
			// 如果值不同则新增
			if($dictionary[$data] > 0 && $dictionary[$data] != $now_baseinfos_dictionary_id){
				$this->operate_mapping($corporation_id, $dictionary[$data], self::TYPE_NATURE);
			}
		}else{
			$this->add_tmp($corporation_id, self::TYPE_NATURE, $data);
		}
		return true;
	}

	/*
	 * 操作规模
	 * @param int $corporation_id 公司ID
	 * @param array $data
	 * return id
	 */
	public function operate_scale($corporation_id, $data){
		// 获取当前映射关系
		$now_mapping = $this->get_baseinfos_mapping($corporation_id, self::TYPE_SCALE);

		$now_baseinfos_dictionary_id = !empty($now_mapping) ?$now_mapping['baseinfos_dictionary_id']: 0;

		// 获取公司基础信息字典
		$dictionary = $this->get_corporations_baseinfos_dictionary(self::TYPE_SCALE);

		// 如果在黑名单中 则直接返回
		if(in_array($data, $this->black_scale_list)){
			L('operate_scale 黑名单... param:' . json_encode([$corporation_id,$data], JSON_UNESCAPED_UNICODE),4);
			return true;
		}

		// 获取数据对应字典，如果没有则直接查询字典，如果还是没有，则加入临时表，待后续处理
		// 判断是否在白名单映射列表中
		foreach($this->scale_regular as $key => $val){
			if(preg_match($key, $data, $matches)){
				$data = $val;
				break;
			}
		}

		// 判断是否在字典表中
		if(array_key_exists($data, $dictionary)){
			// 如果值不同则新增
			if($dictionary[$data] > 0 && $dictionary[$data] > $now_baseinfos_dictionary_id){
				$this->operate_mapping($corporation_id, $dictionary[$data], self::TYPE_SCALE);
			}
		}else{
			$this->add_tmp($corporation_id, self::TYPE_SCALE, $data);
		}
		return true;
	}


	/*
	 * 根据公司ID 类型获取公司基础信息
	 * @param int $corporation_id　公司ID
	 * @param int $type_id　基础信息类型：1 融资，2 规模，3 性质，为空或0查所有
	 * return array
	 */
	public function get_baseinfos($corporation_id, $type_id = 0){
		$sql = "SELECT m.id,m.corporation_id,m.baseinfos_dictionary_id,m.type_id,d.name from corporations_baseinfos_mapping m inner join corporations_baseinfos_dictionary d on d.id = m.baseinfos_dictionary_id where m.corporation_id = $corporation_id";
		if(!empty($type_id)){
			$sql .= " and m.type_id = $type_id";
		}

		$cache_key = 'GSYSTEM_GET_BASEINGOS_MAPPING_LIST' . md5($corporation_id . '-' . $type_id);
		if(empty($result = $this->cache->memcached->get($cache_key))){
			$result = $this->parse_sql($sql);
			$this->cache->memcached->save($cache_key, $result, 3600); // 缓存1小时
		}

		return $result;
	}
}

