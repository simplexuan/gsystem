<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * gearman的下一层
 */
class CI_Worker {
    //public $response_header = array();
    //public $response_body   = array();
    //public $request_body    = array();
    //public $request_header  = array();
    private $CI;
    private $msgpack;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct(){
        L("Worker Class Initialized",6);
        $this->CI =& get_instance();
    }

    /**
     * __get
     *
     * Allows models to access CI's loaded classes using the same
     * syntax as controllers.
     *
     * @param	string
     * @access private
     */
    function &__get($key){
        return $this->CI->$key;
    }

    /**
     * run 
     * 
     * @param mixed $job 
     * @param mixed $log 
     * @access public
     * @return mixed
     */
    final function run($job, &$log) {
        $this->msgpack = false;
        $my_log[] = date('Y-m-d H:i:s');
        $my_log[] = $job->functionName();
        $start_runtime = number_format(microtime(true), 8, '.', '');
        if($this->config->item('mode', 'mgr_config') == 'cli') {
            $input = $job;
            $workloadSize=0;
			$input['header']['unique'] = uniqid('icw_');
        }else {
            $workload = $job->workload();
            $workloadSize= $job->workloadSize();

            $input = json_decode($workload,true);
            if(!is_array($input)){
                $this->msgpack = true;
                $input = msgpack_unpack($workload);
            }

            //$input  = msgpack_unpack($workload);
		//	$input['header']['unique'] = $job->unique();
        }



            


        L(sprintf('input:%s', var_export($input, TRUE)),6);
        $my_log[] = empty($input['request']['c']) ? '' : $input['request']['c'];
        $my_log[] = empty($input['request']['m']) ? '' : $input['request']['m'];
        $pid = getmypid();
        $this->CI->response_body[$pid]   = array('err_no'=>0, 'err_msg'=>'', 'results'=>'');
        $this->CI->response_header[$pid] = $input['header'];
        $this->load->helper('common');
        $this->CI->request_body[$pid]   = $input['request'];
        $this->CI->request_header[$pid] = $input['header'];
        $output    = array();
        $res       = TRUE;
        $log       = array();
 
        if (method_exists($this, 'dispatch')){
            try{
                //验证token
             //   $this->_validate_token();
                if (method_exists($this,'pre_worker')){
                    $this->pre_worker();
                }
                
                $local_ip = getHostByName(getHostName());
                // $this->log->add_basic_info(
                //     array_merge($this->CI->request_header[$pid], 
                //         array(
                //         'worker'=>get_class($this), 
                //         'receive_time' => time(),
                //         'local_ip' => $local_ip,
                //         'client_ip' => isset($this->CI->request_header[$pid]['local_ip']) ? $this->CI->request_header[$pid]['local_ip'] : ''
                //         )
                //     )
                // );


                if(empty($this->CI->request_header[$pid]['log_id'])){
                    $this->load->helper('common');
                    $this->CI->request_header[$pid]['log_id'] = gen_sign_id();
                    $this->CI->request_header[$pid]['local_ip'] = $local_ip;
                }

                // $this->log->add_basic_info(array('request_api' => get_class($this) . '/' . $input['request']['c'] . '/' . $input['request']['m']));
				//$this->log->push_info('(c:%s) (m:%s) ', array($input['request']['c'], $input['request']['m']));
                if (isset($this->CI->db)) $this->CI->db->reset();
                // $this->benchmark->mark('worker_dispatch_start');
                if (method_exists($this,'fetch_user_by_uid')){
                    $this->fetch_user_by_uid($input);
                }

                $res = $this->dispatch($input, $output);
                if (method_exists($this,'post_worker')){
                    $this->post_worker();
                }
                // $this->benchmark->mark('worker_dispatch_end');
                $this->CI->response_body[$pid]['results'] = $output;
				$elapsed_time= $this->benchmark->elapsed_time('worker_dispatch_start', 'worker_dispatch_end');
                // $this->log->add_basic_info(array('cost' => $elapsed_time));
				//$this->log->push_info('cost_all:%ss',    array($elapsed_time));
				//$this->log->push_info('request:%uB',  array($workloadSize));
				//$this->log->push_info('response:%uB', array(strlen(msgpack_pack($output))));
                // $this->log->info(sprintf(' %s:%d ok.', __FILE__, __LINE__));
            }catch(PDOException $e){
                //数据库断开连接 进程退出
				if($e->getCode()=='HY000'){
                    L(sprintf("%s", $e->getMessage()),2);
					$this->CI->db->close();
					unset($this->CI->db);
					$this->load->database('', FALSE, TRUE);
					$this->CI->response_body[$pid]['err_no']  =  5000005;
				}else{
					$this->CI->response_body[$pid]['err_no']  = $e->getCode();
				}
                $this->CI->response_body[$pid]['err_msg'] =  $e->getMessage();
                $this->CI->response_body[$pid]['results'] = $output;
                L(sprintf('%s:%d  failure. err_no:%s err_msg:%s', $e->getFile(), $e->getLine(),$e->getCode(), $e->getMessage()),5);
            }catch(Exception $e){
                $this->CI->response_body[$pid]['err_no']  = $e->getCode();
                $this->CI->response_body[$pid]['err_msg'] =  $e->getMessage();
                $this->CI->response_body[$pid]['results'] = $output;
                L(sprintf('%s:%d  failure. err_no:%d err_msg:%s', $e->getFile(), $e->getLine(),$e->getCode(), $e->getMessage()),5);
            }
        }else{
            /*var_dump($request);*/

        }
        unset($this->request);
        unset($workload);
		if (isset($input['header']['mold']) && $input['header']['mold'] == 'js'  
				&& is_array($this->CI->response_body[$pid]['results'])) {
			$this->CI->response_body[$pid]['results'] = array_values($this->CI->response_body[$pid]['results']); 
		}
        $response = $this->_response($this->CI->response_body[$pid], $this->CI->response_header[$pid]);
        $this->load->cleanup();
        $my_log[] = number_format(microtime(true), 8, '.', '') - $start_runtime;
        $msg = implode("\t",$my_log);
        error_log($msg."\r\n",3,"/opt/log/gsystem_access.".date('Y-m-d'));
        return $response;
    }
    /**
     * _response 
     * 
     * @access protected
     * @return mixed
     */
    protected function _response($response_body, $response_header){
        $func = $this->msgpack ? "msgpack_pack" : "json_encode";
        return $func(array('response'=>$response_body, 'header'=>$response_header));
    }

	function err_msg($err_no, $err_msg='') {
		return  $this->config->item($err_no, 'err_msg');
	}
}
// END Worker Class

/* End of file Worker.php */
/* Location: ./CodeIgniter/core/Worker.php */
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
