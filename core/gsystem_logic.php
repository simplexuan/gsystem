<?php

class Gsystem_logic extends CI_Logic {
    
    function __construct() {
        parent::__construct();
    }


    /**
     * model
     *
     * @param mixed $model
     * @access public
     * @return mixed
     */
    public function model($model){
        $this->load->model($model);

        if (($last_slash = strrpos($model, '/')) !== FALSE)
        {
            $model = substr($model, $last_slash + 1);
        }

        return $this->$model;
    }

    function work_background($input, $host = 'default', $client_timeout_ms=5000, $read_timeout_us=5000000) {
        $worker_name = $input['worker'];
//        $gearman_workers = $this->config->item('gearman_workers');
//        $host           = $gearman_workers[$worker_name]['host'];
        $this->load->library('Gearman_Client', '' ,'gc');
        $this->gc->gearman_client($host, $client_timeout_ms, $read_timeout_us);
        $header = array('uid'=>'1', 'uname'=>'zyh', 'version'=>1, 'signid'=>2132, 'provider'=>'gsystem', 'ip'=>'1232321');
        $this->gc->do_job_background($worker_name, $input, $header);
    }
    function work_foreground($input, $host = 'default', $client_timeout_ms=5000, $read_timeout_us=5000000) {
        $worker_name = $input['worker'];
//        $gearman_workers = $this->config->item('gearman_workers');
//        $host           = $gearman_workers[$worker_name]['host'];
        $this->load->library('Gearman_Client', '' ,'gc');
        $this->gc->gearman_client($host, $client_timeout_ms, $read_timeout_us);
        $header = array('uid'=>'1', 'uname'=>'zyh', 'version'=>1, 'signid'=>2132, 'provider'=>'gsystem', 'ip'=>'1232321');
        return $this->gc->do_job_foreground($worker_name, $input, $header);
    }
    /**
     * 获取多条记录，参数只有ids，可以是单个数字，也可以是逗号分割的数字，也可以是数组
     */
    function get_multi($param) {
        $return = array();

        if(empty($param['ids'])) {
            return $return;
        }

        $ids = is_array($param['ids']) ? $param['ids'] : explode(',', $param['ids']);

        //foreach($ids as $id) {
            //$return[$id] = $this->model(str_replace('Logic_', 'Model_', get_class($this)))->get_by_id($id);
        //}

        return $this->model(str_replace('Logic_', 'Model_', get_class($this)))->get_multi($ids);
        //return $return;
    }
}
