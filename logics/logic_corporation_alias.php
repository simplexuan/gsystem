<?php 
class Logic_corporation_alias extends Gsystem_logic {
	    
    /**
     * 获取公司别名
     * @link http://192.168.1.66/?p=260
     */
    public function get_multi($params){
        if(empty($params['ids'])) throw new Exception('ids不能为空',100001);
        $params['ids'] = is_array($params['ids']) ? $params['ids'] : array($params['ids']);
        $ids = implode(',',$params['ids']);
        
        try{
            $res = $this->model('Model_corporation_alias')->get($ids);
        }catch (Exception $e){
            throw $e;
        }
        
        return $res;
    }
}