<?php
/**
 * Model_corporation_product
 *
 * @uses Gsystem
 * @uses _Dao
 * @package
 * @version $id$
 * @copyright Copyright (c) 2012-2014 Yicheng Co. All Rights Reserved.
 * @author Jiqing Sun
 * @license
 */
class Model_corporation_product_cluster extends Gsystem_model {
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
			'Gsystem_Model_corporation_product_cluster_corporation_id:%d' => array('corporation_id' ),
			 
			'Gsystem_Model_corporation_product_cluster' => array(),
			);
	/**
	 * _equal_search_items 
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_equal_search_items = array('corporation_id'=>'t','type_id'=>'t',);
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
		$new_corporation_product  = array();
		if ($id > 0) {
			$new_corporation_product['corporation_product_cluster'] = $this->dao('/Dao_corporation_product_cluster', $dao_param)->fetch_one_by_id($id);
		} else {
			$new_corporation_product['corporation_product_cluster']  = $this->dao('/Dao_corporation_product_cluster', $dao_param)->new_one();
		}
								return $new_corporation_product;
	}

	/*
     * 同步公司产品信息
     * @param int $cid 公司ID
     * @param array $product 产品
     * return array
     */
	public function sync_corporation_product($cid, $product){
		if(empty($cid) || empty($product)) throw new Exception('参数不能为空', 100001);

		// 删除软删除的公司产品数据
		$this->parse_sql("delete from corporations_product_cluster where corporation_id = {$cid} and is_deleted = 'Y'", false);
		// 将现有公司产品更新为软删除
		$now = date('Y-m-d H:i:s');
		$this->parse_sql("update corporations_product_cluster set is_deleted = 'Y',updated_at = '{$now}' where corporation_id = {$cid}",false);

		// 新增公司产品
		$values = '';
		foreach($product as $val){
			$title 		= !empty($val['title']) ? $val['title']: '';
			$pic_url 	= !empty($val['pic_url']) ? $val['pic_url']: '';
			$desc 		= !empty($val['desc']) ? $val['desc']: '';
			if(empty($title) && empty($pic_url) && empty($desc)) continue;
			$values .= '(' . $cid . ",'" . $title . "','" . $pic_url . "','" . $desc . "'),";
		}

		$values = rtrim($values, ',');
		if(!empty($values)){
			$this->parse_sql("insert INTO corporations_product_cluster (`corporation_id`,`title`,`pic_url`,`description`) VALUES $values", false);
		}

		return [];
	}

	/*
     * 根据公司IDS获取产品列表
     * @param array $ids 公司ID集
     * return array
     */
	public function getProductsByIds($ids){
		if(empty($ids) || !is_array($ids)) throw new Exception('参数不合法', 100001);

		$cache_key = 'CORPORATION_PRODUCT_' . md5('getProductsByIds' . serialize($ids));
		if(empty($result = $this->cache->memcached->get($cache_key))) {
			$ids = implode(',', $ids);
			$sql = "select corporation_id,title,pic_url,description from corporations_product_cluster where corporation_id in ({$ids}) and is_deleted = 'N' order by corporation_id";
			$list = $this->parse_sql($sql);

			$result = [];

			foreach ($list as $val) {
				$result[$val['corporation_id']][] = $val;
			}
			$this->cache->memcached->save($cache_key, $result);
		}

		return $result;
	}

}
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
