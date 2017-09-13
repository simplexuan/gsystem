<?php
class Schedules extends CI_Controller {
    function __construct(){
        $this->_log = LoggerManager::getLogger(__CLASS__);
        parent :: __construct();
    }
    public function index()
    {
        $cache_path = $this->config->item('cache_path');
        file_exists($cache_path) ? '' : mkdir($cache_path,0777,true);
        $this->uid[getmypid()] = '1';
        $this->user_info[getmypid()]['user_name'] = 'zyh';
        try{
            $this->load->library('cron_schedule');
            $this->cron_schedule->dispatch();
        }catch(Exception $e){
            var_dump($e);
        }
    }
}
