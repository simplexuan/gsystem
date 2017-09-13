<?php 
class Logic_corporation extends Gsystem_logic {
	/**
	 * 操作类型，是插入还是更新
	 */
	private $op = 'INSERT';
	/**
	 * 公司类型
	 */
	private $type = array('bdlist'=>1,'customer'=>2,'merchant'=>3,1=>1,2=>2,3=>3,);
	/**
	 * 公司数据来源列表
	 */
	private $type_list = array(1=>'bdlist',2=>'customer',4=>'51job',5=>'zhilian',7=>'liepin');
	/**
	 * 某个类型需要获取的数据
	 */
	private $type_data = array(
		4 => array('description', 'industry', 'address'),
		5 => array('description', 'industry', 'address', 'alias'),
		7 => array('description', 'industry'),
	);
	/**
	 * 某个类型需要获取的数据,针对兼容ToB,左侧是旧的在form里的名字，右侧是新的model名
	 */
	private $type_data_2b = array(
		'bdlist' => array(
			'field_corporation_alias'	=> 'corporation_alias',
			'field_corporation_industry'=> 'corporation_industry',
			'field_corporation_baseinfo'=> 'corporation_baseinfo',
			'field_corporation_contacts'=> 'corporation_contact',
			'field_corporation_address'	=> 'corporation_address',
			'field_corporation_description'=> 'corporation_description',
			'field_corporation_product'	=> 'corporation_product',
			'field_corporation_title'	=> 'corporation_title',
			'field_bdlist_extra'		=> 'bdlist_extra',
			'field_bdlist_recruitment'	=> 'bdlist_rc',
			'field_bdlist_alarm'		=> 'bdlist_alarm', //没有这功能了，但BD需要，在BDList里的代码好像有问题
			'field_bdlist_linked'		=> 'bdlist_link', //没有这功能了，但BD需要
		),
		'customer' => array(
			'field_corporation_alias'	=> 'corporation_alias',
			'field_corporation_industry'=> 'corporation_industry',
			'field_corporation_baseinfo'=> 'corporation_baseinfo',
			'field_corporation_contacts'=> 'corporation_contact',
			'field_corporation_address'	=> 'corporation_address',
			'field_corporation_description'=> 'corporation_description',
			'field_corporation_product'	=> 'corporation_product',
			'field_corporation_title'	=> 'corporation_title',
			'field_customer_employinfo'	=> 'customer_employinfo',
			'field_customer_extra'		=> 'customer_extra',
			'field_customer_functions'	=> 'customer_function',
			'field_customer_salarystructure'=> 'customer_salarystructure',
			'field_customer_notes'		=> 'customer_note',
			'field_customer_bdinfo'		=> 'customer_bdinfo',
			'field_customer_team'		=> 'customer_team', //没有这功能了，BD也不需要
		),
		'merchant' => array(
			'field_corporation_industry'=> 'corporation_industry',
			'field_corporation_contacts'=> 'corporation_contact',
			'field_corporation_address'	=> 'corporation_address',
			'field_merchant_logo'		=> 'merchant_logo',
			'field_merchant_permit'		=> 'merchant_permit',
		),
	);
	/**
	 * 新旧表字段对应,key是新表字段名，field是form提交的字段名
	 */
	private $field_maps = array(
		'corporation'	=> array(
			'id'	=> array('field'=>'id','default'=>0),
			'parent_id'	=> array('field'=>'parent','default'=>0),
			'name'	=> array('field'=>'name','default'=>''),
			'website'	=> array('field'=>'website','default'=>''),
			'city_id'	=> array('field'=>'city','default'=>0),
			'city_name'	=> array('field'=>'cityname','default'=>''),
			'nature_id'	=> array('field'=>'nature_id','default'=>0),
			'nature_name'	=> array('field'=>'nature','default'=>''),
			'size_id'	=> array('field'=>'size','default'=>0),
			'size_name'	=> array('field'=>'sizevalue','default'=>''),
			'uid'	=> array('field'=>'uid','default'=>0),
			'status'	=> array('field'=>'status','default'=>0),
			'point_to'	=> array('field'=>'point_to','default'=>0)
		),
		'corporation_baseinfo'	=> array( //每个公司每个类型下只有一条记录
			'people'	=> array('field'=>'people','default'=>''),
			'profile'	=> array('field'=>'profile','default'=>''),
			'model'	=> array('field'=>'model','default'=>''),
			'founding'	=> array('field'=>'founding','default'=>''),
			'employee'	=> array('field'=>'employee','default'=>''),
			'branch'	=> array('field'=>'branch','default'=>0),
			'phone'	=> array('field'=>'phone','default'=>''),
			'email'	=> array('field'=>'email','default'=>''),
			'logo'	=> array('field'=>'logo','default'=>''),
		),
		'corporation_description'	=> array( //每个公司每个类型下只有一条记录
			'intro'	=> array('field'=>'intro','default'=>''),
			'notes'	=> array('field'=>'notes','default'=>''),
			'description'	=> array('field'=>'desc','default'=>''),
		),
		'corporation_alias'	=> array(
			'alias'	=> array('field'=>'value','default'=>''),
		),
		'corporation_contact'	=> array(
			'name'	=> array('field'=>'name','default'=>''),
			'position'	=> array('field'=>'position','default'=>''),
			'mobile'	=> array('field'=>'mobile','default'=>''),
			'phone'	=> array('field'=>'phone','default'=>''),
			'email'	=> array('field'=>'email','default'=>''),
			'qq'	=> array('field'=>'qq','default'=>''),
			'msn'	=> array('field'=>'msn','default'=>''),
			'blog'	=> array('field'=>'blog','default'=>''),
			'range'	=> array('field'=>'range','default'=>''),
			'gender'	=> array('field'=>'gender','default'=>''),
		),
		'corporation_address'	=> array(
			'address'	=> array('field'=>'value','default'=>''),
		),
		'corporation_industry'	=> array(
			'industry_id'	=> array('field'=>'tid','default'=>0),
			'industry_name'	=> array('field'=>'name','default'=>''),
		),
		'corporation_product'	=> array(
			'product'	=> array('field'=>'value','default'=>''),
		),
		'corporation_title'	=> array(
			'title'	=> array('field'=>'title','default'=>''),
			'description'	=> array('field'=>'description','default'=>''),
			'minsalary'	=> array('field'=>'minsalary','default'=>''),
			'maxsalary'	=> array('field'=>'maxsalary','default'=>''),
			'minyear'	=> array('field'=>'minyear','default'=>''),
			'maxyear'	=> array('field'=>'maxyear','default'=>''),
			'inner'	=> array('field'=>'inner','default'=>0),
		),
		'bdlist_rc'	=> array(
			'rc_id'	=> array('field'=>'tid','default'=>0),
			'rc_name'	=> array('field'=>'name','default'=>''),
		),
		'bdlist_alarm'	=> array(
			'alarm_time'	=> array('field'=>'time','default'=>0),
			'alarm'	=> array('field'=>'value','default'=>''),
		),
		'bdlist_link'	=> array(
			'link_time'	=> array('field'=>'time','default'=>0),
			'username'	=> array('field'=>'user','default'=>''),
			'remark'	=> array('field'=>'value','default'=>''),
		),
		'bdlist_extra'	=> array(
			'maturity_id'	=> array('field'=>'maturity','default'=>0),
			'importance_id'	=> array('field'=>'importance','default'=>0),
			'recruitment_plan'	=> array('field'=>'plan','default'=>''),
			'recruitment_budget'	=> array('field'=>'budget','default'=>''),
			'feature'	=> array('field'=>'feature','default'=>''),
			'capital'	=> array('field'=>'capital','default'=>0.0),
			'revenue'	=> array('field'=>'revenue','default'=>0.0),
			'profit'	=> array('field'=>'profit','default'=>0.0),
			'marketvalue'	=> array('field'=>'marketvalue','default'=>0.0),
			'toponline'	=> array('field'=>'toponline','default'=>''),
			'month'	=> array('field'=>'month','default'=>0),
			'welfare'	=> array('field'=>'salary','default'=>''),
			'currency'	=> array('field'=>'currency','default'=>''),
		),
		'customer_bdinfo'	=> array(
			'uid'	=> array('field'=>'uid','default'=>0),
			'user'	=> array('field'=>'user','default'=>''),
		),
		'customer_function'	=> array(
			'hierarchy'	=> array('field'=>'hierarchy','default'=>''),
			'functions'	=> array('field'=>'functions','default'=>''),
		),
		'customer_employinfo'	=> array(
			'culture'	=> array('field'=>'culture','default'=>''),
			'preference'	=> array('field'=>'preference','default'=>''),
			'attention'	=> array('field'=>'attention','default'=>''),
			'reimburse'	=> array('field'=>'reimburse','default'=>0),
			'reimbursement'	=> array('field'=>'reimbursement','default'=>''),
		),
		'customer_extra'	=> array(
			'business'	=> array('field'=>'business','default'=>''),
			'structure'	=> array('field'=>'structure','default'=>''),
			'development'	=> array('field'=>'development','default'=>''),
			'management'	=> array('field'=>'management','default'=>''),
			'position'	=> array('field'=>'position','default'=>''),
			'competitor'	=> array('field'=>'competitor','default'=>''),
			'advantage'	=> array('field'=>'advantage','default'=>''),
			'disadvantage'	=> array('field'=>'disadvantage','default'=>''),
		),
		'customer_salarystructure'	=> array(
			'minmonth'	=> array('field'=>'minmonth','default'=>0),
			'maxmonth'	=> array('field'=>'maxmonth','default'=>0),
			'stock'	=> array('field'=>'stock','default'=>''),
			'allowance'	=> array('field'=>'allowance','default'=>''),
			'cycle'	=> array('field'=>'cycle','default'=>''),
			'vacation'	=> array('field'=>'vacation','default'=>''),
			'welfare'	=> array('field'=>'welfare','default'=>''),
			'level'	=> array('field'=>'level','default'=>''),
			'commission'	=> array('field'=>'commission','default'=>''),
			'insurance'	=> array('field'=>'insurance','default'=>''),
			'bonus'	=> array('field'=>'bonus','default'=>''),
			'range'	=> array('field'=>'range','default'=>''),
			'other'	=> array('field'=>'other','default'=>''),
			'overtime'	=> array('field'=>'overtime','default'=>''),
			'routine'	=> array('field'=>'routine','default'=>''),
		),
		'customer_note'	=> array(
			'functions'	=> array('field'=>'functions','default'=>''),
			'title'	=> array('field'=>'title','default'=>''),
		),
		'customer_team'	=> array(
			'team_id'	=> array('field'=>'id','default'=>0),
		),
		'merchant_logo'	=> array(
			'logo'	=> array('field'=>'value','default'=>''),
		),
		'merchant_permit'	=> array(
			'permit'	=> array('field'=>'value','default'=>''),
		),
	);
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
		return call_user_func_array(array($this->model('Model_corporation'), $func), $args);
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
		if(empty($id)){
			throw new Exception('id不能为空', $this->config->item('data_exist_err_no', 'err_no'));
		}

