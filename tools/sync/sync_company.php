<?php
/**
 * 
 * @package 
 * @version $id$
 * @copyright Copyright (c) 2012-2013 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@51chance.com.cn> 
 * @license 
 */

set_time_limit(0);
error_reporting(E_ALL);
ini_set('memory_limit','4096M');
class sync_company {
    protected $_log;
    protected $dbh;
    protected $type = array(4=>'jd_functions_industry',5=>'jd_zhaopin',6=>'jd_chinahr',7=>'jd_lietou',8=>'jd_800hr',9=>'jd_indeed',10=>'jd_linkedin');
    protected $sort = array('sync_51job', 'sync_zhilian', 'sync_liepin');
    /**
     * model 
     * 
     * @param mixed $model 
     * @access public
     * @return mixed
     */
    public function model($model) {
        $this->load->model($model);
        if (($last_slash = strrpos($model, '/')) !== FALSE)
        {
            $model = substr($model, $last_slash + 1);
        }
        return $this->$model;
    }
    /**
     * __construct
     *
     * @param string $config
     * @param string $output_dir
     * @access protected
     * @return mixed
     */
    function __construct($config="", $output_dir="", $db="") {
        $this->_log = LoggerManager::getLogger(__CLASS__);
         $this->load->database('', FALSE, TRUE);
         $this->db->conn_id->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         $this->db->conn_id->query('set names utf8');
         $this->db->save_queries = FALSE;

         $this->dbh = $this->load->database('spider_jd', TRUE, TRUE);
         $this->dbh->conn_id->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         $this->dbh->conn_id->query('set names utf8');
         $this->dbh->save_queries = FALSE;
         //$dsn1 = 'mysql:dbname=city;host=localhost';
         //$user1 = 'root';
         //$password1 = '';
         //$dbh1 = new PDO($dsn1, $user1, $password1);
         //$dbh1->query('set names utf8');
         //$dbh1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    function start() {
        foreach($this->sort as $sort) {
            $this->{$sort}();
        }
    }
    /**
     * 导入猎聘
     */
    function sync_liepin() {

        $type_id = 7;
        $num = 100;

        $this->dbh->select_max('id');
        $rs = $this->dbh->get($this->type[$type_id])->row_array();
        $max_id = $rs['id'];

        if(empty($max_id)) {
            return;
        }

        $rs = $this->model('Model_sync_spider')->get_by_table_name_first($this->type[$type_id], array('select'=>'*'));
        if(empty($rs)) {
            $id = $this->model('Model_sync_spider')->save(array('sync_spider'=>array('table_name'=>
                                    $this->type[$type_id],'table_id'=>0,'is_deleted'=>'N','updated_at'=>date('Y-m-d H:i:s'))));
            $min_id = 0;
        } else {
            $id = $rs['id'];
            $min_id = $rs['table_id'];
        }

        if($max_id > $min_id) {
            $this->model('Model_sync_spider')->update_by_id(array('table_id'=>$max_id, 'updated_at'=>date('Y-m-d H:i:s')), $id);
        }

        for($i=$min_id;$i<$max_id;$i=$i+$num) {
            
            $rs = $this->dbh->where_in('id',range($i+1, $i+$num))->get($this->type[$type_id])->result_array();

            foreach($rs as $row) {

                $this->log->info(sprintf(' %s:%d ok.', __FILE__, __LINE__));

                try {
                    $content = $this->trim_array(unserialize($row['content']));

                    //公司信息
                    $data = array(
                        //'website'   => empty($content['wangzhan']) ? $content['company_url'] : $content['wangzhan'],
                        'name'  => $content['company'],
                        //'cityname'  => $content['didian'], //工作地点，不是公司所在地
                        'nature_name'   => $content['xingzhi'],
                        'size_name' => $content['guimo'],
                        'uid'   => 1,
                    ); 
                    $corporation_id = $this->model('Model_corporation')->gen_id_by_unique($data);
                    
                    //公司描述
                    if(!empty($content['qiyejieshao'])) $content['qiyejieshao'] = $this->trim_html($content['qiyejieshao']);
                    if(!empty($content['qiyejieshao'])) {
                        $info = $this->model('Model_corporation_description')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                        if(empty($info) || $info['description'] != $content['qiyejieshao']) {
                            $save_data = array(
                                'corporation_id'    => $corporation_id,
                                'type_id'   => $type_id,
                                'sort'  => 0,
                                'intro' => '',
                                'notes' => '',
                                'description'  => $content['qiyejieshao'],
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
                    if(!empty($content['hangye'])) {
                        $info = $this->model('Model_corporation_industry')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                        if(empty($info) || $info['industry_name'] != $content['hangye']) {
                            $save_data = array(
                                'type_id'   => $type_id,
                                'corporation_id'    => $corporation_id,
                                'sort'  => 0,
                                'industry_id'   => 0,
                                'industry_name' => $content['hangye'],
                                'is_deleted'    => 'N',
                                'updated_at'    => date('Y-m-d H:i:s'),
                            );
                            if(!empty($info)) {
                                $save_data['id'] = $info['id'];
                            }
                            $this->model('Model_corporation_industry')->save(array('corporation_industry'=> $save_data));
                        }
                    }
                    //公司来源表 corporations_sources
                    $this->model('Model_corporation_source')->gen_id_by_unique(array('corporation_id'=>$corporation_id,'type_id'=>$type_id));
                } catch (Exception $e) {
                    $this->log->warn(sprintf('Exception %s:%s %s', $e->getFile(), $e->getLine(), $e->getMessage()));
                    $this->model('Model_sync_spider')->update_by_id(array('table_id'=>$row['id']-1, 'updated_at'=>date('Y-m-d H:i:s')), $id);
                    throw new Exception($e->getMessage());
                }
            }
        }
    }
    /**
     * 导入智联
     */
    function sync_zhilian() {

        $type_id = 5;
        $num = 100;

        $this->dbh->select_max('id');
        $rs = $this->dbh->get($this->type[$type_id])->row_array();
        $max_id = $rs['id'];

        if(empty($max_id)) {
            return;
        }

        $rs = $this->model('Model_sync_spider')->get_by_table_name_first($this->type[$type_id], array('select'=>'*'));
        if(empty($rs)) {
            $id = $this->model('Model_sync_spider')->save(array('sync_spider'=>array('table_name'=>
                                    $this->type[$type_id],'table_id'=>0,'is_deleted'=>'N','updated_at'=>date('Y-m-d H:i:s'))));
            $min_id = 0;
        } else {
            $id = $rs['id'];
            $min_id = $rs['table_id'];
        }

        if($max_id > $min_id) {
            $this->model('Model_sync_spider')->update_by_id(array('table_id'=>$max_id, 'updated_at'=>date('Y-m-d H:i:s')), $id);
        }

        for($i=$min_id;$i<$max_id;$i=$i+$num) {
            
            $rs = $this->dbh->where_in('id',range($i+1, $i+$num))->get($this->type[$type_id])->result_array();

            foreach($rs as $row) {

                $this->log->info(sprintf(' %s:%d ok.', __FILE__, __LINE__));

                try {
                    $content = $this->trim_array(unserialize($row['content']));

                    //公司信息
                    //if(!empty($content['didian'])) {
                        //$content['didian'] = trim(str_replace('正在加载更多城市', '', $content['didian']));
                    //} else {
                        //$content['didian'] = '';
                    //}
                    if(!empty($content['wangzhan']) && $content['wangzhan'] == 'null') {
                        $content['wangzhan'] = '';
                    }
                    $data = array(
                        'website'   => empty($content['wangzhan']) ? $content['company_url'] : $content['wangzhan'],
                        'name'  => $content['company'],
                        //'cityname'  => $content['didian'], //这是工作地点，不是公司所在城市
                        'nature_name'   => $content['xingzhi'],
                        'size_name' => $content['guimo'],
                        'uid'   => 1,
                    ); 
                    $corporation_id = $this->model('Model_corporation')->gen_id_by_unique($data);
                    
                    //公司描述
                    if(!empty($content['jianjie'])) $content['jianjie'] = $this->trim_html($content['jianjie']);
                    if(!empty($content['jianjie'])) {
                        $info = $this->model('Model_corporation_description')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                        if(empty($info) || $info['description'] != $content['jianjie']) {
                            $save_data = array(
                                'corporation_id'    => $corporation_id,
                                'type_id'   => $type_id,
                                'sort'  => 0,
                                'intro' => '',
                                'notes' => '',
                                'description'  => $content['jianjie'],
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
                    if(!empty($content['hangye'])) {
                        $info = $this->model('Model_corporation_industry')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                        if(empty($info) || $info['industry_name'] != $content['hangye']) {
                            $save_data = array(
                                'type_id'   => $type_id,
                                'corporation_id'    => $corporation_id,
                                'sort'  => 0,
                                'industry_id'   => 0,
                                'industry_name' => $content['hangye'],
                                'is_deleted'    => 'N',
                                'updated_at'    => date('Y-m-d H:i:s'),
                            );
                            if(!empty($info)) {
                                $save_data['id'] = $info['id'];
                            }
                            $this->model('Model_corporation_industry')->save(array('corporation_industry'=>$save_data));
                        }
                    }
                    //地址
                    if(!empty($content['dizhi'])) {
                        $info = $this->model('Model_corporation_address')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                        if(empty($info) || $info['address'] != $content['dizhi']) {
                            $save_data = array(
                                'type_id' => $type_id,
                                'corporation_id'    => $corporation_id,
                                'sort'  => 0,
                                'address'   => $content['dizhi'],
                                'is_deleted'    => 'N',
                                'updated_at'    => date('Y-m-d H:i:s'),
                            );
                            if(!empty($info)) {
                                $save_data['id'] = $info['id'];
                            }
                            $this->model('Model_corporation_address')->save(array('corporation_address' =>$save_data));
                        }
                    }
                    //别名
                    if(!empty($content['company2']) && $content['company2'] != $content['company']) {
                        $content['company2'] = str_replace(array(' ','　'), '', $content['company2']);
                        $info = $this->model('Model_corporation_alias')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                        if(empty($info) || $info['alias'] != $content['company2']) {
                            $save_data = array(
                                'type_id' => $type_id,
                                'corporation_id'    => $corporation_id,
                                'sort'  => 0,
                                'alias'   => $content['company2'],
                                'is_deleted'    => 'N',
                                'updated_at'    => date('Y-m-d H:i:s'),
                            );
                            if(!empty($info)) {
                                $save_data['id'] = $info['id'];
                            }
                            $this->model('Model_corporation_alias')->save(array('corporation_alias'=>$save_data));
                        }
                    }
                    //公司来源表 corporations_sources
                    $this->model('Model_corporation_source')->gen_id_by_unique(array('corporation_id'=>$corporation_id,'type_id'=>$type_id));
                } catch (Exception $e) {
                    $this->log->warn(sprintf('Exception %s:%s %s', $e->getFile(), $e->getLine(), $e->getMessage()));
                    $this->model('Model_sync_spider')->update_by_id(array('table_id'=>$row['id']-1, 'updated_at'=>date('Y-m-d H:i:s')), $id);
                    throw new Exception($e->getMessage());
                }
            }
        }
    }
    /**
     * 导入51job
     */
    function sync_51job() {

        $type_id = 4;
        $num = 100;

        $this->dbh->select_max('id');
        $rs = $this->dbh->get($this->type[$type_id])->row_array();
        $max_id = $rs['id'];

        if(empty($max_id)) {
            return;
        }

        $rs = $this->model('Model_sync_spider')->get_by_table_name_first($this->type[$type_id], array('select'=>'*'));
        if(empty($rs)) {
            $id = $this->model('Model_sync_spider')->save(array('sync_spider'=>array('table_name'=>
                                    $this->type[$type_id],'table_id'=>0,'is_deleted'=>'N','updated_at'=>date('Y-m-d H:i:s'))));
            $min_id = 0;
        } else {
            $id = $rs['id'];
            $min_id = $rs['table_id'];
        }

        if($max_id > $min_id) {
            $this->model('Model_sync_spider')->update_by_id(array('table_id'=>$max_id, 'updated_at'=>date('Y-m-d H:i:s')), $id);
        }

        for($i=$min_id;$i<$max_id;$i=$i+$num) {
            
            $rs = $this->dbh->where_in('id',range($i+1, $i+$num))->get($this->type[$type_id])->result_array();

            foreach($rs as $row) {

                $this->log->info(sprintf(' %s:%d ok.', __FILE__, __LINE__));

                try {
                    $content = $this->trim_array(unserialize($row['content']));

                    //公司信息
                    $data = array(
                        'website'   => empty($content['wangzhan']) ? $content['company_url'] : $content['wangzhan'],
                        'name'  => $content['company'],
                        //'cityname'  => empty($content['didian']) ? '' : $content['didian'], //工作地点，不是公司所在地
                        'nature_name'   => $content['xingzhi'],
                        'size_name' => $content['guimo'],
                        'uid'   => 1,
                    ); 
                    $corporation_id = $this->model('Model_corporation')->gen_id_by_unique($data);
                    if(!empty($content['jianjie'])) $content['jianjie'] = $this->trim_html($content['jianjie']);
                    if(!empty($content['jianjie'])) {
                        $info = $this->model('Model_corporation_description')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                        if(empty($info) || $info['description'] != $content['jianjie']) {
                            //公司描述
                            $save_data = array(
                                'corporation_id'    => $corporation_id,
                                'type_id'   => $type_id,
                                'sort'  => 0,
                                'intro' => '',
                                'notes' => '',
                                'description'  => $content['jianjie'],
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
                    if(!empty($content['hangye'])) {
                        $info = $this->model('Model_corporation_industry')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                        if(empty($info) || $info['industry_name'] != $content['hangye']) {
                            $save_data = array(
                                'type_id'   => $type_id,
                                'corporation_id'    => $corporation_id,
                                'sort'  => 0,
                                'industry_id'   => 0,
                                'industry_name' => $content['hangye'],
                                'is_deleted'    => 'N',
                                'updated_at'    => date('Y-m-d H:i:s'),
                            );
                            if(!empty($info)) {
                                $save_data['id'] = $info['id'];
                            }
                            $this->model('Model_corporation_industry')->save(array('corporation_industry'=>$save_data));
                        }
                    }
                    if(!empty($content['dizhi'])) {
                        $info = $this->model('Model_corporation_address')->search_one(array('corporation_id'=>$corporation_id, 'type_id'=>$type_id));
                        if(empty($info) || $info['address'] != $content['dizhi']) {
                            $save_data = array(
                                'type_id' => $type_id,
                                'corporation_id'    => $corporation_id,
                                'sort'  => 0,
                                'address'   => $content['dizhi'],
                                'is_deleted'    => 'N',
                                'updated_at'    => date('Y-m-d H:i:s'),
                            );
                            if(!empty($info)) {
                                $save_data['id'] = $info['id'];
                            }
                            $this->model('Model_corporation_address')->save(array('corporation_address'=> $save_data));
                        }
                    }
                    //公司来源表 corporations_sources
                    $this->model('Model_corporation_source')->gen_id_by_unique(array('corporation_id'=>$corporation_id,'type_id'=>$type_id));
                } catch (Exception $e) {
                    $this->log->warn(sprintf('Exception %s:%s %s', $e->getFile(), $e->getLine(), $e->getMessage()));
                    $this->model('Model_sync_spider')->update_by_id(array('table_id'=>$row['id']-1, 'updated_at'=>date('Y-m-d H:i:s')), $id);
                    throw new Exception($e->getMessage());
                }
            }
        }
    }
    /**
     * 导入800hr
     */
    function sync_800hr() {

        $type_id = 8;

        $this->dbh->select_max('id');
        $rs = $this->dbh->get($this->type[$type_id])->row_array();
        $max_id = $rs['id'];

        if(empty($max_id)) {
            return;
        }

        $rs = $this->model('Model_sync_spider')->get_by_table_name_first($this->type[$type_id], array('select'=>'*'));
        if(empty($rs)) {
            $id = $this->model('Model_sync_spider')->save(array('sync_spider'=>array('table_name'=>
                                    $this->type[$type_id],'table_id'=>0,'is_deleted'=>'N','updated_at'=>date('Y-m-d H:i:s'))));
            $min_id = 0;
        } else {
            $id = $rs['id'];
            $min_id = $rs['table_id'];
        }

        $this->model('Model_sync_spider')->update_by_id(array('table_id'=>$max_id, 'updated_at'=>date('Y-m-d H:i:s')), $id);

        for($i=$min_id;$i<$max_id;$i++) {
            
            $rs = $this->dbh->get_where($this->type[$type_id], array('id'=>$i+1))->row_array();
            if(empty($rs)) continue;

            $content = unserialize($rs['content']);
            //公司信息
            $data = array(
                'name'  => $content['company'],
                'cityname'  => $content['didian'],
                'nature_name'   => $content['xingzhi'],
                'size_name' => $content['guimo'],
                'uid'   => 1,
            ); 
            $corporation_id = $this->model('Model_corporation')->gen_id_by_unique($data);
            if(!empty($content['jieshao'])) {
                //公司描述
                $data = array(
                    'corporation_id'    => $corporation_id,
                    'type_id'   => $type_id,
                    'intro' => $content['jieshao'],
                );
                $this->model('Model_corporation_description')->gen_id_by_unique($data);
            }
            //公司联系人 TODO


            //公司来源表 corporations_sources TODO

        }

    }
    function trim_array($data) {
        if(is_string($data)) {
            return trim(trim($data));
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
    function __get($key){
        $CI =& get_instance();
        return $CI->$key;
    }
}
