<?php
/**
 * 调用其他服务所用
 */
class Apis {

    public $is_refresh = false;

    public $redis;

    public function __construct() {
    }

    public function &__get($key) {
        $CI =& get_instance();
        if($key=='cache' && !isset($CI->$key)){
            $this->load->driver('cache', NULL, 'cache');
        }
        return $CI->$key;
    }


    public function work_foreground($input, $host = 'default', $client_timeout_ms=50000000, $read_timeout_us=5000000000) {
        $worker_name = $input['w'];
        unset($input['w']);
        $this->load->library('Gearman_Client', '' ,'gc');
        $this->gc->gearman_client($host, $client_timeout_ms, $read_timeout_us);
        try {
            $header = $this->header();
            //增加对json的支持
            if (!empty($input['_w'])) {
                $response = $this->gc->do_job_foreground($worker_name, $input, $header,'json_encode','json_decode');
            } else {
                $response = $this->gc->do_job_foreground($worker_name, $input, $header);
            }
        } catch (Exception $e) {
            throw $e;
        }
        if(!empty($response['response']['err_no']) || !isset($response['response']['results'])) {
            throw new Exception(sprintf('调用Gearman %s 失败->err_no:%s err_msg:%s', $worker_name, $response['response']['err_no'], $response['response']['err_msg']), 85061004);
        }
        return $response['response']['results'];
    }

    /**
     * 请求外部接口头部信息
     * @param array $param
     * @return array
     */
    public function header($param=array()){
        switch (ENVIRONMENT){
            case 'dongqing':
            case 'development':
                $ip = '192.168.1.108';break;
            case 'testing':
            case 'testing2':
            case 'testing4':
                $ip = '10.9.10.6';break;
            case 'production':
                $ip = '192.168.8.13';break;
            default:
                $ip = '127.0.0.1';break;
        }
        return array(
            'product_name'=>isset($param['product_name']) ? $param['product_name'] : 'gsystem_service',
            'uid'=> isset($param['uid']) ? $param['uid'] : '9',
            'session_id'=> isset($param['session_id']) ? $param['session_id'] : '0',
            'uname'=>isset($param['uname']) ? $param['uname'] : 'gsystem_server',
            'version'=>isset($param['version']) ? $param['version'] : '0.1',
            'signid'=>isset($param['signid']) ? $param['signid'] : 0,
            'provider'=>isset($param['provider']) ? $param['provider'] : 'icdc',
            'ip'=>isset($param['ip']) ? $param['ip'] : '0.0.0.0',
            'user_ip'=>isset($param['user_ip']) ? $param['user_ip'] : $ip,
            'local_ip'=>isset($param['local_ip']) ? $param['local_ip'] : $ip,
            'log_id'=>isset($param['log_id']) ? $param['log_id'] : getmypid(),
            'appid'=>isset($param['appid']) ? $param['appid'] : 999,
        );
    }

    /** 公司识别接口
     * @param $name string 公司名称
     * @param bool $is_return_id
     * @return int
     * @throws Exception
     */
    public function corp_tag($name,$is_return_id=true){
        $input = array(
            'cv_id'=>'',
            'work_list' => [array(
                'position' => '',
                'company_name' => $name,
                'work_id' => '1',
                'desc' => '',
                'industry_name' => ''
            )]
        );
        try {
            $response = $this->client('corp_tag',$input);
        } catch (Exception $e) {
            throw $e;
        }

        if(!empty($response['status']) || !isset($response['result'])) {
            throw new Exception(sprintf('调用Gearman %s 失败->err_no:%s', 'corp_tag', $response['status']),85061004);
        }

        if($is_return_id){
            return (int)$response['result'][0]['company_id'];
        }else{
            return $response['result'];
        }
    }

    /** 教育识别接口
     * @param $name string 学校名称
     * @param bool $is_return_id
     * @return int
     * @throws Exception
     */
    public function cv_education_service_online($name,$is_return_id=true){
        $input = [
            'c' => 'CVEducation',
            'm' => 'query',
            'p' => [
                    [
                        'school'    => $name,
                        'major'     => '',
                        'degree'    => '',
                    ]
            ],
        ];
        try {
            $response = $this->client('cv_education_service_online',$input);
        } catch (Exception $e) {
            throw $e;
        }

        if(!empty($response['status']) || !isset($response['result'])) {
            throw new Exception(sprintf('调用Gearman %s 失败->err_no:%s', 'cv_education_service_online', $response['status']),85061004);
        }

        if($is_return_id){
            return (int)$response['result'][0]['school_id'];
        }else{
            return $response['result'];
        }
    }


    /** gearman 客户端
     * @param $work_name
     * @param $request array 请求参数
     * @param bool $request_json 是否采用json格式请求
     * @param bool $response_json 是否采用json格式接受  如果对方服务器返回的是json格式则采用此中方式
     * @param bool $is_async 是否采用异步方式
     * @return mixed
     */
    public function client($work_name,$request,$request_json=false,$response_json=false,$is_async=false){
        $gm_arr = json_decode(file_get_contents("/opt/wwwroot/conf/gm.conf"), TRUE);
        $client = new \GearmanClient();
        $client->setTimeout(50000);
        foreach($gm_arr[$work_name]['host'] as $host){
            $client->addServers($host);
        }
        $client->addServers($this->host);
        $param = array('header'=>$this->header(),'request'=>$request);
        $send_data = $request_json ? json_encode($param) : msgpack_pack($param);
        $return = $is_async ? $client->doBackground($work_name, $send_data) : $client->doNormal($work_name, $send_data);
        $ret = $response_json ? json_decode($return,true) : msgpack_unpack($return);
        return $ret['response'];
    }




    /**
     * @param $model
     * @return object
     */
    public function model($model)
    {
        $this->load->model($model);

        if (FALSE !== ($last_slash = strrpos($model, '/'))) {
            $model = substr($model, $last_slash + 1);
        }

        return $this->$model;
    }


    /*
     * 推送职位交通信息给职位抓取那边
     * @param string $traffic 交通信息
     * @param int $position_id 职位ID
     * @param int $flag_updated 标记更新 0不更新，1更新
     */
    public function update_address_by_pid($traffic, $position_id, $flag_updated = 1){
        $input = [
            'c' => 'positions/logic_position',
            'm' => 'update_address_by_pid',
            'p' => [
                'jd_address'    => json_encode($traffic, JSON_UNESCAPED_UNICODE),
                'flag_updated'  => $flag_updated,
                'position_id'   => $position_id,
            ],
        ];
        try {
            return $this->client('icdc_position_save',$input);
        } catch (Exception $e) {
            return $e;
        }
    }
}