		$selected  = isset($param['selected']) ? parse_selected($param['selected']) : array();
		if (!$selected){
			$selected  = $selected + array('corporation'=>array())
				;
		}

		$c['corporation'] = $this->model('Model_corporation')->fetch_one_by_id($id, $selected['corporation']);
		if (empty($c['corporation']) ){
			throw new Exception(sprintf('%s: The id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
		} 


		return $c; 
	}
	function search($param){
		//$param['_ft_']     = 'edps';
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
		$corporations = $this->model('Model_corporation')->search($param, $param['page'], $param['pagesize']);
		$corporations['results'] = array_values($corporations['results']);
		return $corporations;
	}
    /**
     * search优化版，支持like搜索
     * 用于新公司清洗平台的支持公司名称模糊搜索
     *
     * @return array
     */
    function search_optimize($param) {
        if (isset($param['page']) && is_numeric($param['page']) && 1 < $param['page']) {
            $param['page'] = (int) $param['page'];
        } else {
            $param['page'] = 1;
        }
        if (isset($param['pagesize']) && is_numeric($param['pagesize']) && 0 < $param['pagesize'] && 1000 > $param['pagesize']) {
            $param['pagesize'] = (int) $param['pagesize'];
        } else {
            $param['pagesize'] = 1000;
        }
        $corporations = $this->model('Model_corporation')->search_optimize($param);
        return $corporations;
    }
	/**
	 * 新企业注册
	 * 公司已存在，就更改状态为0，关联数据增加
	 * 公司未存在，直接增加
	 * param=array(
	 * 		name=>'',	//公司名称
	 * 		alias,		//公司别名
	 * 		industry,	//
	 * 		type
	 * 		
	 * )
	 */
	public function save($param) {
		$status = (true === $param['is_reviewed']) ? 1 : 2;


		$type = 3;

		$op = 'INSERT';

		//验证公司名称
		if(empty($param['name'])) throw new Exception('公司名字未填写！', 100002);
		

		//查询是否存在
		$corp_rs = $this->model('Model_corporation')->search_one(array('name' => $param['name']));
		
		//处理公司名称
		$param['name'] = $this->_format_corporation_name($param['name']);
		$temp_name = str_replace(' ', '', $param['name']);
		$temp_name = strtolower($temp_name);



		//查询公司格式化后的名字是否存在
		$another_corp_rs = $this->model('Model_corporation')->search_one(array('name' => $param['name'])); 
		if (!empty($another_corp_rs)) $corp_rs = $another_corp_rs;
		
		//查询 公司名称倒排表
		$inverted_rs = $this->model('Model_corporation_name_inverted')->search_one(array('name' => $temp_name));

		if(!empty($corp_rs) || !empty($inverted_rs)) {
			if (empty($corp_rs)) {
				$corp_rs = $this->model('Model_corporation')->fetch_one_by_id($inverted_rs['id']);
			}
			$param['id'] = $corp_rs['id'];
			$op = 'UPDATE';
			
			// 判断别名是否相同
			$is_same_alias = true;
			$input_aliases = array_column($param['alias'], 'alias');
			$alias_rs = $this->model('Model_corporation_alias')->search_all(array('corporation_id' => $inverted_rs['id']));
			$aliases = array_column($alias_rs, 'alias');
			if (! empty($input_aliases) && empty($aliases)) { // 别名不同
				$is_same_alias = false;
			} else {
				$alias_diff = array_diff($input_aliases, $aliases);
				if (! empty($alias_diff)) {
					$is_same_alias = false;
				}
			}
			
			// 判断行业是否相同
			$is_same_industry = true;
			if ($is_same_alias) {
				$input_industry_ids = array_column($param['industry'], 'industry_id');
				$industry_rs = $this->model('Model_corporation_industry')->search_all(array('corporation_id' => $inverted_rs['id']));
				$industry_ids = array_column($industry_rs, 'industry_id');
				if (! empty($input_industry_ids) && empty($industry_ids)) {
					$is_same_industry = false;
				} else {
					$industry_diff = array_diff($input_industry_ids, $industry_ids);
					if (! empty($industry_diff)) {
						$is_same_industry = false;
					}
				}
			}
			// 如果别名或行业不相同
			if (! $is_same_alias || ! $is_same_industry) {
				$param['status'] = 2;
			}
            //如果status ==0 重置为2
            if($corp_rs['status'] == 0){
                $param['status'] = 2;
            }
		} else {
			/* @XXX 去掉算法识别，由法哥人工识别
			//通过姚工识别公司id
			$this->log->info(sprintf('corporation_name:%s', $param['name']));
			$work_list_one = array('company_name'=>$param['name'],'position'=>'','work_id'=>'','desc'=>'','industry_name'=>'');
			$work_rs = $this->work_foreground(array('worker'=>'jd_trade', 'cv_id'=>'gsystem_9999', 'work_list'=>array($work_list_one)));
			$this->log->info(sprintf('corporation_name:%s corporation_id:%s', $param['name'], var_export($work_rs, true)));
			$work_rs = $work_rs['response']['result'];
			if(!empty($work_rs[0]['company_id'])) {
				$param['id'] = $work_rs[0]['company_id'];
				$op = 'UPDATE';
				unset($param['name']);
			}
			*/
            $company_id = $this->apis->corp_tag($param['name']);
            if($company_id > 0){
                $param['id'] = $company_id;
                $op = 'UPDATE';
                unset($param['name']);
            }
			$param['status'] = 2;
		}

		$tables = array(
			'address', 'alias', 'industry', 'product', 'logo', 'permit',
		);
		if (1 === $status) $param['status'] = 1;
		$corporation_id = $this->model('Model_corporation')->save(array('corporation' => $this->format_save_param($param, 'corporation', $op)));
		if(empty($param['id'])) {
			$this->model('Model_corporation')->update_by_id(array('point_to'=>$corporation_id), $corporation_id);
		}
		if (empty($inverted_rs)) {
			$this->_add_to_inverted($corporation_id, $temp_name);
		}

		foreach($tables as $table) {
			if(in_array($table, array('logo', 'permit'))) {
				$table_name = 'merchant_' . $table;
			} else {
				$table_name = 'corporation_' . $table;
			}
			if(!empty($param[$table])) {
				$rs = $this->model('Model_' . $table_name)->search_all(array('corporation_id'=>$corporation_id));
				foreach($param[$table] as $val) {
					foreach($rs as $one) {
						if($table == 'industry') {
							if($one['industry_id'] == $val['industry_id']) {
								continue 2;
							}
						} elseif($table == 'logo' || $table == 'permit') {
							$this->model('Model_' . $table_name)->delete_one_by_id($one['id']);
						} else {
							if($one[$table] == $val[$table]) {
								continue 2;
							}
						}
					}
					if($table == 'industry') {
						try{
							$industry_info = $this->model('Model_industry')->fetch_one_by_id($val['industry_id']);
							$data['industry_id'] = $val['industry_id'];
							$data['industry_name'] = $industry_info['name'];
						} catch (Exception $e) {
							continue;
						}
					} else {
						$data[$table] = $val[$table];
					}

					$data['type_id'] = $type;
					$data['corporation_id'] = $corporation_id;
					$data['sort'] = 1;
					$data['is_deleted'] = 'N';
					$data['updated_at'] = date('Y-m-d H:i:s');

					$this->model('Model_' . $table_name)->save(array($table_name=>$data));
				}
			}
		}

		return $corporation_id;
	}


	 /**
     * 修改公司信息(For Frank)
     *
     * $param = array('uid'      => 1,                              // 用户ID
     *                'uname'    => 'kai.wang',                     // 用户名
     *                'id'       => 1,                              // 公司ID
     *                'alias'    => array('公司别名1', '公司别名2') // 公司别名数组
     *                'industry' => array(1, 13)                    // 公司行业ID数组
     *                'status'   => 0,                              // 公司状态，0表示不启用,1表示启用
     *                'ka_top'   => 0                               // 公司重要度，0表示普通，1表示KA，2表示TOP
     *                'industry_main'   => 8                        // 主营行业ID 为空不处理
     *                'address'   => '地址'                        // 公司地址
     *                ) 
     */
    public function update_by_id($param){

        if (empty($param['id']) || ! is_numeric($param['id']) || 0 >= $param['id'] ||
            ! isset($param['alias']) || ! is_array($param['alias']) ||
            ! isset($param['industry']) || ! is_array($param['industry']) ||
            ! isset($param['status']) || ! is_numeric($param['status']) || 0 > $param['status'] || 4 < $param['status'] ||
            ! isset($param['ka_top']) || ! is_numeric($param['ka_top']) || 0 > $param['ka_top'] || 2 < $param['ka_top']
            ) {
            throw new Exception('参数不符合规范', 100001);
        }

        if($param['industry_main']){
            if(!in_array($param['industry_main'],$param['industry'])) throw new Exception('主营行业必须在公司行业中', 100001);
        }

        $corporation_id = (int) $param['id'];
        $status = (int) $param['status'];
        
        try{
        	$this->model('Model_corporation')->fetch_one_by_id($corporation_id);
        }catch(Exception $e){
        	throw new Exception("公司不存在！",100002);
        }
        

        if (4 === $status && $this->_has_enabled_subsidiary($corporation_id)) {
            throw new Exception('存在启用的子公司，不能禁用该公司', 85042005);
        }

        try{
            $this->model('Model_corporation')->update_info($param);
        }catch (Exception $e){
            throw $e;
        }

        return $corporation_id;
    }




	private function _format_corporation_name($name) {
		$search = array('（', '）');
		$replace = array('(', ')');
		$formated_name = str_replace($search, $replace, $name);
		return $formated_name;
	}
	private function _add_to_inverted($corporation_id, $name) {
		$new_one = $this->model('Model_corporation_name_inverted')->new_one();
		$new_one['id'] = $corporation_id;
		$new_one['name'] = $name;
		$this->model('Model_corporation_name_inverted')->save(array('corporation_name_inverted' => $new_one));
	}
	/**
	 * 增加公司接收简历的email（ToC投递简历用）
	 * @param $param array array('company_name','hr_name','email')
	 */
	function add_company_email($param) {
		$this->load->helper('email');
		if(empty($param['company_name']) || empty($param['email']) || !valid_email($param['email'])) {
			throw new Exception('error parameters');
		}

		$data = array('name'=>trim($param['company_name']),'uid'=>1,'updated_at'=>date('Y-m-d H:i:s'));
		$id = $this->model('Model_corporation')->gen_id_by_unique($data);

		$data = array('corporation_id'=>$id, 'name'=>trim($param['hr_name']), 'email'=>trim($param['email']), 'updated_at'=>date('Y-m-d H:i:s'));
		$hr_id = $this->model('Model_corporation_hr')->gen_id_by_unique($data);

		return $hr_id;
	}
	/**
	 * ToC获取公司信息
	 */
	function get_corporation_for_2c($param) {
		$rs = array();
		if(empty($param['ids'])) {
			return $rs;
		}
		$ids = is_array($param['ids']) ? $param['ids'] : explode(',', $param['ids']);
		foreach($ids as $id) {
			if(!is_numeric($id)) {
				$rs[$id] = array();
			} else {
				$rs[$id] = $this->get_merchant($id);
				if(!empty($rs[$id])) {
					//查merchants_logos表
					$one = $this->model('Model_merchant_logo')->search_one(array('corporation_id'=>$id));
					if(empty($one['logo'])) {
						$logo_path = $this->get_logo($id);
					} else {
						$logo_path = sprintf('%s%s',$this->config->item('img_path'), $one['logo']);
					}

					$rs[$id]['logo'] = $logo_path;
				}
			}
		}

		return $rs;
	}
    /**
     * 目前公司关系树为3层，从叶子节点往上遍历查找LOGO
     * 若ID为第三层公司ID，会取同一个第二层父节点的兄弟节点LOGO，也会取到所有第二层的节点和第一层根节点的LOGO，没有则返回空字符串
     * 若ID为第二层公司ID，会取所有第二层的兄弟节点和第一层父节点的LOGO，没有则返回空字符串
     * 若ID为第一层公司ID，只取自己的LOGO，没有就返回空字符串
     */
    private function get_logo ($id) {
        try {
            $img_path = $this->config->item('img_path');

            $self_baseinfo = $this->model('Model_corporation_baseinfo')->search_one(array('corporation_id' => $id, 'type_id' => 1));
            if (! empty($self_baseinfo['logo'])) {
                return sprintf('%s%s', $img_path, $self_baseinfo['logo']);
            }

            $self_corporation = $this->model('Model_corporation')->fetch_one_by_id($id);
            $parent_id = $self_corporation['parent_id'];
            if (0 != $parent_id && $id != $parent_id) {
                $sibling_corporations = $this->model('Model_corporation')->search_all(array('parent_id' => $parent_id)); // 旨在取兄弟节点，有可能取到父节点，因为有部分公司数据的parent_id等于自己
                unset($sibling_corporations[$id]);
                foreach ($sibling_corporations as $sibling_corporation) {
                    $sibling_baseinfo = $this->model('Model_corporation_baseinfo')->search_one(array('corporation_id' => $sibling_corporation['id'], 'type_id' => 1));
                    if (! empty($sibling_baseinfo['logo'])) {
                        return sprintf('%s%s', $img_path, $sibling_baseinfo['logo']);
                    }
                }
                return $this->get_logo($parent_id);
            }
        } catch (Exception $e) {
        }
        return '';
    }
	/**
	 * 从ToB获取
	 */
	function get_merchant($id) {
		return $this->get_baike($id);
	}

	/**
	 * 从百科拿
	 */
	function get_baike($id) {
		$rs = $this->model('Model_corporation_baike')->search_one(array('corporation_id'=>$id));

		if(empty($rs) || $rs['status'] == 0) {
			return $this->get_7sources($id);
		} else {
			//$rs['from_src'] = 'baike';
			return $this->model('Model_corporation_baike')->format_data(array($rs));
		}
	}

	/**
	 * 7大网站,51job(4)>zhilian(5)>liepin(7)
	 */
	function get_7sources($id) {
		if(!is_numeric($id)) {
			return array();
		}

		$src_list = $this->model('Model_corporation_source')->search_all(array('corporation_id'=>$id));
		if(empty($src_list)) {
			return $this->get_bdlist($id);
		} else {
			$type_id = 9999;
			foreach($src_list as $src) {
				$type_id = min($src['type_id'], $type_id);
			}

			$rs = $this->model('Model_corporation')->fetch_one_by_id($id);

			foreach($this->type_data[$type_id] as $val) {
				$rs[$val]	= $this->model('Model_corporation_' . $val)->search_all(array('corporation_id'=>$id,'type_id'=>$type_id));
			}

			$rs['from_src'] = $this->type_list[$type_id];

			return $rs;
		}
	}
	/**
	 * 获取BDList
	 */
	function get_bdlist($id) {
		$rs = $this->get_corporation_by_type(array('ids'=>$id, 'type'=>1));
		if(empty($rs)) {
			return array();
		} else {
			return reset($rs);
		}
	}

	/**
	 * 获取公司相关信息,包括公司地址、扩展信息等,默认取BDList
	 */
	function get_corporation_by_type($param) {
		if(empty($param['ids'])) {
			return array();
		}
		$type_id = 1;
		if(!empty($param['type'])) {
			$type = strtolower($param['type']);
			if(!empty($this->type[$type])) {
				$type_id = $this->type[$type];
			}
		}
		$ids = explode(',', $param['ids']);
		$return = array();
		foreach($ids as $id) {
			if(!is_numeric($id)) {
				$return[$id] = array();
				continue;
			}
			try {
				if($type_id == 2) {
					$rs = $this->model('Model_customer')->fetch_one_by_id($id);
				} else {
					$rs = $this->model('Model_corporation')->fetch_one_by_id($id);
				}

				foreach(array('address', 'alias', 'baseinfo', 'contact', 'description', 'industry', 'product', 'title') as $val) {
					$rs[$val]	= $this->model('Model_corporation_' . $val)->search_all(array('corporation_id'=>$id, 'type_id'=>$type_id));
				}

				if($type_id == 1) {
					foreach(array(/*'alarm', */'extra',/* 'link',*/ 'rc') as $val) {
						$rs[$val]	= $this->model('Model_bdlist_' . $val)->search_all(array('corporation_id'=>$id));
					}
				} elseif ($type_id == 2) {
					foreach(array(/*'bdinfo', */'employinfo', 'extra', 'function', 'note', 'salarystructure'/*, 'team'*/) as $val) {
						$rs[$val]	= $this->model('Model_customer_' . $val)->search_all(array('corporation_id'=>$id));
					}
				}

				$rs['from_src'] = $this->type_list[$type_id];

				$return[$id] = $rs;
			} catch (Exception $e) {
				$this->log->warn($e->getMessage());
				$return[$id] = array();
			}
		}

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_Corporation_load
	 */
	function load($param) {
		if(empty($param['id']) || !is_numeric($param['id'])) {
			return false;
		}
		$bundle = empty($param['bundle']) ? 'bdlist' : strtolower($param['bundle']);
		if(empty($this->type[$bundle])) {
			$bundle = 'bdlist';
		}
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		try {
			if($bundle == 'customer') {
				$rs = $this->model('Model_customer')->fetch_one_by_id($param['id']);
			} else {
				$rs = $this->model('Model_corporation')->fetch_one_by_id($param['id']);
			}

			return (object)$this->format_data($rs, $bundle, $select_field);
		} catch (Exception $e) {
			//$this->log->warn($e->getMessage());
			return false;
		}
	}
	/**
	 * 实现ToB的ToBusiness_Corporation_loadMulti
	 */
	function loadMulti($param) {
		$return = array();
		if(empty($param['ids'])) {
			return $return;
		}
		$ids = is_array($param['ids']) ? $param['ids'] : explode(',', $param['ids']);
		$bundle = empty($param['bundle']) ? '' : $param['bundle'];
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		foreach($ids as $id) {
			$rs = $this->load(array('id'=>$id,'bundle'=>$bundle,'select_field'=>$select_field));
			if($rs) {
				$return[$id] = $rs;
			}
		}

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_Corporation_loadByName
	 */
	function loadByName($param) {
		if(empty($param['name'])) {
			return false;
		}
		$bundle = empty($param['bundle']) ? '' : $param['bundle'];
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		$rs = $this->model('Model_corporation')->search_one(array('name'=>$param['name']));
		if(empty($rs)) {
			return false;
		} else {
			return $this->load(array('id'=>$rs['id'], 'bundle'=>$bundle, 'select_field'=>$select_field));
		}
	}
	/**
	 * 实现ToB的ToBusiness_Corporation_loadByNames
	 */
	function loadByNames($param) {
		$return = array();
		if(empty($param['names'])) {
			return $return;
		}
		$names = is_array($param['names']) ? $param['names'] : explode(',', $param['names']);
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		$bundle = empty($param['bundle']) ? '' : $param['bundle'];
		foreach($names as $name) {
			$rs = $this->loadByName(array('name'=>$name, 'bundle'=>$bundle, 'select_field'=>$select_field));
			if($rs) {
				$return[$rs->id] = $rs;
			}
		}

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_Corporation_loadByAlias
	 */
	function loadByAlias($param) {
		$return = array();
		if(empty($param['alias'])) {
			return $return;
		}
		$bundle = empty($param['bundle']) ? '' : $param['bundle'];
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		$rs = $this->model('Model_corporation_alias')->search_all(array('alias'=>$param['alias']));
		$ids = array();
		foreach($rs as $one) {
			$ids[] = $one['corporation_id'];
		}
		
		return $this->loadMulti(array('bundle'=>$bundle, 'ids'=>$ids, 'select_field'=>$select_field));
	}
	/**
	 * 实现ToB的ToBusiness_Corporation_loadSubsidiary
	 */
	function loadSubsidiary($param) {
		$return = array();
		if(empty($param['id']) || !is_numeric($param['id'])) {
			return $return;
		}
		$bundle = empty($param['bundle']) ? '' : $param['bundle'];
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		$rs = $this->model('Model_corporation')->search_all(array('parent_id'=>$param['id']));
		$ids = array();
		foreach($rs as $one) {
			$ids[] = $one['id'];
		}
		
		return $this->loadMulti(array('bundle'=>$bundle, 'ids'=>$ids, 'select_field'=>$select_field));
	}
	/**
	 * 实现ToB的ToBusiness_Corporation_mergeByNames
	 */
	function mergeByNames($param) {
	}
	/**
	 * 实现ToB的ToBusiness_Corporation_getShortName
	 */
	function getShortName($param) {
		$ids = array();
		$is_one = true;
		$return = array();
		if(empty($param['id']) || !is_numeric($param['id'])) {
			if(!empty($param['ids'])) {
				$ids = is_array($param['ids']) ? $param['ids'] : explode(',', $param['ids']);
				$is_one = false;
			} else {
				return '';
			}
		} else {
			$ids = array($param['id']);
		}
		$length = empty($param['length']) ? 0 : abs(intval($param['length']));
		if(empty($length)) {
			$length = 9;
		}
		$rs_es = $this->model('Model_corporation')->get_multi($ids);
		foreach($ids as $id) {
			if(empty($rs_es[$id])) {
				$return[$id] = '';
			} else {
				$aliases = $this->model('Model_corporation_alias')->search_all(array('corporation_id'=>$id,'type_id'=>1));
				foreach($aliases as $alias) {
					if (preg_match("/^[\x{2e80}-\x{9fa5}\w\s]{1,".$length."}$/u", $alias['alias'])) {
						$return[$id] = $alias['alias'];
						continue 2;
					}
				}
				$return[$id] = $rs_es[$id]['name'];
			}
		}

		return $is_one ? reset($return) : $return;
	}
	/**
	 * 实现ToB的ToBusiness_Corporation_listCorporation
	 */
	function listCorporation($param) {
		$return = array();
		$bundle = empty($param['bundle']) ? 'bdlist' : strtolower($param['bundle']);
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		if(empty($this->type[$bundle])) {
			$bundle = 'bdlist';
		}
		$page = 1;
		if(isset($param['page']) && is_numeric($param['page'])) {
			$page = max($page, intval($param['page']));
		}
		$pagesize = 12;
		if(isset($param['size']) && is_numeric($param['size'])) {
			$pagesize = max(1, intval($param['size']));
		}

		$table = $bundle == 'customer' ? 'customers' : 'corporations';
		if(empty($this->db)) {
			$this->load->database('', FALSE, TRUE);
		}
		//查总数
		if(isset($param['status'])) {
			$cond['status'] = $param['status'];
			if(is_array($param['status'])) {
				$this->db->where_in('status', $param['status']);
			} else {
				$this->db->where('status', $param['status']);
			}
		}
		if(isset($param['uid']) && is_numeric($param['uid'])) {
			$this->db->where('uid', $param['uid']);
		}
		$this->db->from($table);
		$num = $this->db->count_all_results();
		
		//查记录
		if(isset($param['status'])) {
			$cond['status'] = $param['status'];
			if(is_array($param['status'])) {
				$this->db->where_in('status', $param['status']);
			} else {
				$this->db->where('status', $param['status']);
			}
		}
		if(isset($param['uid']) && is_numeric($param['uid'])) {
			$this->db->where('uid', $param['uid']);
		}
		$this->db->order_by('updated_at', 'DESC');
		$this->db->limit($pagesize, ($page-1)*$pagesize);
		$this->db->select('id');
		$rs = $this->db->get($table);
		foreach($rs->result_array() as $one) {
			//$return[$one['id']] = (object)$this->format_data($one, $bundle);
			$return[$one['id']] = $this->load(array('id'=>$one['id'], 'bundle'=>$bundle, 'select_field'=>$select_field));
		}
		$return['variables'] = array('page'=>$page, 'size'=>$pagesize, 'total'=>$num, 'pages'=>ceil($num/$pagesize));

		return $return;
	}
	/**
	 * 实现ToB的ToBusiness_Corporation_listCustomerByTeam
	 */
	function listCustomerByTeam($param) {
		$return = array();
		if(empty($param['teamid'])) {
			return $return;
		}
		$teamid = is_array($param['teamid']) ? $param['teamid'] : explode(',', $param['teamid']);
		$select_field = empty($param['select_field']) ? array() : $param['select_field'];
		$rs = $this->model('Model_customer_team')->search_all(array('team_id'=>$teamid));
		if(empty($rs)) {
			return $return;
		} else {
			$corporation_ids = array();
			foreach($rs as $one) {
				$corporation_ids[] = $one['corporation_id'];
			}
			$status = '';
			if(isset($param['status']) && is_numeric($param['status'])) {
				$status = $param['status'];
			}
			$page = 1;
			if(isset($param['page']) && is_numeric($param['page'])) {
				$page = max($page, intval($param['page']));
			}
			$pagesize = 12;
			if(isset($param['size']) && is_numeric($param['size'])) {
				$pagesize = max(1, intval($param['size']));
			}
			if($status == '') {
				$rs = $this->model('Model_customer')->get_by_id($corporation_ids, array('select'=>'*','ordersort'=>'updated_at DESC'));
			} else {
				$rs = $this->model('Model_customer')->get_by_id__status($corporation_ids, $status, array('select'=>'*','ordersort'=>'updated_at DESC'));
			}
			$count = count($rs);
			$rs = array_slice($rs, ($page - 1) * $pagesize, $pagesize);
			foreach($rs as $one) {
				$return[$one['id']] = (object)$this->format_data($one, 'customer', $select_field);
			}
			$return['variables'] = array('page'=>$page, 'size'=>$pagesize, 'total'=>$count, 'pages'=>ceil($count/$pagesize));

			return $return;
		}
	}
	//处理主表数据
	function format_data($data, $bundle, $select_field = array()) {
		if(empty($data)) {
			return $data;
		} else {
			$data['parent']		= $data['parent_id'];
			$data['city']		= $data['city_id'];
			$data['cityname']	= $data['city_name'];
			$data['nature']		= $data['nature_name'];
			$data['size']		= $data['size_id'];
			$data['sizevalue']	= $data['size_name'];
			$data['created']	= strtotime($data['created_at']);
			$data['updated']	= strtotime($data['updated_at']);
			unset($data['parent_id'], $data['city_id'], $data['city_name'], $data['nature_name'],
				$data['size_id'], $data['size_name'], $data['created_at'], $data['updated_at']);

			$select_field = is_array($select_field) ? $select_field : explode(',',$select_field);

			foreach($this->type_data_2b[$bundle] as $key=>$model) {
				if(!empty($select_field) && !in_array($key, $select_field)) continue;
				$this->log->info(sprintf(' %s:%d ok.', __FILE__, __LINE__));
				$data[$key]	= $this->get_all_by_search('Model_' . $model, array('corporation_id'=>$data['id'], 'type_id'=>$this->type[$bundle]));
			}

			if(!empty($select_field)) {
				foreach($data as $k=>$v) {
					if(!in_array($k, $select_field)) {
						unset($data[$k]);
					}
				}
			}

			return $data;
		}
	}
	//通过search获取附表符合条件的全部数据
	function get_all_by_search($model, $cond) {
		$return = array();
		$data = $this->model($model)->search_all($cond);
		foreach($data as $val) {
			$return[] = $this->format_sub_data($model, $val);
		}
	
		return $return;
	}
	//处理关联表的数据
	function format_sub_data($model, $data) {
		if(!empty($data)) {
			switch($model) {
				case 'Model_corporation_alias':
				case 'Model_corporation_address':
				case 'Model_corporation_product':
				case 'Model_merchant_logo':
				case 'Model_merchant_permit':
					list(,,$sub_model) = explode('_', $model);
					return array('value'=>$data[$sub_model]);
					break;
				case 'Model_corporation_industry':
				case 'Model_bdlist_rc':
					list(,,$sub_model) = explode('_', $model);
					return array('tid'=>$data[$sub_model . '_id'], 'name'=>$data[$sub_model . '_name']);
					break;
				case 'Model_corporation_baseinfo':
				case 'Model_corporation_contact':
				case 'Model_corporation_description':
				case 'Model_corporation_title':
				case 'Model_customer_employinfo':
				case 'Model_customer_function':
				case 'Model_customer_note':
				case 'Model_customer_extra':
				case 'Model_customer_salarystructure':
				case 'Model_customer_bdinfo':
					foreach(array('id', 'type_id', 'corporation_id', 'sort', 'is_deleted', 'created_at', 'updated_at') as $key) {
						if(isset($data[$key])) {
							unset($data[$key]);
						}
					}
					if(isset($data['description'])) {
						$data['desc'] = $data['description'];
					}
					return $data;
					break;
				case 'Model_bdlist_extra':
					$data['maturity']	= $data['maturity_id'];
					$data['importance']	= $data['importance_id'];
					$data['plan']		= $data['recruitment_plan'];
					$data['budget']		= $data['recruitment_budget'];
					$data['salary']		= $data['welfare'];
					foreach(array('id', 'type_id', 'corporation_id', 'sort', 'is_deleted', 'created_at', 'updated_at', 'maturity_id', 
							'importance_id', 'recruitment_plan', 'recruitment_budget', 'welfare') as $key) {
						if(isset($data[$key])) {
							unset($data[$key]);
						}
					}
					break;
				case 'Model_customer_team':
					return array('id'=>$data['team_id']);
					break;
				case 'Model_bdlist_link':
					return array('time'=>$data['link_time'],'user'=>$data['username'],'value'=>$data['remark']);
					break;
				case 'Model_bdlist_alarm':
					return array('time'=>$data['alarm_time'],'value'=>$data['alarm']);
					break;
				default:
					return $data;
					break;
			}
		}

		return $data;
	}
	/**
	 * 实现ToBusiness_Corporation_insert
	 */
	function insert($param) {
		$param = (array)$param;
		if(empty($param) || empty($param['name']) || trim($param['name']) == '') {
			throw new Exception('Your data is not validated', 85042001);
		}

		$this->log->warn(json_encode($param, JSON_UNESCAPED_UNICODE). '***' . json_encode($this->request_header[getmypid()], JSON_UNESCAPED_UNICODE));

		$param = $this->prepareItem($param);



		//查询是否存在
		$corp_rs = $this->model('Model_corporation')->search_one(array('name' => $param['name']));
		
		//处理公司名称
		$param['name'] = $this->_format_corporation_name($param['name']);
		$temp_name = str_replace(' ', '', $param['name']);
		$temp_name = strtolower($temp_name);



		//查询公司格式化后的名字是否存在
		$another_corp_rs = $this->model('Model_corporation')->search_one(array('name' => $param['name'])); 
		if (!empty($another_corp_rs)) $corp_rs = $another_corp_rs;
		
		//查询 公司名称倒排表
		$inverted_rs = $this->model('Model_corporation_name_inverted')->search_one(array('name' => $temp_name));

	

		// $param['name'] = $this->_format_corporation_name($param['name']);
		// $temp_name = str_replace(' ', '', $param['name']);
		// $temp_name = strtolower($temp_name);
		// $inverted_rs = $this->model('Model_corporation_name_inverted')->search_one(array('name' => $temp_name));
		if (empty($inverted_rs) && empty($corp_rs)) {
			if ($param['bundle'] != 'bdlist' && empty($param['status'])) {
				$param['status'] = 0;
			}
			$param['id'] = $this->_save($param, 'bdlist');
			$this->_add_to_inverted($param['id'], $temp_name);
		} else {
			if (empty($corp_rs)) {
				$corp_rs = $this->model('Model_corporation')->fetch_one_by_id($inverted_rs['id']);
			}
			$param['id'] = $corp_rs['id'];
			// $param['id'] = empty($corp_rs) ? $inverted_rs['id'] : $corp_rs['id'];
		}
        switch (strtolower($param['bundle'])) {
            case 'customer':
                if ($this->model('Model_customer')->search_one(array('name'=>$param['name']))) {
					throw new Exception('Item has been exists.', 85042002);
                } elseif ($this->model('Model_customer')->get_by_id($param['id'])) {
					throw new Exception('Item has been exists.', 85042002);
                }
				$this->_save($param, 'customer');
                break;
                
            case 'merchant':
                //unset($param['status'], $param['uid']);
				if(empty($param['status']) || $param['status'] != 1) {
					unset($param['status']);
				}
				unset($param['uid']);
				$this->_save($param, 'merchant', 'UPDATE');
                break;
                
            case 'bdlist':
                if (!empty($inverted_rs)) {
					throw new Exception('Item has been exists.', 85042002);
                }
                break;
        }


        if (empty($param['id']) || ! is_numeric($param['id']) || 0 >= $param['id'] ||
            ! isset($param['alias']) || ! is_array($param['alias']) ||
            ! isset($param['industry']) || ! is_array($param['industry']) ||
            ! isset($param['status']) || ! is_numeric($param['status']) || 0 > $param['status'] || 4 < $param['status'] ||
            ! isset($param['ka_top']) || ! is_numeric($param['ka_top']) || 0 > $param['ka_top'] || 2 < $param['ka_top']
            ) {
	        return (object)$param;
        }else{
        	try{
	        	$this->update_by_id($param);
	        }catch(Exception $e){
	        	throw $e;
	        }

			return $param['id'];
        }
	}

	/**
	 * 实现ToB的ToBusiness_Corporation_update
	 */
	function update($param) {
		$param = (array)$param;
		if(empty($param) || empty($param['id']) || empty($param['name']) || trim($param['name']) == '') {
			throw new Exception('Your data is not validated', 85042001);
		}
		$param = $this->prepareItem($param);
        switch (strtolower($param['bundle'])) {
            case 'customer':
            case 'merchant':
            case 'bdlist':
                break;
			default:
				$param['bundle'] = 'bdlist';
        }
		$param['name'] = $this->_format_corporation_name($param['name']);
		$temp_name = str_replace(' ', '', $param['name']);
		$temp_name = strtolower($temp_name);
		$inverted_rs = $this->model('Model_corporation_name_inverted')->search_one(array('name' => $temp_name));
		if (empty($inverted_rs)) {
			try {
				$this->model('Model_corporation_name_inverted')->fetch_one_by_id($param['id']);
				$this->model('Model_corporation_name_inverted')->update_by_id(array('name' => $temp_name), $param['id']);
			} catch (Exception $e) {
				$this->_add_to_inverted($param['id'], $temp_name);
			}
		} else {
			if ($param['id'] != $inverted_rs['id']) {
				throw new Exception('已存在同名的公司，且公司ID不相同', 85042008);
			}
		}
		$this->_save($param, $param['bundle'], 'UPDATE');

		return (object)$param;
	}
	/**
	 * 插入公司主表和附表数据
	 * #param $op string INSERT表示插入，如果是UPDATE，就需要验证id是否存在，附表的数据需要先清理
	 */
	function _save($param, $bundle = '', $op = 'INSERT') {
		switch (strtolower($bundle)) {
			case 'bdlist':
				$type = 1;
				$master_table = 'corporation';
				break;
			case 'customer':
				$type = 2;
				$master_table = 'customer';
				break;
			case 'merchant':
				$type = 3;
				$master_table = 'corporation';
				break;
			default:
				$bundle = 'bdlist';
				$type = 1;
				$master_table = 'corporation';
		}
		if($op == 'INSERT' && $type == 2) {
			//客户新增，先去掉id，增加成功后，再修改id
			$corporation_id = $param['id'];
			unset($param['id']);
			try{
				$tmp_corporation_id = $this->model('Model_' . $master_table)->save(array($master_table=>$this->format_save_param($param, 'corporation', $op)));
				$this->model('Model_' . $master_table)->update_by_id(array('id'=>$corporation_id), $tmp_corporation_id);
			}catch(Exception $e){

			}
			
			
		} else {
			try{
				$corporation_id = $this->model('Model_' . $master_table)->save(array($master_table=>$this->format_save_param($param, 'corporation', $op)));
			
			}catch(Exception $e){}
			if(empty($params['id'])) {
				$this->model('Model_corporation')->update_by_id(array('point_to'=>$corporation_id), $corporation_id);
			}
		}

		foreach($this->type_data_2b[$bundle] as $old=>$new_model) {
			if(!empty($param[$old])) {
				$i=0;
				foreach($param[$old] as $key=>$val) {
					if(empty($val) || is_scalar($val)) continue;
					$data = $this->format_save_param($val, $new_model, $op, TRUE);
					if(empty($data)) {
						continue;
					}

					//清理旧数据,i=0的时候清理
					if($i == 0 && $op == 'UPDATE') {
						$where = array('corporation_id'=>$corporation_id, 'type_id'=>$type);
						//删除和保存一样，如果有不存在的字段，会自动跳过，而gen_id_by_uniqe则报错
						try{
							$this->model('Model_' . $new_model)->delete($where);
						} catch (Exception $e) {
							//do nothing
						}
					}
					$data['type_id'] = $type;
					$data['corporation_id'] = $corporation_id;
					$data['sort'] = $i;

					$this->model('Model_' . $new_model)->save(array($new_model=>$data));
					++$i;
				}
				
			}
		}

		return $corporation_id;
	}
	//把form数据格式化成新格式,关联表都是Insert
	function format_save_param($param, $model, $op = 'INSERT', $entity = FALSE) {
		if($op == 'INSERT' || $entity) {
			$data = $this->model('Model_' . $model)->new_one();
		} else {
			$data = array();
		}
		foreach($this->field_maps[$model] as $field=>$rule) {
			//if($op == 'UPDATE' && !isset($param[$rule['field']])) {
			if($op == 'UPDATE' && !$entity && !isset($param[$rule['field']])) {
				continue;
			}
			if($rule['default'] == '') {
				$cook_op = 'trim';
			} elseif($rule['default'] == 0.0) {
				$cook_op = 'floatval';
			} else {
				$cook_op = 'intval';
			}
			$data[$field] = $cook_op(empty($param[$rule['field']]) ? $rule['default'] : $param[$rule['field']]);
		}
		if(!empty($data)) {
			//id和name不对应，手动处理下，以id为准
			if($model == 'corporation_industry') {
				try{
					$industry_info = $this->model('Model_industry')->fetch_one_by_id($data['industry_id']);
					$data['industry_name'] = $industry_info['name'];
				} catch (Exception $e) {
					return array();
				}
			} elseif($model == 'bdlist_rc') {
				try{
					//$data['rc_id'] = $data['rc_id'] + 1;
					$rc_info = $this->model('Model_corporation_rc')->fetch_one_by_id($data['rc_id']);
					$data['rc_name'] = $rc_info['name'];
				} catch (Exception $e) {
					return array();
				}
			}
			$data['is_deleted'] = 'N';
			$data['updated_at'] = date('Y-m-d H:i:s');
		}

		return $data;
	}
	//前置数据处理
	protected function prepareItem($item) {        
		if(empty($item['bundle'])) {
			$item['bundle'] = 'bdlist';
		}
		switch ($bundle = strtolower($item['bundle'])) {
			case 'bdlist':
			case 'customer':
			case 'merchant':
				$item['bundle'] = $bundle;
				break;
	
			default:
				$item['bundle'] = 'bdlist';
		}
		//$item['updated_at'] = date('Y-m-d H:i:s');
		//$item['is_deleted'] = 'N';
		$item = $this->entity_filter($item, 'field_corporation_address', array('value'=>array('')));
		$item = $this->entity_filter($item, 'field_corporation_alias', array('value'=>array('')));
		$item = $this->entity_filter($item, 'field_corporation_contacts', array('name'=>array('')));
		$item = $this->entity_filter($item, 'field_corporation_industry', array('tid'=>array('',0)));
		$item = $this->entity_filter($item, 'field_corporation_product', array('value'=>array('')));
		$item = $this->entity_filter($item, 'field_customer_bdinfo', array('uid'=>array('',0)));
		$item = $this->entity_filter($item, 'field_customer_functions', array('hierarchy'=>array('')));         
		$item = $this->entity_filter($item, 'field_merchant_logo', array('value'=>array('')));
		$item = $this->entity_filter($item, 'field_merchant_permit', array('value'=>array('')));
		if (isset($item['field_corporation_title']) && is_array($item['field_corporation_title'])) {
			foreach ($item['field_corporation_title'] as $k => $v) {
				if (!is_array($v) || ($v['title'] == '' && empty($v['description']))) {
					unset($item['field_corporation_title'][$k]);
					continue;
				}
			}
		}

		return $item;
	}
	/*
	 * 过滤不符合要求的Entity cck数据
	 *
	 * @param object $entity
	 * @param string $field
	 * @param array  $columns
	 *
	 * e.g.
	 *   entity_filter($entity, 'field_corporation_alas', array('value'=>array('')))
	 *   当field_corporation_alas的value为空时,过滤掉该条记录
	 */
	function entity_filter($entity, $field, $columns) {
		if (empty($entity[$field])) {
			return $entity;
		}
		if (is_array($entity[$field])) {
			foreach ($entity[$field] as $k => $v) {
				if (!is_array($v)) {
					unset($entity[$field][$k]);
					continue;
				}

				//$entity[$field][$k]['is_deleted'] = 'N';
				//$entity[$field][$k]['updated_at'] = date('Y-m-d H:i:s');

				foreach ($columns as $column => $values) {
					if (!isset($v[$column])) {
						unset($entity[$field][$k]);
						continue 2;
					}
					if (in_array(trim($v[$column]), $values)) {
						unset($entity[$field][$k]);
						continue 2;
					}
				}
			}
		} else {
			unset($entity[$field]);
		}

		return $entity;
	}
    /**
     * 获取以指定公司ID为根节点的所有子节点ID
     *
     * @param array ids
     * @param int parent_id
     * @return void
     */
    private function _get_tree_ids(&$ids, $parent_id)
    {
        if (0 >= $parent_id) {
            return;
        }
        $corporations = $this->model('Model_corporation')->search_all(array('parent_id' => $parent_id));
        foreach ($corporations as $corporation) {
            $corporation_id = (int) $corporation['id'];
            if ($parent_id === $corporation_id) {
                continue;
            }
            $ids[] = $corporation_id;
            $this->_get_tree_ids($ids, $corporation_id);
        }
    }
    /**
     * 判断是否存在启用的子公司
     *
     * @param int corporation_id
     * @return boolean
     */
    private function _has_enabled_subsidiary($corporation_id)
    {
        $subsidiary_ids = array();
        $this->_get_tree_ids($subsidiary_ids, $corporation_id);
        $subsidiaries = $this->model('Model_corporation')->get_multi($subsidiary_ids);
        foreach ($subsidiaries as $subsidiary) {
            if (1 == $subsidiary['status'] || 2 == $subsidiary['status']) {
                return true;
            }
        }
        return false;
    }

    /**
     * 建立公司的关联关系(For Frank)
     *
     * $param = array('uid'       => 1,          // 用户ID
     *                'uname'     => 'kai.wang', // 用户名
     *                'id'        => 13,         // 公司ID
     *                'parent_id' => 1           // 公司父ID
     *                )
     */
    public function build_relationship($param){

        $this->log->warn('---frank_B---'.var_export($param, true));
        if (empty($param['id']) || ! is_numeric($param['id']) || 0 >= $param['id'] ||
            empty($param['parent_id']) || ! is_numeric($param['parent_id']) || 0 >= $param['parent_id']) {
            throw new Exception('参数不符合规范', 100001);
        }

        $corporation_id = (int) $param['id'];
        $parent_id = (int) $param['parent_id'];

        if ($corporation_id === $parent_id) {
            $parent_id = 0;
        }

        try{
        	$parent_corporation = $this->model('Model_corporation')->fetch_one_by_id($parent_id);
        }catch(Exception $e){
        	throw new Exception("该公司不存在！", 100002);
        }


        if (0 < $parent_id) {
            if (0 == $parent_corporation['status'] || 4 == $parent_corporation['status']) {
                throw new Exception('指向的父公司未启用', 85042006);
            }

            $tree_ids = array();
            
            $this->_get_tree_ids($tree_ids, $corporation_id);

            if (in_array($parent_id, $tree_ids)) {
                throw new Exception('目标公司的关系树中叶子节点已包含子公司', 85042007);
            }
        }

        $this->model('Model_corporation')->update_by_id(array('parent_id' => $parent_id, 'updated_at' => date('Y-m-d H:i:s')), $corporation_id);
        return $corporation_id;
    }



    private function _get_root_id($corporation_id)
    {
        $corporation = $this->model('Model_corporation')->fetch_one_by_id($corporation_id);
        $parent_id = (int) $corporation['parent_id'];
        if (0 === $parent_id || $corporation_id === $parent_id) {
            return $corporation_id;
        }
        return $this->_get_root_id($parent_id);
    }
    public function get_tree($params)
    {
        if (empty($params['id']) || ! is_numeric($params['id']) || 0 >= $params['id']) {
            throw new Exception('参数不符合规范', 100001);
        }
        $corporation_id = (int) $params['id'];

        $root_id = $this->_get_root_id($corporation_id);
        $corporation_ids = array($root_id);
        $this->_get_tree_ids($corporation_ids, $root_id);
        $corporations = $this->model('Model_corporation')->get_multi($corporation_ids);

        return $corporations;
    }

    /**
     * 获取过滤的字段
     * @return array
     */
    private function _process_selected($selected)
    {
        $filter = array();
        $allowed_keys = array('basic', 'address', 'alias', 'baike', 'baseinfo', 'contact', 'description', 'hr', 'industry', 'product', 'source', 'tag', 'title');
        foreach ($allowed_keys as $allowed_key) {
            if (! isset($selected[$allowed_key])) continue;
            $filter_fields = $selected[$allowed_key];
            if ('' === $filter_fields) {
                $filter[$allowed_key] = array();
            } else {
                $filter[$allowed_key] = explode(',', $filter_fields);
            }
        }
        return $filter;
    }

    /**
     * 过滤字段
     */
    private function _filter_field(&$row, $filter)
    {
        if (empty($filter)) return;
        foreach ($row as $key => $value) {
            if (! in_array($key, $filter)) {
                unset($row[$key]);
            }
        }
    }
	
    /**
     * 公司数据统一接口
     * 提供未经处理的公司数据统一接口
     *
     * @return array //@XXX 测试
     */
    public function get_multi_all($params)
    {
        if (empty($params['ids']) || ! is_array($params['ids'])) {
            throw new Exception('参数不符合规范', 100002);
        }
        $corporation_ids = $params['ids'];
        if (isset($params['type_id'])) {
            $type_id = (int) $params['type_id'];
        }
        if (isset($params['selected'])) {
            $selected = parse_selected(trim($params['selected']));
            $selected = $this->_process_selected($selected);
        }

        $result = array();

        // corporations
        if (! isset($selected) || array_key_exists('basic', $selected)) {
            $corporations = $this->model('Model_corporation')->get_multi($corporation_ids);
        }

        foreach ($corporation_ids as $corporation_id) {
            $condition = array('corporation_id' => $corporation_id);
            if (isset($type_id)) {
                $condition_with_type_id = array('corporation_id' => $corporation_id, 'type_id' => $type_id);
            }

            if (! isset($selected) || array_key_exists('basic', $selected)) {
                $corporation = $corporations[$corporation_id];
                $this->_filter_field($corporation, $selected['basic']);
                $result[$corporation_id]['basic'] = $corporation;
            }

            // corporations_addresses
            if (! isset($selected) || array_key_exists('address', $selected)) {
                if (isset($condition_with_type_id)) {
                    $addresses = $this->model('Model_corporation_address')->search_all($condition_with_type_id); // 数据结构不唯一
                } else {
                    $addresses = $this->model('Model_corporation_address')->search_all($condition);
                }
                foreach ($addresses as $address) {
                    $this->_filter_field($address, $selected['address']);
                    $result[$corporation_id]['address'][] = $address;
                }
            }

            // corporations_aliases
            if (! isset($selected) || array_key_exists('alias', $selected)) {
                if (isset($condition_with_type_id)) {
                    $aliases = $this->model('Model_corporation_alias')->search_all($condition_with_type_id); // 数据结构不唯一
                } else {
                    $aliases = $this->model('Model_corporation_alias')->search_all($condition);
                }
                foreach ($aliases as $alias) {
                    $this->_filter_field($alias, $selected['alias']);
                    $result[$corporation_id]['alias'][] = $alias;
                }
            }

            // corporations_baikes
            if (! isset($selected) || array_key_exists('baike', $selected)) {
                $baike = $this->model('Model_corporation_baike')->search_one($condition); // @XXX 数据结构不唯一，数据唯一
                $this->_filter_field($baike, $selected['baike']);
                $result[$corporation_id]['baike'] = $baike;
            }

            // corporations_baseinfos
            if (! isset($selected) || array_key_exists('baseinfo', $selected)) {
                if (isset($condition_with_type_id)) {
                    $baseinfos = $this->model('Model_corporation_baseinfo')->search_all($condition_with_type_id); // @XXX 数据结构不唯一，数据唯一
                } else {
                    $baseinfos = $this->model('Model_corporation_baseinfo')->search_all($condition);
                }
                foreach ($baseinfos as $baseinfo) {
                    $this->_filter_field($baseinfo, $selected['baseinfo']);
                    $result[$corporation_id]['baseinfo'][] = $baseinfo;
                }
            }

            // corporations_contacts
            if (! isset($selected) || array_key_exists('contact', $selected)) {
                if (isset($condition_with_type_id)) {
                    $contacts = $this->model('Model_corporation_contact')->search_all($condition_with_type_id); // 数据结构不唯一
                } else {
                    $contacts = $this->model('Model_corporation_contact')->search_all($condition);
                }
                foreach ($contacts as $contact) {
                    $this->_filter_field($contact, $selected['contact']);
                    $result[$corporation_id]['contact'][] = $contact;
                }
            }

            // corporations_descriptions
            if (! isset($selected) || array_key_exists('description', $selected)) {
                if (isset($condition_with_type_id)) {
                    $descriptions = $this->model('Model_corporation_description')->search_all($condition_with_type_id); // @XXX 数据结构不唯一，数据唯一
                } else {
                    $descriptions = $this->model('Model_corporation_description')->search_all($condition);
                }
                foreach ($descriptions as $description) {
                    $this->_filter_field($description, $selected['description']);
                    $result[$corporation_id]['description'][] = $description;
                }
            }

            // corporations_hrs
            if (! isset($selected) || array_key_exists('hr', $selected)) {
                $hrs = $this->model('Model_corporation_hr')->search_all($condition); // 数据结构不唯一
                foreach ($hrs as $hr) {
                    $this->_filter_field($hr, $selected['hr']);
                    $result[$corporation_id]['hr'][] = $hr;
                }
            }

            // corporations_industries
            if (! isset($selected) || array_key_exists('industry', $selected)) {
                if (isset($condition_with_type_id)) {
                    $industries = $this->model('Model_corporation_industry')->search_all($condition_with_type_id); // 数据结构不唯一
                } else {
                    $industries = $this->model('Model_corporation_industry')->search_all($condition);
                }
                foreach ($industries as $industry) {
                    $this->_filter_field($industry, $selected['industry']);
                    $result[$corporation_id]['industry'][] = $industry;
                }
            }

            // corporations_products
            if (! isset($selected) || array_key_exists('product', $selected)) {
                if (isset($condition_with_type_id)) {
                    $products = $this->model('Model_corporation_product')->search_all($condition_with_type_id); // 数据结构不唯一
                } else {
                    $products = $this->model('Model_corporation_product')->search_all($condition);
                }
                foreach ($products as $product) {
                    $this->_filter_field($product, $selected['product']);
                    $result[$corporation_id]['product'][] = $product;
                }
            }

            // corporations_sources
            if (! isset($selected) || array_key_exists('source', $selected)) {
                if (isset($condition_with_type_id)) {
                    $sources = $this->model('Model_corporation_source')->search_all($condition_with_type_id); // 数据结构唯一
                } else {
                    $sources = $this->model('Model_corporation_source')->search_all($condition);
                }
                foreach ($sources as $source) {
                    $this->_filter_field($source, $selected['source']);
                    $result[$corporation_id]['source'][] = $source;
                }
            }

            // corporations_tags
            if (! isset($selected) || array_key_exists('tag', $selected)) {
                try {
                    $tag = $this->model('Model_corporation_tag')->fetch_one_by_id($corporation_id); // 数据结构唯一
                    $this->_filter_field($tag, $selected['tag']);
                    $result[$corporation_id]['tag'] = $tag;
                } catch (Exception $e) {
                }
            }

            // corporations_titles
            if (! isset($selected) || array_key_exists('title', $selected)) {
                if (isset($condition_with_type_id)) {
                    $titles = $this->model('Model_corporation_title')->search_all($condition_with_type_id); // 数据结构不唯一
                } else {
                    $titles = $this->model('Model_corporation_title')->search_all($condition);
                }
                foreach ($titles as $title) {
                    $this->_filter_field($title, $selected['title']);
                    $result[$corporation_id]['title'][] = $title;
                }
            }

            // corporations_logos @XXX 没数据
            // corporations_importances @XXX 没关联
            // corporations_maturities @XXX 没关联
            // corporations_natures @XXX 没关联
            // corporations_rcs @XXX 没关联
            // corporations_sizes @XXX 没关联
            // corporations_types @XXX 没关联
        }

        return $result;
    }

    /** 一个公司指向另一个公司
     * @link http://192.168.1.66/?p=114
     * @param $params
     * @return string
     * @throws Exception
     */
    public function point($params){
        if(empty($params['from'])) throw new Exception('from 参数错误',10001);
        if(empty($params['to'])) throw new Exception('to 参数错误',10001);
        try{
            $res = $this->model('Model_corporation')->point($params);
        }catch (Exception $e){
            throw $e;
        }
        return $res ? 'update success' : 'time out';
    }

	/*
	 * 根据公司ID查询出其所有相关子公司
	 * @param $params['id'] 公司ID
	 * return array
	 */
	public function get_subsidiary_ids($params){
		if(empty($params['id']) || !is_numeric($params['id']) || $params['id'] <= 0 ) throw new Exception('id 参数错误',10001);

		try{
			return $this->model('Model_corporation')->get_subsidiary_ids($params['id']);
		}catch (Exception $e){
			throw $e;
		}
	}

	/*
	 * 根据公司ID、层级 查询出对应父类公司
	 * @param $params['id'] 公司ID
	 * @param $params['depth'] 层级 1为顶级
	 * return array
	 */
	public function get_parent_corporation_id($params){
		if(empty($params['id']) || !is_numeric($params['id']) || $params['id'] <= 0 ) throw new Exception('id 参数错误',10001);
		if(empty($params['depth']) || !is_numeric($params['depth']) || $params['depth'] <= 0 ) throw new Exception('depth 参数错误',10001);

		try{
			return $this->model('Model_corporation')->get_parent_corporation_id($params['id'], $params['depth']);
		}catch (Exception $e){
			throw $e;
		}
	}

	/*
	 * 根据公司ID获取所有相关父公司及子公司
	 */
	public function get_corporation_sids_by_id($params){
		if(empty($params['id']) || intval($params['id']) <= 0 ) throw new Exception('id 参数错误',10001);

		try{
			return $this->model('Model_corporation')->getCorporationSidsById(intval($params['id']));
		}catch (Exception $e){
			throw $e;
		}
	}

	/*
	 * 根据公司ID获取公司基础信息
	 */
	public function get_corporation_baseinfos($params){
		if(empty($params['id']) || intval($params['id']) <= 0 ) throw new Exception('id 参数错误',10001);

		try{
			return $this->model('Model_corporation')->getCorporationBaseinfos(intval($params['id']));
		}catch (Exception $e){
			throw $e;
		}
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
