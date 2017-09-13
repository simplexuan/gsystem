<?php

/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2017/2/28
 * Time: 15:05
 */
class model_weals extends Gsystem_model
{

    /*
     * 通过公司ID获取公司福利列表
     * @param int id 公司ID
     * return array
     */
    public function get_corporation_weals($param){
        if(empty($param['id'])) throw new Exception('公司ID为空，请核实', 100001);
        $sql = "select m.`corporation_id`,m.`weal_id`,w.`name` as weal_name,w.`status` from corporations_weal_map m inner join corporations_weal w on w.id = m.weal_id where m.`is_deleted` = 'N' and m.corporation_id = " . intval($param['id']);
        $result  = $this->parse_sql($sql);
        return !empty($result) ? $result: [];
    }

    /*
     * 保存公司福利接口
     * @param int id 公司ID
     * return array
     */
    public function save_corporation_weal($param){
        // 获取福利数据
        $weal_list = $this->get_weal_ids($param['weals']);

        if(empty($weal_list)) throw new Exception('无数据需处理', 100001);

        // 删除当前公司的福利
        L("保存公司福利 删除公司关联福利 {$param['id']}",4);
        $this->parse_sql("delete from corporations_weal_map where corporation_id = {$param['id']}", false);

        // 新增公司福利
        $values = '';
        foreach($weal_list as $val){
            $values .= "('".$param['id']."','".$val."'),";
        }
        $values = rtrim($values, ',');
        L("保存公司福利 新增公司关联福利 {$values}",4);
        $this->parse_sql("insert INTO corporations_weal_map (`corporation_id`,`weal_id`) VALUES $values", false);

        return [];
    }

    /*
     * 根据福利名称集获取对应id集
     * @param array $param 福利集
     */
    public function get_weal_ids($param){
        $param = array_unique($param);

        $result = [];
        $where = " `is_deleted` = 'N' and name in (";
        $weal_list = [];
        foreach($param as $val){
            $temp = trim($val);
            $weal_list[$temp] = $temp;
            $where .= "'" . $temp . "',";
        }
        $where = rtrim($where, ',') . ")";

        $list = $this->parse_sql("select `id`,`name` from corporations_weal where $where order by id asc");

        // 收集存在数据库中的福利
        foreach($list as $val){
            unset($weal_list[$val['name']]);
            $result[] = $val['id'];
        }

        // 未存在数据库中的福利，进行新增
        foreach($weal_list as $val){
            if($id = $this->addCorporationsWeal($val)){
                L("保存公司福利 新增福利 $id：$val...",4);
                $result[] = $id;
            }
        }
        return $result;
    }

    /*
     * 新增公司福利
     * @param string $name 福利名称
     * return boolean
     */
    public function addCorporationsWeal($name){
        if(empty($name)) return 0;
        return $this->parse_sql("INSERT INTO corporations_weal (`name`) VALUES ('$name')", false, true);
    }

}