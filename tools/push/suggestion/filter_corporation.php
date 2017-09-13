<?php
require_once __DIR__ . '/../config.php';

$fields = explode(',', 'id,parent,user_id,name,city,status,updated,industry,alias,bundle');

$fp = fopen($path . 'filter_corporation.sql','w');
fputcsv($fp,$fields);

foreach(array('corporations', 'customers') as $table) {
    $sql = 'SELECT max(id) max FROM ' . $table;
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT id,parent_id,uid,name,city_id,status,UNIX_TIMESTAMP(updated_at) FROM ' . $table . ' WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {

            //公司关联行业
            $sql = 'SELECT industry_id FROM corporations_industries WHERE corporation_id=?';
            $sub_rs = get_db_rs($sql, array($one['id']));
            $industry_ids = array();
            foreach($sub_rs as $sub_one) {
                $industry_ids[] = $sub_one['industry_id'];
            }
            $one['industry'] = empty($industry_ids) ? '' : implode('::', $industry_ids);

            $sql = 'SELECT alias FROM corporations_aliases WHERE corporation_id=?';
            $sub_rs = get_db_rs($sql, array($one['id']));
            $aliases = array();
            foreach($sub_rs as $sub_one) {
                $aliases[] = $sub_one['alias'];
            }
            $one['alias'] = empty($aliases) ? '' : implode('::', $aliases);

            $one['bundle'] = ($table == 'customers') ? 'customer' : 'bdlist';

            $one = array_map('addslashes', $one);

            fputcsv($fp, $one);
        }
    }
}
fclose($fp);
