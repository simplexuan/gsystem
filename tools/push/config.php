<?php
//error_reporting(0); 
set_time_limit(0);
ini_set('memory_limit','1024M');
$dsn = 'mysql:dbname=gsystem;host=192.168.1.201';
$user = 'devuser';
$password = 'devuser';
$dbh = new PDO($dsn, $user, $password);
$dbh->query('set names utf8');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$path = '/opt/log/gsystem/';
if(!is_dir($path)) {
    mkdir($path, 0777, true);
}

$step = 100;

//获取单条记录
function get_db_row($sql, $param = array()) {
    global $dbh;
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $rs = $stmt->fetch(PDO::FETCH_ASSOC);

    return $rs;
}

//获取多条记录集
function get_db_rs($sql, $param = array()) {
    global $dbh;
    $stmt = $dbh->prepare($sql);
    $stmt->execute($param);
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $rs;
}
