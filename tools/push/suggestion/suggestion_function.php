<?php
require_once __DIR__ . '/../config.php';

$fields = explode(',', 'tid,parent,name,tid_name,cnt');

$fp = fopen($path . 'suggestion_functions.csv','w');
fputcsv($fp,$fields);

$table = 'functions';
$sql = 'SELECT max(id) max FROM ' . $table;
$rs = get_db_row($sql);
$max = $rs['max'];

for($i=0;$i<=$max;$i+=$step) {
    $sql = 'SELECT id,parent_id,name,status,is_deleted FROM ' . $table . ' WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
    $rs = get_db_rs($sql);
    foreach($rs as $one) {
        
        if($one['is_deleted'] == 'Y' || $one['status'] != 1) {
            continue;
        }
        unset($one['status'],$one['is_deleted']);

        $sql = 'SELECT alias FROM functions_aliases WHERE function_id=?';
        $sub_rs = get_db_rs($sql, array($one['id']));
        $aliases = array($one['name']);
        foreach($sub_rs as $sub_one) {
            $aliases[] = $sub_one['alias'];
        }
        $one['tid_name'] = sprintf('%s::%s', $one['id'], $one['name']);
        $one['name']    = implode('::', $aliases);
        $one['cnt']     = 0;

        $one = array_map('addslashes', $one);

        fputcsv($fp, $one);
    }
}
fclose($fp);
