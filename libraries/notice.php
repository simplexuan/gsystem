<?php
/**
 * 通知类
 */
class Notice {

    private $func = 'jcsj_basic';

    public function email($params = array()) {
        try{
            if(empty($params['subject']) || empty($params['body'])) {
                return false;
            }

            //同样的标题，1分钟发一次
            // if(!$this->cache->memcached->add('icdc_sendmail_'.md5($params['subject']), 1, 60)) {
            //     return false;
            // }

            if(defined('ENVIRONMENT')) {
                $params['subject'] = strtoupper(ENVIRONMENT) . ' 环境 : ' . $params['subject'] . date('Y-m-d');
            } else {
                $params['subject'] = '未定义环境 : ' . $params['subject'] . date('Y-m-d');
            }

            $params = array(
                'w' => $this->func,
                'c'  =>'sendmail',
                'm'=>'create',
                'p'  => array(
                    'subject'       => $params['subject'],
                    'content'       => $params['body'],
                    'to_emails'     => implode(',', $this->config->item('notice_email')),
                    'cc_emails'     => empty($params['cc_emails']) ? '' : $params['cc_emails'],
                ),
            );

            $this->work_background($params);

        } catch(Exception $e) {
            //file_put_contents('/opt/log/send.log',var_export($mail->ErrorInfo,true));
        }
    }
    public function work_background($input, $host = 'default', $client_timeout_ms=500000, $read_timeout_us=5000000) {
        $worker_name = $input['w'];
        unset($input['w']);
        //$this->log->warn(sprintf('%s %s', __FUNCTION__, $worker_name));
        $this->load->library('Gearman_Client', '' ,'gc');
        $this->gc->gearman_client($host, $client_timeout_ms, $read_timeout_us);
        $this->gc->do_job_background($worker_name, $input, $this->request_header[getmypid()]);
    }
    public function __get($key) {
        $CI =& get_instance();
        if($key=='cache' && !isset($CI->$key)){ 
            $this->load->driver('cache', NULL, 'cache');
        }
        return $CI->$key;
    }
}
