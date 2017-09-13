<?php
require_once __DIR__ . '/../config.php';

$fields = explode(',', 'id,name,id_name,cnt');

$fp = fopen($path . 'suggestion_school.csv','w');
fputcsv($fp,$fields);

$table = 'schools';
$sql = 'SELECT max(id) max FROM ' . $table;
$rs = get_db_row($sql);
$max = $rs['max'];

for($i=99999;$i<=$max;$i+=$step) {
    $sql = 'SELECT id,name_cn,is_deleted FROM ' . $table . ' WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
    $rs = get_db_rs($sql);
    foreach($rs as $one) {
        
        if($one['is_deleted'] == 'Y') {
            continue;
        }
        unset($one['is_deleted']);

        $one['id_name'] = sprintf('%s::%s', $one['id'], $one['name_cn']);
        $one['cnt']     = 0;

        $one = array_map('addslashes', $one);

        fputcsv($fp, $one);
    }
}
fclose($fp);
