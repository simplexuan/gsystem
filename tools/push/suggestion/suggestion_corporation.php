<?php
require_once __DIR__ . '/../config.php';

$fields = explode(',', 'id,user_id,name,status,id_name,cnt,bundle');

$fp = fopen($path . 'suggestion_corporation.csv','w');
fputcsv($fp,$fields);

foreach(array('corporations', 'customers') as $table) {
    $sql = 'SELECT max(id) max FROM ' . $table;
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT id,uid,name,status FROM ' . $table . ' WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {

            if($one['status'] != 1) {
                continue;
            }

            $bundle = ($table == 'customers') ? 'customer' : 'bdlist';
            $one['id_name'] = sprintf('%s::%s::%s::%s', $one['id'], $one['name'], $one['status'], $bundle);
            $one['cnt'] = 0;
            $one['bundle'] = $bundle;

            $one = array_map('addslashes', $one);

            fputcsv($fp, $one);

            //bdlist
            if($table == 'corporations') {
                $sql = 'SELECT alias FROM corporations_aliases WHERE corporation_id=? AND type_id=?';
                $sub_rs = get_db_rs($sql, array($one['id'], 1));
                foreach($sub_rs as $sub_one) {
                    $data = array(
                        'id'    => $one['id'],
                        'uid'   => $one['uid'],
                        'name'  => $sub_one['alias'],
                        'status'=> $one['status'],
                        'id_name'=> sprintf('%s::%s::%s::%s', $one['id'], $one['name'], $one['status'], $bundle),
                        'cnt'   => 0,
                        'bundle'=> $bundle,
                    );
                    $data = array_map('addslashes', $data);
                    fputcsv($fp, $data);
                }
            }
        }
    }
}
fclose($fp);
