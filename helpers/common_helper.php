<?php 
/**
 * parse_selected 
 * 
 * @param mixed $str
 * @access public
 * @return mixed
 */

function parse_selected($str) {
    if (!is_string($str)) return array();
    $selected =array();
    if(!preg_match_all('/([a-zA-Z0-9_]+)(#\d+)?(\{(.*?)?\})?,?/mi',$str, $matches)){
        return $selected;
    }
    foreach ($matches[1] as $k=>$v){
        if ('' === $matches[3][$k]) {
            $selected[$v] = '';
            continue;
        }
        $item = trim(substr($matches[3][$k], 1, -1));
        if ('' === $item) {
            continue;
        }
        $selected[$v] = $item;
    }
    return $selected;
}
function parse_grandson_selected($s){
    if (!is_string($s)) return '';
    preg_match_all('/(([a-z_0-9]+)(\[\d*\])\((.*?)\)|([a-z_0-9]+)(\[\d*\])|([a-z_0-9]+))/mi', $s, $matches);
    $key =  $matches[2][0]  ?  $matches[2][0] :  $matches[5][0];
    return $key ? $key : $matches[1][0];
}
function parse_sub_selected($s){
    if (!is_string($s)) return array();
    if(!preg_match_all('/(([a-zA-Z0-9_]+(\[\d+?\])\([a-z_A-Z0-9,]+\))|[a-zA-Z0-9_]+(\[\d+?\])|[a-zA-Z0-9_]+)/mi', $s, $matches)){
        return array();
    }
    return $matches[0];
}
/**
 * @brief 将数字转化为IP
 *
 * @param $num2ip    *
 * @return 
 */
function num2ip($num) {   
    $tmp = (double)$num;
    return sprintf('%u.%u.%u.%u', $tmp & 0xFF, (($tmp >> 8) & 0xFF),
            (($tmp >> 16) & 0xFF), (($tmp >> 24) & 0xFF));
}

/**
 * @brief 将Ip地址转化为整数
 *
 * @param $ip
 *
 * @return 
 */
function ip2num($ip) {
    $n = ip2long($ip);

    /** convert to network order */
    $n =       (($n & 0xFF) << 24)
        | ((($n >> 8) & 0xFF) << 16)
        | ((($n >> 16) & 0xFF) << 8)
        | (($n >> 24) & 0xFF);
    return  $n;
}
/**
 * @brief request unique id，保证一段时间内没有重复的id
 *
 * @return 
 */
function gen_sign_id(){
    $arr = gettimeofday();
    return sprintf('%u',((($arr['sec']*100000 + $arr['usec']/10) & 0x7FFFFFFF) |
                0x80000000));
} 
/**
 * @brief get_ip 
 *
 * @return 
 */
function get_local_ip(){
    $pattern = '/(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,3}|2[0-4]\d|25[0-5])/';
    exec("/sbin/ifconfig", $out, $stats);
    $ips = array();
    if(!empty($out))
    {
        foreach ($out as $v){
            if(preg_match_all($pattern, $v, $matches)){
                return $matches[0][0];
            }
        }
    }
    return '127.0.0.1';

}
function gen_rand_num ($intMinNum=4, $intMaxNum=4) {
    $digits = array('0','1','2','3','4','5','6','7','8','9');
    shuffle($digits);
	$arr = gettimeofday ();
	srand($arr['sec'] + $arr['usec']);
	$intLen = rand($intMinNum,$intMaxNum);
   return  implode('', array_rand($digits, $intLen));
}
function gen_rand_str ($intMinNum =8 , $intMaxNum = 15) {
    $arr = gettimeofday ();
    srand($arr['sec'] + $arr['usec']);
    $intLen = rand($intMinNum,$intMaxNum);
    $str = md5(md5(rand()));
    $intStart = rand(0,$intMaxNum-$intLen);
    return substr($str,$intStart,$intLen);
}
function my_password_hash($password, $salt){
	return md5(md5($password) .$salt);
}
function my_password_verify($password, $hash, $salt){
	return my_password_hash($password, $salt) == $hash;
}

/**
 * 向URL发送请求
 * @param String $url | 请求的地址URL
 * @param mixed $data | 请求传参
 * @param int $http_header 额外的HTTP头设置
 * @return mixed
 */
function send_curl($url = null, $method = 'GET', $data = [] , $http_header = [])
{
    if (empty($url)) return ['status' => 5, 'data' => [], 'msg' => 'url为空'];

    //初始化curl
    $ch = curl_init();

    //设置参数
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if (!empty($http_header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }

    //post数据
    if($method == 'POST'){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100); //设置超时时间
    $response = curl_exec($ch); //接收返回信息

    if (curl_errno($ch)) {//出错则显示错误信息
        return curl_error($ch);
    }

    curl_close($ch); //关闭curl链接

    return json_decode($response, true); //显示返回信息
}
