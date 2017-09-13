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
$max_file   = sprintf('%s/.maxid', $path);
$csv_file   = sprintf('%s/corporations_descriptions%s.csv', $path, date('YmdHis'));
$num = 10;

if(file_exists($lock_file)) {
    exit('已在执行中！');
}
file_put_contents($lock_file, getmypid());

if(file_exists($max_file)) {
    $min_id = file_get_contents($max_file);
} else {
    $min_id = 0;
}

$dsn = 'mysql:dbname=gsystem;host=192.168.1.201;port=3306';
$user = 'devuser';
$password = 'devuser';
$dbh = new PDO($dsn, $user, $password);
$dbh->query('set names utf8');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$fp = @fopen($csv_file, 'w');
if($fp) {
    fputcsv($fp, array('id','type_id','name','intro','notes','description'));
    $sql = "SELECT max(id) FROM corporations_descriptions";
    //$sql = "SELECT max(id) FROM corporations";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $rs2 = $stmt->fetch();
    $max_id = $rs2[0];

    for($i=$min_id;$i<$max_id;$i=$i+$num) {
        $sql = sprintf('SELECT * FROM corporations_descriptions WHERE id in (%s)', implode(',', range($i+1, $i+$num)));
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($rs as $one) {
            $sql = sprintf('SELECT * FROM corporations WHERE id=%d', $one['corporation_id']);
            $stmt = $dbh->prepare($sql);
            $stmt->execute();
            $rs2 = $stmt->fetch(PDO::FETCH_ASSOC);
            if(empty($rs) || empty($one['intro']) && empty($one['notes']) && empty($one['description'])) {
                continue;
            }
            fputcsv($fp, array($rs2['id'], $one['type_id'], addslashes($rs2['name']), addslashes($one['intro']), addslashes($one['notes']), addslashes($one['description'])));
        }
    }

    fclose($fp);
    file_put_contents($max_file, $max_id);
}

@unlink($lock_file);

