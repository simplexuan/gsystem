<?php 
class Logic_corporation_tag extends Gsystem_logic {
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
        return call_user_func_array(array($this->model('Model_corporation_tag'), $func), $args);
    }
    /**
     * save 
     * 
     * @param mixed $post_data 
     * @access public
     * @return mixed
     */
    function save($post_data) {
        $adjust_data = $this->_adjust($post_data);
        return $this->model('Model_corporation_tag') ->save($adjust_data);
    }
    /**
     * @return mixed
     */
    protected function _adjust(&$post_data) {
        $new_one = $this->model('Model_corporation_tag')->new_one();
        $post_data['corporation_tag'] = array_merge($new_one, array_intersect_key($post_data['corporation_tag'], $new_one));
        return $post_data;
    }
    function search($param){
		$param['page']     = empty($param['page']) ? 1 : max(intval($param['page']), 1);
		$param['pagesize'] = empty($param['pagesize']) ? 1000 : min(abs(intval($param['pagesize'])), 1000);
        $corporation_tags = $this->model('Model_corporation_tag')->search($param, $param['page'], $param['pagesize']);
        $corporation_tags['results'] = array_values($corporation_tags['results']);
        return $corporation_tags;
    }

    //只支持单id
    function is_katop ($params) {
        if(empty($params['id'])) {
            throw new Exception('参数不完整', 100002);
        }

        try {
            $this->model('Model_corporation_tag')->fetch_one_by_id($params['id']);
            return 1;
        } catch (Exception $e) {
        }

        try {
            $corporation = $this->model('Model_corporation')->fetch_one_by_id($params['id']);
            if (0 != $corporation['parent_id'] && $params['id'] != $corporation['parent_id']) {
                return $this->is_katop(array('id' => $corporation['parent_id']));
            }
        } catch (Exception $e) {
        }
        return 0;
    }
    /**
     * 获取多个公司的KA/TOP状态
     * 用于新公司清洗平台的获取多个公司的KA/TOP状态
     *
     * @return array
     */
    public function get_tags($params)
    {
        if (empty($params['ids']) || ! is_array($params['ids'])) {
            throw new Exception('参数不符合规范', 100001);
        }

        $result = array();
        foreach ($params['ids'] as $id) {
            $result[$id] = $this->get_tag(array('id' => $id));
        }
        return $result;
    }
    /**
     * 获取单个公司的KA/TOP状态
     * 用于新公司清洗平台的获取单个公司的KA/TOP状态
     *
     * @return array
     */
    public function get_tag($params)
    {
        if (empty($params['id']) || ! is_numeric($params['id']) || 0 >= $params['id']) {
            throw new Exception('参数不符合规范', 100001);
        }
        $corporation_id = (int) $params['id'];

        try {
            $corporation_tag = $this->model('Model_corporation_tag')->fetch_one_by_id($corporation_id);
            if (0 != $corporation_tag['is_ka'] || 0 != $corporation_tag['is_top']) {
                return array('is_ka'  => $corporation_tag['is_ka'],
                             'is_top' => $corporation_tag['is_top']);
            }
        } catch (Exception $e) {
        }

        try {
            $corporation = $this->model('Model_corporation')->fetch_one_by_id($corporation_id);
            $parent_id = (int) $corporation['parent_id'];
            if (0 !== $parent_id && $corporation_id !== $parent_id) {
                return $this->get_tag(array('id' => $parent_id));
            }
        } catch (Exception $e) {
        }
        return array('is_ka'  => 0,
                     'is_top' => 0);
    }
    /**
     * 获取指定公司的父级公司的KA/TOP状态
     * 用于新公司清洗平台的获取指定公司的父级公司的KA/TOP状态
     *
     * @return array
     */
    public function get_parent_tag($params)
    {
        if (empty($params['id']) || ! is_numeric($params['id']) || 0 >= $params['id']) {
            throw new Exception('参数不符合规范', 100001);
        }
        $corporation_id = (int) $params['id'];

        $parent_tag = array('is_ka'  => 0,
                            'is_top' => 0);
        try {
            $corporation = $this->model('Model_corporation')->fetch_one_by_id($corporation_id);
            $parent_id = (int) $corporation['parent_id'];

            $corporation_tag = $this->model('Model_corporation_tag')->fetch_one_by_id($parent_id);
            $parent_tag['is_ka'] = $corporation_tag['is_ka'];
            $parent_tag['is_top'] = $corporation_tag['is_top'];
        } catch (Exception $e) {
        }

        return $parent_tag;
    }
}
