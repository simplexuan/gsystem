<?php
/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2016/12/20
 * Time: 10:53
 */
class Model_logo extends Gsystem_model {

    /*
     * 批量保存公司或学校logo
     */
    public function save($params){
        //是否传递id  如果传递则直接操作，否则去取id
        $type = (int)$params['type'];
        $src_no = (int)$params['src_no'];
        $table = $type == 1 ? 'schools_logos' : 'corporations_logos';
        $field = $type == 1 ? 'school_id' : 'corporation_id';

        $success_num = 0;
        $error_result = [];

        $values = '';
        foreach($params['data'] as $d){
            //id如果不存在则去接口识别，如果识别不了则抛弃这条数据
            if(empty($d['id'])) $d['id'] = $this->get_id($d['name'],$type);
            $id = (int)$d['id'];
            if($id === 0){
                $error_result[] = [ 'id' => $d['id'], 'msg' => 'id不合法或name找不到对应id'];
                continue;
            }

            $cache_key = "logo_{$type}_{$id}_{$src_no}";

            //查缓存是否存在，如果缓存存在，则抛弃这条数据
            if(!empty($cache = $this->cache->memcached->get($cache_key))){
                $this->log->warn("$id memcached 已经存在！");
                $error_result[] = [ 'id' => $d['id'], 'msg' => '数据已存在'];
                continue; //缓存存在
            }
            $sql = "select logo from {$table} where {$field}={$id} and type_id={$src_no} and is_deleted='N' limit 1";
            $res = $this->parse_sql($sql);
            if(count($res) == 1){
                $this->log->warn("$id mysql 已经存在！");
                $this->cache->memcached->save($cache_key, $res[0]['logo']);
                $error_result[] = [ 'id' => $d['id'], 'msg' => '数据已存在'];
                continue;
            }

            //保存缓存数据
            unset($d['name']);
            $this->cache->memcached->save($cache_key, $d['logo']);

            //保存到数据库
            $values .= "('".$src_no."','$id','{$d['logo']}'),";
            $success_num++;
        }
        $values = rtrim($values,',');
        if($values){
            $sql = "insert into `{$table}` (`type_id`,`{$field}`,`logo`) VALUES $values";
            $this->parse_sql($sql,false);
            $this->log->warn('保存数据库成功！');
        }

        return [
            'total' => count($params['data']),
            'success_num' => $success_num,
            'error_result' => [
                'total' => count($error_result),
                'list' => $error_result,
            ],
        ];
    }


    /**查询公司logo或学校logo
     * @param $param
     * @return array
     */
    public function get($param){
        $flag = is_array($param['ids']) ? true : false;
        $type = (int)$param['type'];
        $src_no = (int)$param['src_no'];
        $results = [];
        if($flag == false){
            $id = (int)$param['ids'];
            $results = $this->select($id,$type,$src_no);
        }else{
            foreach($param['ids'] as $k=>$id){
                $results[$id] = $this->select($id,$type,$src_no);
            }
        }
        return $results;
    }

    /** 缓存中获取不到需要取数据库的
     * @param $id integer 要获取的id   公司id或者学校id
     * @param $type integer  分类 公司还是学校
     * @param $src_no integer 来源 上传还是抓取
     * @return mixed|null|string|array
     */
    private function select($id,$type,$src_no){
        $cache_key = "logo_{$type}_{$id}_{$src_no}";
        $table = $type == 0 ? 'corporations_logos' : 'schools_logos';
        $wid = $type == 0 ? 'corporation_id' : 'school_id';

        $result = $this->parse_sql("select logo from $table where $wid = $id and is_deleted='N'");
        if(empty($result)){
            $cache_value = '';
            $this->log->warn("$id logo在库中不存在...");
        }else{

            /**
             *  src_no      type_id     src_no_value
             *  0           0           0   如果有 0 的值直接取
             *  0           1           0   如果没有 1 的值取 0 的值
             *  1           0           1   如果没有 0 的值取 1 的值
             *  1           1           1   如果有 1 的值直接取
             */
            foreach($result as $r){
                if($r['type_id'] == $src_no){  //  0 或 1 都有值的时候取
                    $cache_value = $r['logo'];
                    break;
                }
            }
            //当获取不到和src_no对应的值，那么就取隔壁的值
            if(empty($cache_value)){
                $cache_value = $result[0]['logo'];
            }
            $this->cache->memcached->save($cache_key, $cache_value);
        }
        $this->log->warn("$id 重新读库刷新缓存...");
        return $cache_value;
    }

    /**
     * 通过名字获取对应ID
     * @param $name  公司名  学校名
     * @param $type  0 为公司名  1 为学校名
     * @return int
     */
    private function get_id($name,$type){
        if(empty($name)) return 0;
        $function = $type == 0 ? 'corp_tag': 'cv_education_service_online';
        $result = $this->apis->$function($name);
        $this->log->warn("通过名字获取ID $name === $result ......");
        return $result;
    }


    /*
     * 更新公司或学校logo
     */
    public function update($params){
        switch($params['type']){
            case 0:
                $table  = 'corporations_logos';
                $wid    = 'corporation_id';
                break;
            case 1:
                $table  = 'schools_logos';
                $wid    = 'school_id';
                break;
            default:
                throw new Exception('type值不符合，请核实后重试', 100016);
        }

        $sql = "select id from {$table} where {$wid} = {$params['id']}  and is_deleted='N' limit 1";

        $res = $this->parse_sql($sql);
        if(count($res) < 1){
            $values = "(0,{$params['id']},'{$params['logo']}')";
            $sql = "insert into `{$table}` (`type_id`,`{$wid}`,`logo`) VALUES $values";
            $this->parse_sql($sql,false);
            $return =  'success insert';
        }else{
            $time = date('Y-m-d H:i:s',time());
            $update_sql = "update {$table} set logo='{$params['logo']}',updated_at='{$time}' where {$wid} = {$params['id']}";
            $this->parse_sql($update_sql,false);
            $return = 'success update';
        }

        // 清除缓存
        $cache_key_0 = "logo_{$params['type']}_{$params['id']}_0";
        $cache_key_1 = "logo_{$params['type']}_{$params['id']}_1";
        $cache_key_2 = "get_ascend_logo_{$params['id']}";
        $this->cache->memcached->del($cache_key_0);
        $this->cache->memcached->del($cache_key_1);
        $this->cache->memcached->del($cache_key_2);
        return $return;
    }

    /*
     * 获取追溯公司logo
     * @param int $id 公司ID
     */
    public function get_ascend_logo($id){
        if(empty($id)) return '';

        $cache_key = "get_ascend_logo_{$id}";
        if(empty($logo = $this->cache->memcached->get($cache_key))){
            // 如果该公司id获取到公司logo，则返回，否则根据父类向上追加获取logo
            $logo = $this->select($id,0,1);
            if(empty($logo)){
                $parent_ids = [];
                $this->load->model('model_corporation');
                $this->model_corporation->get_parent_ids($id, $parent_ids);

                foreach($parent_ids as $val){
                    $tmp = $this->select($val,0,1);
                    if(!empty($tmp)){
                        $logo = $tmp;
                        continue;
                    }
                }
            }
            $this->cache->memcached->save($cache_key,$logo,3600);
        }

        return $logo;
    }
}