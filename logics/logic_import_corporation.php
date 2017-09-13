<?php 
class Logic_import_corporation extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_function'), $func), $args);
	}
	/**
	 * 导入国靖给的公司数据
	 */
	function import($param) {
		if(empty($param) || !is_array($param)) {
			return false;
		}
		foreach($param as $item) {
			if(empty($item['corporation']['source'])) {
				continue;
			}
			$data = $item['corporation'];
			switch($data['source']) {
				case '51job':
					$data['type_id'] = '4';
					return $this->import_51job($data);
					break;
				case 'zhilian':
					$data['type_id'] = '5';
					return $this->import_zhilian($data);
					break;
				default:
					continue;
			}
		}
	}
	/**
	 * 导入51job
	 */
	function import_51job($param) {
		if(empty($param['name'])) {
			return false;
		}
		$this->model('Model_corporation')->beginTransaction();
		try {
			$param = $this->trim_array($param);

			$type_id = $param['type_id'];

			$data = array(
				'website'   => empty($param['homepage']) ? '' : $param['homepage'],
				'name'  => $param['name'],
				//'cityname'  => $param['address'], //这是公司地点，不是公司所在城市
				'nature_name'   => empty($param['type']) ? '' : $param['type'],
				'size_name' => empty($param['scale']) ? '' : $param['scale'],
				'uid'   => 1,
			); 
			$corporation_id = $this->model('Model_corporation')->gen_id_by_unique($data);

			if(!empty($param['address'])) {
                $info = $this->model('Model_corporation_address')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                if(empty($info) || $info['address'] != $param['address']) {
                    $save_data = array(
                        'type_id' => $type_id,
                        'corporation_id'    => $corporation_id,
                        'sort'  => 0,
                        'address'   => $param['address'],
                        'is_deleted'    => 'N',
                        'updated_at'    => date('Y-m-d H:i:s'),
                    );
                    if(!empty($info)) {
                        $save_data['id'] = $info['id'];
                    }
                    $this->model('Model_corporation_address')->save(array('corporation_address'=>$save_data));
                }
			}
			if(!empty($param['desc'])) $param['desc'] = $this->trim_html($param['desc']);
			if(!empty($param['desc'])) {
                $info = $this->model('Model_corporation_description')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                if(empty($info) || $info['description'] != $param['desc']) {
                    //公司描述
                    $save_data = array(
                        'corporation_id'    => $corporation_id,
                        'type_id'   => $type_id,
                        'sort'  => 0,
                        'intro' => '',
                        'notes' => '',
                        'description'  => $param['desc'],
                        'is_deleted'    => 'N',
                        'updated_at'    => date('Y-m-d H:i:s'),
                    );
                    if(!empty($info)) {
                        $save_data['id'] = $info['id'];
                    }
                    $this->model('Model_corporation_description')->save(array('corporation_description'=>$save_data));
                }
			}
			//行业
			if(!empty($param['industry'])) {
                $info = $this->model('Model_corporation_industry')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                if(empty($info) || $info['industry_name'] != $param['industry']) {
                    $save_data = array(
                        'type_id'   => $type_id,
                        'corporation_id'    => $corporation_id,
                        'sort'  => 0,
                        'industry_id'   => 0,
                        'industry_name' => $param['industry'],
                        'is_deleted'    => 'N',
                        'updated_at'    => date('Y-m-d H:i:s'),
                    );
                    if(!empty($info)) {
                        $save_data['id'] = $info['id'];
                    }
                    $this->model('Model_corporation_industry')->save(array('corporation_industry'=>$save_data));
                }
			}
			//公司来源表 corporations_sources
			$this->model('Model_corporation_source')->gen_id_by_unique(array('corporation_id'=>$corporation_id,'type_id'=>$type_id));

			$this->model('Model_corporation')->commit();

			return true;
		} catch (Exception $e) {
            $this->log->warn(sprintf('Exception %s:%s %s', $e->getFile(), $e->getLine(), $e->getMessage()));
			$this->model('Model_corporation')->rollback();

			return false;
		}
	}
	/**
	 * 导入智联
	 */
	function import_zhilian($param) {
		return $this->import_51job($param);
	}
    function trim_array($data) {
        if(is_string($data)) {
            return trim(trim($data), ' ');
        }
        if(is_array($data)) {
            foreach($data as $key=>$val) {
                $data[$key] = $this->trim_array($val);
            }
        }

        return $data;
    }
    function trim_html($txt) {
        $txt = str_ireplace(
            array('&nbsp;', "\r", "\n"),
            array(' ', '', ''),
            strip_tags($txt)
        );

        return trim(preg_replace("/\s{2,}/",',',$txt));
    }
}
