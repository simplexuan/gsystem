<?php 
/**
 * 远程服务报警  
 */
if (! function_exists('remote_alarm')) {
    function remote_alarm($s) {
        $CI =& get_instance();
        $content = file_get_contents(str_replace('gm.conf', 'monitor.conf', GM_CONF));
        list($host, $port) = explode(":", trim($content));
        $alarm = $CI ->config->item('alarm');
        $alarm['msg'] = $s;
        $message = json_encode($alarm)."\n";
        $message = strtr($message, array('[ \b'=>'[', ' \b]'=>']', '('=>'{', ')'=>'}'));
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if($sock){
            socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => 50000));
            $len = strlen($message);
            socket_sendto($sock, $message, $len, 0, $host, $port);
            socket_close($sock);
        }
    }

}
