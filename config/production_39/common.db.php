<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$active_record = TRUE;

// 默认连接库
$active_group = 'gsystem';

// 默认数据库设置
$eh_basedb = array(
    // 'hostname' => 'mysql:host=192.168.8.111;port=3307',
    //'hostname' => 'mysql:host=192.168.8.102;port=3306',
	'hostname' => 'mysql:host=192.168.8.101;port=3307',
    'username' => 'jcsj_gs',
    'password' => '0!TaaBsc7WQ!AfH',
    'database' => 'gsystem',
    'dbdriver' => 'pdo',
    //'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => FALSE,
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'autoinit' => TRUE,
    'stricton' => FALSE
);

// tobusiness交通库
$tobusiness_traffic = [
    'hostname' => 'mysql:host=192.168.8.101;port=3307',
    'username' => 'jcsj_gs',
    'password' => '0!TaaBsc7WQ!AfH',
    'database' => 'tobusiness_traffic',
    'dbdriver' => 'pdo',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => FALSE,
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'autoinit' => TRUE,
    'stricton' => FALSE
];

// gsystem交通库
$gsystem_traffic = [
    'hostname' => 'mysql:host=192.168.8.101;port=3307',
    'username' => 'jcsj_gs',
    'password' => '0!TaaBsc7WQ!AfH',
    'database' => 'gsystem_traffic',
    'dbdriver' => 'pdo',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => FALSE,
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'autoinit' => TRUE,
    'stricton' => FALSE
];

/** 
 * 不分库
 */
$db['gsystem'] = $eh_basedb;
$db['tobusiness_traffic'] = $tobusiness_traffic;
$db['gsystem_traffic'] = $gsystem_traffic;


/**
 * 获取库名
 *
 *	@string	$dbname		// 数据库名
 *	@int	$dbcount	// 分库数
 *	@string	$hash_key	// hash_key
 *	@return string		// 返回数据库名称
 *
 */
if(! function_exists('get_base_db_common')) {
    function get_base_db_common($dbname, $dbcount, $hash_key)
    {
    	if ($dbcount <= 1 && empty($hash_key)) {
    		return $dbname;
    	}
    
    	// 拆库没有上限，分表后缀是十六进制，会补全0前缀
    	if (in_array($dbcount, array(16, 256, 4096))) {
    		$suffix = substr(md5($hash_key), 0, log($dbcount, 16));  // 从 _00 到 _ff
    	} else {
    		$suffix = str_pad(dechex(hexdec(substr(md5($hash_key), 0, 4)) % $dbcount), ceil(log($dbcount, 16)), '0', STR_PAD_LEFT);
    	}
    
    	return $dbname . '_' . $suffix;
    }
}

/**
 * 获取表名
 *
 *	@string	$dbname		// 数据表名
 *	@int	$dbcount	// 分表数
 *	@string	$hash_key	// hash_key
 *	@return string		// 返回数据表名称
 *
 */
if(! function_exists('get_base_table_common')) {
    function get_base_table_common($tbname, $tbcount, $hash_key)
    {
    	if ($tbcount <= 1 || $hash_key == '') {
    		return $tbname;
    	}
    
    	// 拆表没有上限，分表后缀是十六进制，会补全0前缀
    	if (in_array($tbcount, array(16, 256, 4096))) {
    		$suffix = substr(md5($hash_key), 0, log($tbcount, 16));  // 从 _00 到 _ff
    	} else {
    		$suffix = str_pad(dechex(hexdec(substr(md5($hash_key), 0, 4)) % $tbcount), ceil(log($tbcount, 16)), '0', STR_PAD_LEFT);
    	}
    
    	return $tbname . '_' . $suffix;
    }
}

/* End of file database.php */
/* Location: ./application/config/database.php */
