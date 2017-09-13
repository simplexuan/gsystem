<?php
/**
 * Created by PhpStorm.
 * User: qing
 * Date: 16-12-1
 * Time: 下午5:22
 */
class Model_share extends Gsystem_model{


    /** 查询操作
     * @param $param
     * @return mixed
     */
    public function select($param){
        $where = empty($param['code']) ? '' : "code='{$param['code']}' and ";
        $where .= empty($param['company_id']) ? '' : "company_id='{$param['company_id']}' and ";
        $where .= "is_deleted='N'";
        $page = empty($param['page']) ? null : (int)$param['page'];
        $page_size = empty($param['page_size']) ? 1000 : (int)$param['page_size'];
        $field = empty($param['field']) ? '*' : $param['field'].",trade_id,listing_id";

        //查主表
        if(is_null($page)){
            $sql = "select $field from shares where $where";
        }else{
            $sql="SELECT $field FROM `shares` WHERE code >= (SELECT code FROM `shares` where $where ORDER BY code asc LIMIT " . ($page - 1) * $page_size . ", 1) and $where ORDER BY code asc LIMIT $page_size";
        }

        $result = $this->parse_sql($sql);
        foreach($result as $key=>$row){
            if($param['trade']){
                $trade = $this->parse_sql("select eid,trade from shares_trade_dic where id={$row['trade_id']}");
                $result[$key]['trade']=$trade[0];
            }

            if($param['place']){
                $place = $this->parse_sql("select `name` from shares_place_dic where id='{$row['listing_id']}'");
                $result[$key]['place']=$place[0]['name'];
            }

            if($param['finance']){
                $result[$key]['finance'] = $this->parse_sql("select income,net_profit,close_date from shares_finance where code='{$row['code']}'");
            }

            if($param['resume']){
                $result[$key]['resume']=$this->parse_sql("select resume from shares_manager where code='{$row['code']}'");
            }
            unset($result[$key]['trade_id'],$result[$key]['listing_id'],$result[$key]['is_refresh'],$result[$key]['is_deleted'],$result[$key]['updated_at'],$result[$key]['created_at']);
        }
        return $result;
    }

    /** 公司股票信息存储
     * @param $param
     * @return string
     * @throws Exception
     */
    public function save($param){

        //table shares_trade_dic
        $trade = $param['trade'];
        if (!empty($trade['trade']) && !empty($trade['eid'])) {
            $result = $this->parse_sql("select id from shares_trade_dic where eid='{$trade['eid']}' and trade='{$trade['trade']}'");
            if ($result) {
                $trade_id = $result[0]['id'];
            } else {
                $trade_id = $this->parse_sql("insert into shares_trade_dic(`eid`,`trade`) VALUES ({$trade['eid']},'{$trade['trade']}')",false,true);
            }
        } else {
            $trade_id = 0;
        }

        //table shares_place_dic
        if (!empty($param['place'])) {
            $result = $this->parse_sql("select id from shares_place_dic where name='{$param['place']}'");
            if ($result) {
                $place_id = $result[0]['id'];
            } else {
                $place_id = $this->parse_sql("insert into shares_place_dic(`name`) VALUES ('{$param['place']}')",false,true);
            }
        } else {
            $place_id = 0;
        }

        //table shares_manager
        if (!empty($param['resume'])) {
            $result = $this->parse_sql("select `code` from shares_manager where `code`='{$param['code']}'");
            if ($result) {
                $this->parse_sql("update shares_manager set resume='".addslashes(htmlentities($param['resume']))."',company_id='{$param['company_id']}' where code='{$param['code']}'",false);
            } else {
                $this->parse_sql("insert into shares_manager(code,company_id,resume) VALUES ('{$param['code']}','{$param['company_id']}','".addslashes(htmlentities($param['resume']))."')",false);
            }
        }

        //table shares_finance
        $param['finance'] = is_array($param['finance']) ? $param['finance'] : array();
        foreach ($param['finance'] as $finance) {
            $result = $this->parse_sql("select `code`,`id` from shares_finance where code='{$param['code']}' and company_id='{$param['company_id']}' and close_date='{$finance['close_date']}'");
            if ($result) {
                $this->parse_sql("update shares_finance set income='{$finance['income']}',net_profit='{$finance['net_profit']}' where id='{$result[0]['id']}'",false);
            } else {
                $this->parse_sql("insert into shares_finance(code,company_id,income,net_profit,close_date) VALUES ('{$param['code']}',{$param['company_id']},'{$finance['income']}','{$finance['net_profit']}','{$finance['close_date']}')",false);
            }
        }

        //table shares
        $result = $this->parse_sql("select `code` from shares where code='{$param['code']}'");
        if ($result) {
            $time = date('Y-m-d H:i:s');
            $this->parse_sql("update shares set company_id='{$param['company_id']}',company_name='{$param['company_name']}',company_fullname_cn='{$param['company_fullname_cn']}',company_fullname_en='{$param['company_fullname_en']}',total='{$param['total']}',trade_id='$trade_id',listing_id='$place_id',is_refresh='N',is_deleted='N',updated_at='{$time}' where code='{$param['code']}'",false);
        } else {
            $this->parse_sql("insert into shares(code,company_id,company_name,company_fullname_cn,company_fullname_en,total,trade_id,listing_id) values ('{$param['code']}','{$param['company_id']}','{$param['company_name']}','{$param['company_fullname_cn']}','{$param['company_fullname_en']}','{$param['total']}','$trade_id','$place_id')",false);
        }
        return 'save success';
    }


