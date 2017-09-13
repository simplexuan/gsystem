<?php 
class Logic_corporation_product_cluster extends Gsystem_logic {
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
		return call_user_func_array(array($this->model('Model_corporation_product_cluster'), $func), $args);
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
			$selected  = $selected + array('corporation_product_cluster'=>array())
				;
		}

		$c['corporation_product'] = $this->model('Model_corporation_product_cluster')->fetch_one_by_id($id, $selected['corporation_product_cluster']);
		if (empty($c['corporation_product']) ){
			throw new Exception(sprintf('%s: The id %d does not exist or has been deleted.',
				__FUNCTION__, $id), $this->config->item('data_exist_err_no', 'err_no'));  
		} 


		return $c; 
	}

	/*
     * 同步公司产品信息
     */
	public function sync_corporation_product($param){
		if(empty($param['cid'])) throw new Exception('cid不能为空', 100001);
		if(empty($param['product'])) throw new Exception('product不能为空', 100001);
		if($param['cid'] <= 0) throw new Exception('cid不能小于1', 100001);

		return $this->model('Model_corporation_product_cluster')->sync_corporation_product($param['cid'], $param['product']);
	}

	/*
     * 根据ids获取公司产品数据
     */
	public function get_products_by_ids($param){
		if(empty($param['ids'])) throw new Exception('ids不能为空', 100001);
		if(!is_array($param['ids'])) throw new Exception('ids必须为数组', 100001);

		return $this->model('Model_corporation_product_cluster')->getProductsByIds($param['ids']);
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
