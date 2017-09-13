<?php
/**
 * 导出公司描述,同个type_id，以后面的为准
 *
 */
//error_reporting(0); 
set_time_limit(0);
ini_set('memory_limit','1024M');
$path = dirname(__FILE__);

//文件锁，独占执行
$lock_file  = sprintf('%s/.lock', $path);
$csv_file   = sprintf('%s/corporations_descriptions%s.csv', $path, date('YmdHis'));
$num = 10;

if(file_exists($lock_file)) {
    exit('已在执行中！');
}
file_put_contents($lock_file, getmypid());

$dsn = 'mysql:dbname=spider_jd;host=192.168.8.91;port=3306';
$dsn = 'mysql:dbname=spider_jd;host=127.0.0.1;port=3306';
//$dsn = 'mysql:dbname=7jd;host=192.168.1.180;port=3306';
$user = 'root';
$password = '';
$dbh = new PDO($dsn, $user, $password);
$dbh->query('set names utf8');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$fp = @fopen($csv_file, 'w');

$min_id = 0;
$num = 100;

if($fp) {
    start();
    fclose($fp);
}
@unlink($lock_file);

function start() {
    //global $fp;
    //fputcsv($fp, array('name','description'));
    start_liepin();
    start_51job();
    start_zhaopin();
    echo 'done';
}

function start_liepin() {
    global $dbh, $min_id, $num, $fp;

    $sql = "SELECT max(id) FROM jd_lietou";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $rs2 = $stmt->fetch();
    $max_id = $rs2[0];

    for($i=$min_id;$i<$max_id;$i=$i+$num) {
        $sql = sprintf('SELECT * FROM jd_lietou WHERE id in (%s)', implode(',', range($i+1, $i+$num)));
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($rs as $one) {
            $content = unserialize($one['content']);
            $description = '';
            if(!empty($content['qiyejieshao'])) {
                $description = trim_html($content['qiyejieshao']);
            }
            fputcsv($fp, array(addslashes($content['company']), addslashes($description)));
            //return;
        }
    }
}
function start_51job() {
    global $dbh, $min_id, $num, $fp;

    $sql = "SELECT max(id) FROM jd_functions_industry";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $rs2 = $stmt->fetch();
    $max_id = $rs2[0];

    for($i=$min_id;$i<$max_id;$i=$i+$num) {
        $sql = sprintf('SELECT * FROM jd_functions_industry WHERE id in (%s)', implode(',', range($i+1, $i+$num)));
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($rs as $one) {
            $content = unserialize($one['content']);
            $description = '';
            if(!empty($content['jianjie'])) {
                $description = trim_html($content['jianjie']);
            }
            fputcsv($fp, array(addslashes($content['company']), addslashes($description)));
            //return;
        }
    }
}
function start_zhaopin() {
    global $dbh, $min_id, $num, $fp;

    $sql = "SELECT max(id) FROM jd_zhaopin";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $rs2 = $stmt->fetch();
    $max_id = $rs2[0];

    for($i=$min_id;$i<$max_id;$i=$i+$num) {
        $sql = sprintf('SELECT * FROM jd_zhaopin WHERE id in (%s)', implode(',', range($i+1, $i+$num)));
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($rs as $one) {
            $content = unserialize($one['content']);
            $description = '';
            if(!empty($content['jianjie'])) {
                $description = trim_html($content['jianjie']);
            }
            fputcsv($fp, array(addslashes($content['company']), addslashes($description)));
            //return;
        }
    }
}

function trim_html($txt) {
    $txt = str_ireplace(
        array('&nbsp;', "\r", "\n"),
        array(' ', '', ''),
        strip_tags($txt)
    );

    return trim(preg_replace("/\s{2,}/",',',$txt));
}