    /** 刷公司id字典库   人工添加的
     * @param $param array 多条 如果是单条，要包在array中
     * @throws Exception
     */
    public function company_mapping($param){
        foreach($param as $p){
            $result = $this->parse_sql("select code from share_company_mapping where code='{$p['code']}'");
            if(empty($result)) $this->parse_sql("insert into share_company_mapping(`code`,`company_id`,`short_name`) VALUES ('{$p['code']}','{$p['company_id']}','{$p['short_name']}')",false);
        }
    }

    /** 行业板块
     * @param $param
     * @throws Exception
     */
    public function trade($param){
        foreach($param as $p){
            $result = $this->parse_sql("select trade from shares_trade_dic where trade='{$p['trade']}'");
            if(empty($result)) $this->parse_sql("insert into shares_trade_dic(`eid`,`trade`,`trade_alias`) VALUES ('{$p['eid']}','{$p['trade']}','{$p['trade_alias']}')",false);
        }
    }

    /** 上市地区
     * @param $param
     * @throws Exception
     */
    public function place($param){
        foreach($param as $p){
            $result = $this->parse_sql("select `name` from shares_place_dic where `name`='{$p['name']}'");
            if(empty($result)) $this->parse_sql("insert into shares_place_dic(`name`,`name_alias`) VALUES ('{$p['name']}','{$p['name_alias']}')",false);
        }
    }


    /** 金融主表
     * @param $param 
     */
    public function shares($param){
        foreach($param as $p){
            $company_id = $this->get_company_id($p['code'],$p['company_name']);

            //高管简历
            $result = $this->parse_sql("select code from shares_manager where code='{$p['code']}'");
            if(empty($result) && !empty($p['resume'])){
                $this->parse_sql("insert into shares_manager(`code`,`company_id`,`resume`) VALUES ('{$p['code']}','{$company_id}','".addslashes($p['resume'])."')",false);
            }

            //资金收入
            foreach($p['finance'] as $f){
                $time = strtotime($f['close_date']);

                if(strpos($f['income'],',') !== false) $f['income'] = str_replace(',','',$f['income']);
                if(strpos($f['net_profit'],',') !== false) $f['net_profit'] = str_replace(',','',$f['net_profit']);
                if(strpos($f['income'],'万元') !== false) $f['income'] = str_replace('万元','',$f['income']) * 10000;
                if(strpos($f['net_profit'],'万元') !== false) $f['net_profit'] = str_replace('万元','',$f['net_profit']) * 10000;
                if(strpos($f['income'],'万美元') !== false) $f['income'] = str_replace('万美元','',$f['income']) * 10000;
                if(strpos($f['net_profit'],'万美元') !== false) $f['net_profit'] = str_replace('万美元','',$f['net_profit']) * 10000;
                if(strpos($f['income'],'万港元') !== false) $f['income'] = str_replace('万港元','',$f['income']) * 10000;
                if(strpos($f['net_profit'],'万港元') !== false) $f['net_profit'] = str_replace('万港元','',$f['net_profit']) * 10000;
                if(strpos($f['income'],'万港币') !== false) $f['income'] = str_replace('万港币','',$f['income']) * 10000;
                if(strpos($f['net_profit'],'万港币') !== false) $f['net_profit'] = str_replace('万港币','',$f['net_profit']) * 10000;
                if(strpos($f['income'],'元') !== false) $f['income'] = str_replace('元','',$f['income']);
                if(strpos($f['net_profit'],'元') !== false) $f['net_profit'] = str_replace('元','',$f['net_profit']);


                $res = $this->parse_sql("select id from shares_finance where code='{$p['code']}' and company_id='{$company_id}' and close_date='$time'");
                if(empty($res)){
                    $this->parse_sql("insert into shares_finance(`code`,`company_id`,`income`,`net_profit`,`close_date`) VALUES ('{$p['code']}','{$company_id}','{$f['income']}','{$f['net_profit']}','$time')",false);
                }else{
                    $this->parse_sql("update shares_finance set income='{$f['income']}',net_profit='{$f['net_profit']}' where id={$res[0]['id']}",false);
                }
            }

            $result = $this->parse_sql("select code from shares where code='{$p['code']}'");
            if(empty($result)){
                $trade = $this->parse_sql("select id from shares_trade_dic where `trade`='{$p['trade']}'");
                $trade_id = empty($trade[0]['id']) ? 0 : (int)$trade[0]['id'];
                $place = $this->parse_sql("select id from shares_place_dic where `name`='{$p['place']}'");
                $place_id = empty($place[0]['id']) ? 0 : (int)$place[0]['id'];
                $this->parse_sql("insert into shares(`code`,`company_id`,`company_name`,`company_fullname_cn`,`company_fullname_en`,`total`,`trade_id`,`listing_id`,`is_refresh`) VALUES ('{$p['code']}','{$company_id}','".addslashes($p['company_name'])."','".addslashes($p['company_fullname_cn'])."','".addslashes($p['company_fullname_en'])."','{$p['total']}','{$trade_id}','{$place_id}','Y')",false);
            }
        }
    }

    /** 获取公司id
     * @param $code string 股票代码
     * @param $name string 公司名称
     * @return int  公司id
     */
    private function get_company_id($code,$name){
        //首先取mapping表
        $result = $this->parse_sql("select company_id from share_company_mapping where code='{$code}'");
        if(!empty($result)) return (int)$result[0]['company_id'];

        //再公司识别 corp_tag
        return $this->apis->corp_tag($name);
    }
}