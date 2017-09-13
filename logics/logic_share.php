<?php
/**
 * Created by PhpStorm.
 * User: qing
 * Date: 16-12-1
 * Time: 下午5:10
 */
class Logic_share extends Gsystem_logic {


    function __construct() {
        parent::__construct();
    }

    /**
     * @param $param [field='code,company_id,company_name,company_fullname_cn,en,total,trade,place,finance,resume'
     *      code=''
     *      company_id='',
     *      page=
     *      page_size=]
     * @return mixed
     * @throws Exception
     */
    public function select($param){
        $field = ['code','company_id','company_name','company_fullname_cn','company_fullname_en','total','trade','place','finance','resume'];
        $param['trade'] = $param['place'] = $param['finance'] = $param['resume'] = false;
        if(empty($param['field'])){
            $field_arr = array();
            $param['trade'] = $param['place'] = $param['finance'] = $param['resume'] = true;
        }else{
            $field_arr = explode(',',$param['field']);
        }
        $field_new='';
        foreach($field_arr as $f){
            if(!in_array($f,$field)) throw new Exception("$f 字段不在查询支持范围内",100001);
            switch ($f){
                case 'trade' : $param['trade'] = true;break;
                case 'place' : $param['place'] = true;break;
                case 'finance' : $param['finance'] = true;break;
                case 'resume' : $param['resume'] = true;break;
                default : $field_new .= $f.',';break;
            }
        }
        $param['field'] = rtrim($field_new,',');
        try{
            return $model = $this->model('Model_share')->select($param);
        }catch (Exception $e){
            throw $e;
        }
    }

    /**
     * 保存接口
     * @param $param
     */

    /**
     * @param $param array(
     *  'code'=>
     *  'company_id'=>
     *  'company_name'=>
     *  'company_fullname_cn'=>
     *  'company_fullname_en'=>
     *  'total'=>
     *  'trade'=>[
     *      'eid'=>
     *      'trade'=>
     *      'trade_alias'=>
     *   ]
     *  'place'=>
     *  'finance'=>[
     *      ['income'=>'','net_profit'=>'','close_date'=>''],
     *      ['income'=>'','net_profit'=>'','close_date'=>'']
     * ]
     *  'resume'=>
     *
     * )
     * @return mixed
     * @throws Exception
     */
    public function save($param){
        if(empty($param['code'])) throw new Exception('公司股票代码不能为空', 100001);
        if(empty($param['company_id'])) throw new Exception('公司id不能为空', 100001);
        if(strlen($param['company_name']) > 255) throw new Exception('名称过长',100001);
        if(strlen($param['company_fullname_cn']) > 255) throw new Exception('名称过长',100001);
        if(strlen($param['company_fullname_en']) > 255) throw new Exception('名称过长',100001);
        if(strlen($param['place']) > 255) throw new Exception('名称过长',100001);
        if(strlen($param['trade']['trade']) > 255) throw new Exception('名称过长',100001);
        if(!is_array($param['finance'])) throw new Exception('finance 格式错误',100001);
        $param['trade']['eid'] = empty($param['trade']['eid']) ? 0 : (int)$param['trade']['eid'];
        $param['trade']['trade'] = empty($param['trade']['trade']) ? '' : addslashes($param['trade']['trade']);
        $param['place'] = empty($param['place']) ? '' : addslashes($param['place']);
        $param['resume'] = empty($param['resume']) ? '' : addslashes($param['resume']);

        foreach($param['finance'] as $k=>$f){
            if(strpos($f['income'],',') !== false) $param['finance'][$k]['income'] = str_replace(',','',$f['income']);
            if(strpos($f['net_profit'],',') !== false) $param['finance'][$k]['net_profit'] = str_replace(',','',$f['net_profit']);
            if(strpos($f['income'],'万元') !== false) $param['finance'][$k]['income'] = str_replace('万元','',$f['income']) * 10000;
            if(strpos($f['net_profit'],'万元') !== false) $param['finance'][$k]['net_profit'] = str_replace('万元','',$f['net_profit']) * 10000;
        }
        try{
            return $model = $this->model('Model_share')->save($param);
        }catch (Exception $e){
            throw $e;
        }
    }

    /** 刷库接口
     * @param $param [
     *      type=1      //1、刷入shares_company_mapping  2、刷入trade  3、刷入place  4、刷入shares_manager、刷入shares_finance、刷入shares
     *      data=[      //分别是对应type的表的字段
     *          //share_company_mapping  刷公司id字典库   人工添加的
     *          {'code'=>'',
     *          'company_id'=>'',
     *          'short_name'=>''}
     *
     *          //shares_trade_dic  行业板块
     *          {'eid'=>'',
     *          'trade'=>'',
     *          'trade_alias'=>''}
     *
     *          //shares_place_dic  上市地区
     *          {'name'=>'',
     *          'name_alias'=>''}
     *
     *
     *          //shares
     *          {'code'=>'',
     *          'trade'=>'',
     *          'place'=>'',
     *          'company_name'=>'',
     *          'company_fullname_cn'=>'',
     *          'company_fullname_en'=>'',
     *          'total'=>''
     *          'resume'=>'',
     *          'finance'=>{
                    'income'=>'',
     *              'net_profit'=>'',
     *              'close_date'=>
     *              }
     *          }
     *
     *      ]
     * ]
     * @return mixed
     * @throws Exception
     */
    public function refresh($param){
        if(empty($param['type'])) throw new Exception('type-格式错误！', 100001);
        if(empty($param['data'])) throw new Exception('data-格式错误！', 100001);
        try{
            switch ((int)$param['type']){
                case 1:
                    $result = $this->model('Model_share')->company_mapping($param['data']);break;
                case 2:
                    $result = $this->model('Model_share')->trade($param['data']);break;
                case 3:
                    $result = $this->model('Model_share')->place($param['data']);break;
                case 4:
                    $result = $this->model('Model_share')->shares($param['data']);break;
                default:
                    $result = '';break;
            }
            return $result;
        }catch (Exception $e){
            throw $e;
        }
    }
}