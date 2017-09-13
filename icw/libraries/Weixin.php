<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 *	微信公众平台消息接口
 *   http://mp.weixin.qq.com/wiki/index.php?title=%E9%A6%96%E9%A1%B5
 *
 *	@package	Weixin
 *	@subpackage Libraries
 *	@category	API
 *	@link
 */
class Weixin
{
    protected $_weixin_token = '';
    protected $_weixin_app_id = '';
    protected $_weixin_app_secret = '';

    protected $CI;
    public $error;

    public function __construct($config = array())
    {
        if ($config)
        {
            foreach ($config as $key => $val)
            {
                if(isset($this->{'_' . $key}))
                {
                    $this->{'_' . $key} = $val;
                }				
            }
        }
        $this->CI = &get_instance();

        //$this->valid();

        log_message('debug', "Weixin Class Initialized.");		
    }
    function initialize($config){
        if ($config)
        {
            foreach ($config as $key => $val)
            {
                if(isset($this->{'_' . $key}))
                {
                    $this->{'_' . $key} = $val;
                }				
            }
        }
    }

    /**
     * 接入是否生效
     *
     * @return void
     */
    public function valid()
    {
        // 随机字符串
        $echostr = $this->CI->input->get('echostr');

        if ($this->_check_signature())
        {
            echo $echostr;
        }
        else
        {
            log_message('error', 'check_signature fail.');
            exit;
        }
    }

    /**
     * 接收消息
     *
     * @return object 微信接口对象
     */
    public function msg()
    {

        if (!isset($GLOBALS["HTTP_RAW_POST_DATA"]) ){
            return FALSE;
        }

        $post = $GLOBALS["HTTP_RAW_POST_DATA"];
        //extract post data
        if ( ! $post)
        {
            return;
        }

        return simplexml_load_string($post, 'SimpleXMLElement', LIBXML_NOCDATA);
    }
    /**
     * ticket 
     * 
     * @param mixed $scene_id 
     * @param mixed $access_token 
     * @access public
     * @return mixed
     */
    public function ticket($scene_id, $access_token) {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=%s', $access_token);
        if (is_array($scene_id)) {
            $post_data = $scene_id;
        }else{
            $post_data = array(
                    "expire_seconds" => 1800, 
                    "action_name"    => 'QR_SCENE' , 
                    "action_info"    => array("scene"=>array("scene_id"=> $scene_id))
                    );
        }
        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 60,  
                    'content' => json_encode($post_data),
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return json_decode($content)->ticket;
    }
    /**
     * create_menu 
     * 
     * @param mixed $menu 
     * @param mixed $access_token 
     * @access public
     * @return mixed
     */
    public function create_menu($menu, $access_token) {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/menu/create?access_token=%s', $access_token);
        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 60,  
                    'content' => json_encode($menu, JSON_UNESCAPED_UNICODE),
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return json_decode($content)->errcode;
    }
    /**
     * upload 
     * 
     * 参数    是否必须    说明
     * access_token    是  调用接口凭证
     * type    是  媒体文件类型，分别有图片（image）、语音（voice）、视频（video）和缩略图（thumb）
     * media   是  form-data中媒体文件标识，有filename、filelength、content-type等信息
     * @param mixed $param 
     * @param mixed $access_token 
     * @access public
     * @return mixed
     */
    function upload($media, $type, $access_token) {
        $url = sprintf('http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=%s&type=%s', $access_token, $type);
        $content            = $media['content'];
        $filename           = $media['filename'];
        $content_type       = $media['content_type'];
        $boundary           = substr(md5(rand(0,32000)), 0, 10);

        $data .= "--$boundary\n";
        $data .= "Content-Disposition: form-data; name=\"file\"\n\n";
        $data .= "file\n";
        $data .= "--$boundary\n";
        $data .= "Content-Disposition: form-data; name=\"file\";filename=\"{$filename}\"\n";
        $data .= 'Content-Type:'."{$content_type}\n";    
        $data .= 'Content-Transfer-Encoding: binary'."\n\n";
        $data .= $content."\n";
        $data .= "--$boundary--\n";

        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 600,  
                    'header' =>"Content-Type: multipart/form-data; boundary={$boundary}", //头信息
                    'content'=> $data
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }

