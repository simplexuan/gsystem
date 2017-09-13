<?php

foreach ($argv as $k=>$v){
    if (preg_match('/config\/(\w+)\/gearman/', $v, $matches)){
        define('ENVIRONMENT', $matches[1]);
    }
}
setlocale(LC_ALL, "en_US.UTF-8");
//error_reporting(E_ALL);
ini_set('date.timezone','Asia/Shanghai');
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('EXT', '.php');
define('BASEPATH',__DIR__.'/icw/');
define('FCPATH',__DIR__);
define('SYSDIR', 'icw');
define('APPPATH', __DIR__.'/');
define('LOGPATH','/opt/log/');
define('GM_CONF', '/opt/wwwroot/conf/gm.conf');

require_once BASEPATH.'/Icw.php';
$manager = new CI_Gearman_Manager();
$manager->run();