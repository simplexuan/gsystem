<?php 
class Gsystem_Worker extends CI_Worker{
    private $logs_id;
    private $log_start_time;
    function __construct(){
        parent:: __construct();
    }

    function __destruct() {
        //parent::__destruct();
    }

    public function logic($logic) {
        $this->load->logic($logic);

        if (($last_slash = strrpos($logic, '/')) !== FALSE)
        {
            $logic = substr($logic, $last_slash + 1);
        }

        return $this->$logic;
    }
    /**
     * 1、访问频率控制  后期
     * 2、白名单控制  后期
     * 3、规范控制  后期
     * @param $header
     * local_ip  uname m c p pid time status
     */
    public function iptables(&$input){
        $header = $input['header'];
        $this->logs_id = !empty($header['log_id']) ? uniqid($header['log_id']) : uniqid(getmypid());
        $this->log_start_time = number_format(microtime(true), 8, '.', '');
        $this->log_start_memory = memory_get_usage();



        $this->log($input,'input');

    }
    /**
     * dispatch 
     * 
     * @param array $input 
     * @param array $output 
     * @access public
     * @return mixed
     */
    public function dispatch($input=array(), &$output=array()) {
        $this->iptables($input);
        $this->benchmark->mark('worker_dispatch_start');
        $this->log->warn(sprintf('Logic:%s Method:%s start', $input['request']['c'], $input['request']['m']));

        if(is_object($input['request']['p'])) {
            $input['request']['p'] = (array)$input['request']['p'];
        }

        // 埋点
        $this->buriedPoint($input['request']['c'],$input['request']['m']);

        $this->log->info("[pid_".getmypid()."_input]:".json_encode($input,JSON_UNESCAPED_UNICODE));
        $method = $input['request']['m'];
        if(!empty($input['request']['o'])) {
            $input['request']['p']['select_field'] = $input['request']['o'];
        }
        try {
            $output = $this->logic($input['request']['c'])->$method($input['request']['p']);
            $this->log->info("\n");
        } catch(Exception $e) {
            $runtime = number_format(microtime(true), 8, '.', '') - $this->log_start_time;
            $this->log(array(
                'runtime'=>$runtime.'s',
                'req'=>number_format(1 / $runtime, 2).'req|s',
                'memory'=>number_format((memory_get_usage() - $this->log_start_memory) / 1024, 2).'kb',
                'usefile'=>count(get_included_files()),
                'status'=>0
            ),'time');
            $this->log("错误信息：".$e->getCode() .':'. $e->getMessage(),'error');
            $p = array(
                'subject'=>ENVIRONMENT . ' gsystem 发生错误',
                'body'=> date('Y-m-d H:i:s') . '<br /><br />参数信息：'.var_export($input, true) . '<br /><br />错误信息：'
                                .$e->getCode() .':'. $e->getMessage() . ' ' . $e->getTraceAsString(),
                );
            $this->notice->email($p);
            throw $e;
        }

        if(isset($input['request']['p']['page']) && isset($input['request']['p']['pagesize'])) {
            $output['page'] = $input['request']['p']['page'];
            $output['pagesize'] = $input['request']['p']['pagesize'];
        }

        $this->benchmark->mark('worker_dispatch_end');
        $elapsed_time= $this->benchmark->elapsed_time('worker_dispatch_start', 'worker_dispatch_end');
        $this->log->warn(sprintf('Logic:%s Method:%s runtime:%ss', $input['request']['c'], $input['request']['m'], $elapsed_time));

        $runtime = number_format(microtime(true), 8, '.', '') - $this->log_start_time;
        $this->log(array(
            'runtime'=>$runtime.'s',
            'req'=>number_format(1 / $runtime, 2).'req|s',
            'memory'=>number_format((memory_get_usage() - $this->log_start_memory) / 1024, 2)."kb",
            'usefile'=>count(get_included_files()),
            'status'=>1
        ),'time');
        $this->log($output,'output');
        return TRUE;
    }

    private function log($msg,$type){
        $destination = "/opt/log/gsystem_gearman.".date("Y-m-d");
        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if (!is_string($msg)) {
            $msg = json_encode($msg,JSON_UNESCAPED_UNICODE);
        }
        return error_log(date("Y-m-d H:i:s")."\t{$this->logs_id}\t$type\t$msg\r\n", 3,$destination);
    }

    /*
     * 埋点
     * @param string $c
     * @param string $m
     * @param string $p
     */
    private function buriedPoint($c, $m){
        $destination = '/opt/log/gsystem_buried_point_' . date('Ymd') . '.csv';
        if(!file_exists($destination)){
            error_log("request_date,request_time,controller,method\n", 3, $destination);
        }

        $str = date("Y-m-d") . ',' . date("H:i:s") . ",{$c},{$m}\r\n";
        $data = iconv('UTF-8','GBK',$str);
        return error_log($data, 3, $destination);
    }
}
