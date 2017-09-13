<?php
/**
 * 导出城市、学历、性别、语言、学校
 */
require_once __DIR__ . '/../config.php';

$tables = array('regions','degrees','genders','languages','schools');

foreach($tables as $table) {
    //$sql = 'SHOW FULL FIELDS FROM ' . $table;
    $sql = 'SHOW FIELDS FROM '.$table;
    $rs = get_db_rs($sql);
    $fields = array();
    foreach($rs as $field) {
        $fields[] = $field['Field'];
    }

    $fp = fopen($path . $table . '.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM ' . $table;
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT * FROM ' . $table . ' WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            if($table == 'schools' && $one['is_deleted'] == 'Y') {
                continue;
            }
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);
}