        return json_decode($content);
    }
    /**
     * download 
     * 
     * @param mixed $media_id 
     * @param mixed $access_token 
     * @access public
     * @return mixed
     */
    function download($media_id, $access_token){
        $url = sprintf('http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=%s&media_id=%s%s', $access_token, $media_id);
        $options = array(  
                'http'=>array(  
                    'method'  => "GET",  
                    'timeout' => 600,  
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return json_decode($content)->errcode;
    }
    function qrcode($ticket) {
        $url = sprintf('https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=%s', urlencode($ticket));
        $options = array(  
                'http'=>array(  
                    'method'  => "GET",  
                    'timeout' => 600,  
                    //'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content = file_get_contents($url, false, $context);
        return $content;
        //log_message('debug', $content);
        //$response = json_decode($content);
        //if (isset($response->errcode) && $response->errcode){
          //  throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
       // }
       // return json_decode($content)->errcode;
    }
    /**
     * upload_news  上传图文消息素材
     * 参考 http://mp.weixin.qq.com/wiki/index.php?title=%E9%AB%98%E7%BA%A7%E7%BE%A4%E5%8F%91%E6%8E%A5%E5%8F%A3
     * @param mixed $news 
     *  
     * @param mixed $access_token 
     * @access public
     * @return mixed
     */
    function upload_news($news, $access_token){
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token=%s', $access_token);
        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 60,  
                    'content' => json_encode($news, JSON_UNESCAPED_UNICODE),
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return json_decode($content)->errcode;
    }
    /**
     * sendall  根据分组进行群发
     * 
     * @param mixed $news 
     * @param mixed $access_token 
     * @access public
     * @return mixed
     */
    function sendall($news, $access_token){
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=%s', $access_token);
        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 60,  
                    'content' => json_encode($news, JSON_UNESCAPED_UNICODE),
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return json_decode($content)->errcode;
    }
    /**
     * mass_send 根据OpenID列表群发
     * 
     * @param mixed $news 
     * @param mixed $access_token 
     * @access public
     * @return mixed
     */
    function mass_send($news, $access_token){
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=%s', $access_token);
        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 60,  
                    'content' => json_encode($news, JSON_UNESCAPED_UNICODE),
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return json_decode($content)->errcode;
    }
    /**
     * access_menu 
     * 
     * @param mixed $access_token 
     * @access public
     * @return mixed
     */
    public function access_menu($access_token) {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/menu/get?access_token=%s', $access_token);
        $options = array(  
                'http'=>array(  
                    'method'  => "GET",  
                    'timeout' => 60,  
                    //'content' => json_encode($menu, JSON_UNESCAPED_UNICODE),
                    //'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return  $content;
    }
    /**
     * create_group 
     * 
     * @param mixed $access_token 
     * @param mixed $group 
     * @access public
     * @return mixed
     */
    public function create_group($access_token, $group) {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/groups/create?access_token=%s', $access_token);
        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 60,  
                    'content' => json_encode($group, JSON_UNESCAPED_UNICODE),
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return  $response;
    }
    public function update_member_group($access_token, $group) {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token==%s', $access_token);
        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 60,  
                    'content' => json_encode($group, JSON_UNESCAPED_UNICODE),
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return  $response;
    }

    public function get_user($access_token, $openid) {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/user/get?access_token=%s&openid=%s', $access_token, $openid);
        $options = array(  
                'http'=>array(  
                    'method'  => "GET",  
                    'timeout' => 60,  
                   // 'content' => json_encode($group, JSON_UNESCAPED_UNICODE),
                   // 'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return  $response;
    }
    /**
     * update_group 
     * 
     * @param mixed $access_token 
     * @param mixed $group 
     * @access public
     * @return mixed
     */
    public function update_group($access_token, $group) {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/groups/update?access_token=%s', $access_token);
        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 60,  
                    'content' => json_encode($group, JSON_UNESCAPED_UNICODE),
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return  $response;
    }
    public function get_group_by_id($access_token, $openid) {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/groups/getid?access_token=%s', $access_token);
        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 60,  
                    'content' => json_encode($openid, JSON_UNESCAPED_UNICODE),
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return  $response;
    }
    public function get_group($access_token) {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/groups/get?access_token=%s', $access_token);
        $options = array(  
                'http'=>array(  
                    'method'  => "GET",  
                    'timeout' => 60,  
                    //'content' => json_encode($group, JSON_UNESCAPED_UNICODE),
                    //'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return  $response;
    }
    /**
     * delete_menu 
     * 
     * @param mixed $access_token 
     * @access public
     * @return mixed
     */
    public function delete_menu($access_token) {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=%s', $access_token);
        $options = array(  
                'http'=>array(  
                    'method'  => "GET",  
                    'timeout' => 60,  
                    // 'content' => json_encode($menu, JSON_UNESCAPED_UNICODE),
                    //'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return json_decode($content)->errcode;
    }
    /**
     * access_token 
     * 
     * @access public
     * @return mixed
     */
    public function access_token() {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s', $this->_weixin_app_id, $this->_weixin_app_secret);

        $options = array(  
                'http'=>array(  
                    'method'  => "GET",  
                    'timeout' => 60,  
                    )  
                );  

        $context = stream_context_create($options); 
        $content  = file_get_contents($url, false, $context);
        log_message('debug', $content. $url . $this->_weixin_app_id. $this->_weixin_app_secret);
        $response = json_decode($content);
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return json_decode($content)->access_token;
    }
    /**
     *  发送消息
     * 
     * @param array $msg 消息
     */
    public function send($msg = array(), $token='')
    {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=%s', $token);
        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 60,  
                    'content' => json_encode($msg, JSON_UNESCAPED_UNICODE),
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context  = stream_context_create($options); 
        $content      = file_get_contents($url, false, $context);
        $response = json_decode($content);
        $this->error = $response->errmsg;
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return json_decode($content)->errcode;
    }
    public function send_template_message($msg = array(), $token='')
    {
        $url = sprintf('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=%s', $token);
        $options = array(  
                'http'=>array(  
                    'method'  => "POST",  
                    'timeout' => 60,  
                    'content' => json_encode($msg, JSON_UNESCAPED_UNICODE),
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    )  
                );  

        $context = stream_context_create($options); 
        $content = file_get_contents($url, false, $context);
        $response = json_decode($content);
        $this->error = json_decode($content)->errmsg;
        if (isset($response->errcode) && $response->errcode){
            throw new Exception (json_decode($content)->errmsg, json_decode($content)->errcode);
        }
        return json_decode($content)->errcode;
    }

    /**
     * 根据经纬度反译地理信息
     *
     * @param string $lat 纬度
     * @param string $lng 经度
     * @return array
     */
    public function geocode($lat, $lng, $language = 'en')
    {		
        $url = sprintf('http://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&sensor=false&language=' . $language, $lat, $lng);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
        $geo = curl_exec($ch);
        curl_close($ch);	

        $geo = json_decode($geo, TRUE);	

        // 不存在有效地理信息
        if ( ! isset($geo['results']))
        {
            return;
        }

        $output = array();

        foreach ($geo['results'][0]['address_components'] as $address)
        {
            $output[$address['types'][0]] = $address['long_name'];
        }

        return $output;
    }

    /**
     * 通过检验signature对网址接入合法性进行校验
     *
     * @return bool
     */
    private function _check_signature()
    {
        // 微信加密签名
        $signature = $this->CI->input->get('signature');
        // 时间戳
        $timestamp = $this->CI->input->get('timestamp');
        // 随机数
        $nonce = $this->CI->input->get('nonce');

        $tmp = array($this->_weixin_token, $timestamp, $nonce);
        sort($tmp, SORT_STRING);

        $str = sha1(implode($tmp));

        return $str == $signature ? TRUE : FALSE;
    }
}
