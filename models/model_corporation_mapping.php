<?php
/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2016/12/20
 * Time: 14:23
 */
class Model_corporation_mapping extends Gsystem_model {

    /*
     * 保存公司映射
     */
    public function save($params){
        $success_num = 0;
        $error_result = [];
        $values = '';
        foreach($params as $val){
            if(!is_numeric($val['company_id'])){
                $error_result[] = [ 'company_id' => $val['company_id'], 'msg' => 'company_id参数格式不合法'];
                continue;
            }
            // 检查公司ID是否已存在
            if($this->checkCompanyIsExist($val['company_id'])){
                $error_result[] = [ 'company_id' => $val['company_id'], 'msg' => 'company_id数据已存在'];
                continue;
            }

            if(!is_numeric($val['pcompany_id'])){
                $error_result[] = [ 'company_id' => $val['company_id'], 'msg' => 'pcompany_id参数格式不合法'];
                continue;
            }
            if(!is_numeric($val['depth'])){
                $error_result[] = [ 'company_id' => $val['company_id'], 'msg' => 'depth参数格式不合法'];
                continue;
            }
            if(!is_numeric($val['horizontal'])){
                $error_result[] = [ 'company_id' => $val['company_id'], 'msg' => 'horizontal参数格式不合法'];
                continue;
            }
            if(!is_numeric($val['stype'])){
                $error_result[] = [ 'company_id' => $val['company_id'], 'msg' => 'stype参数格式不合法'];
                continue;
            }

            $values .= '(' . $val['company_id'] . ',' . $val['pcompany_id'] . ',' . $val['depth'] . ',' . $val['horizontal'] . ',' . $val['stype'] . ",'N'),";
            $success_num++;
        }
        $values = rtrim($values,',');

        if($values){
            $sql = "replace into `corporations_mapping` (`company_id`,`pcompany_id`,`depth`,`horizontal`,`stype`,`is_deleted`) VALUES $values";
            $this->parse_sql($sql,false);
            $this->log->warn('corporation_mapping保存数据库成功！');
        }

        return [
            'total' => count($params),
            'success_num' => $success_num,
            'error_result' => [
                'total' => count($error_result),
                'list' => $error_result,
            ],
        ];
    }

    /*
     * 删除公司映射
     */
    public function delete($company_id){
        // 检查公司ID是否已存在
        if(!$this->checkCompanyIsExist($company_id)){
            return "公司ID：{$company_id}不存在，请核实后重新处理";
        }

        // 软删除
        $this->parse_sql("update corporations_mapping set is_deleted='Y' where company_id={$company_id}",false);
        return 'delete success';
    }

    /*
     * 检查公司映射是否存在
     * @param int $company_id 公司ID
     * return int
     */
    public function checkCompanyIsExist($company_id){
        $sql = "select company_id from corporations_mapping where company_id=$company_id and is_deleted='N' limit 1";
        $res = $this->parse_sql($sql);
        return count($res);
    }

    /*
     * 查询公司映射
     */
    public function select($company_id){
        $sql = "select * from corporations_mapping where company_id=$company_id and is_deleted='N' limit 1";
        $rs =  $this->parse_sql($sql);
        return empty($rs) ? []: $rs[0];
    }

    public function update($param){
        // 检查公司ID是否已存在
        if(!$this->checkCompanyIsExist($param['company_id'])){
            return 'company_id不存在';
        }
        $time = date('Y-m-d H:i:s', time());
        $sql = "update corporations_mapping set pcompany_id={$param['pcompany_id']},depth={$param['depth']},horizontal={$param['horizontal']},stype={$param['stype']},updated_at='{$time}' where company_id={$param['company_id']}  and is_deleted='N'";
        $this->parse_sql($sql, false);
        return 'update success';
    }

}