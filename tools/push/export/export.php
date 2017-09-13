<?php
require_once __DIR__ . '/../config.php';

export();

function export() {
    export_industry();
    export_corporation();
    export_function();
    export_title();
    export_product();
    export_corporation_title();
    export_school();
    echo 'done';
}

//行业字典
//id, name, parent, depth
function export_industry() {
    global $step,$path;
    $fields = explode(',', 'id,name,parent,depth');
    $fp = fopen($path . 'industry.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM industries';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT id,name,parent_id,depth FROM industries WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);

    $fields = explode(',', 'entity_id,alias');
    $fp = fopen($path . 'industry_alias.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM industries_aliases';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT industry_id,alias FROM industries_aliases WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);
}

//公司字典 只输出有行业的公司
//id, parent, company, industry_id, alias
function export_corporation() {
    global $step,$path;
    $fields = explode(',', 'id,parent,company,industry_id,alias,is_ka,is_top');
    $fp = fopen($path . 'corporation.csv','w');
    fputcsv($fp,$fields);

    $sql = 'select id,parent_id,name
        from corporations
        where status in (1,2)';
    $rs = get_db_rs($sql);
    foreach($rs as $one) {
        $sql = 'SELECT industry_id FROM corporations_industries WHERE corporation_id=?';
        $sub_rs = get_db_rs($sql, array($one['id']));
        $industry_ids = array();
        foreach($sub_rs as $sub_one) {
            $industry_ids[] = $sub_one['industry_id'];
        }
        $one['industry'] = implode('::', $industry_ids);
        $sql = 'SELECT alias,type_id FROM corporations_aliases WHERE corporation_id=?';
        $sub_rs = get_db_rs($sql, array($one['id']));
        $aliases = array();
        foreach($sub_rs as $sub_one) {
            if($sub_one['type_id'] == 3) {
                continue;
            }
            $aliases[] = $sub_one['alias'];
        }
        $one['alias'] = implode('::', $aliases);

        $sql = 'SELECT is_ka,is_top FROM corporations_tags WHERE id=?';
        $sub_rs = get_db_row($sql, array($one['id']));
        $one['is_ka'] = empty($sub_rs['is_ka']) ? 0 : $sub_rs['is_ka'];
        $one['is_top'] = empty($sub_rs['is_top']) ? 0 : $sub_rs['is_top'];

        $one = array_map('addslashes', $one);
        fputcsv($fp, $one);
    }
    fclose($fp);
}

//职能字典
//tid, industry_id, parent, name, wieght, flag
//entity_id, alias
//entity_id, keyword
//entity_id, knowledge
//特殊职能
//tid,parent,industry_id,name,alias,relations
function export_function() {
    global $step,$path;
    //主表
    $fields = explode(',', 'tid,industry_id,parent,name,weight,flag,industry_ids');
    $fp = fopen($path . 'functions.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM functions';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    $valid_functions = array();
    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT id,industry_id,parent_id,name,weight,flag,industry_ids,status,is_deleted FROM functions WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            if($one['is_deleted'] == 'Y' || $one['status'] != 1) {
                continue;
            }
            unset($one['status'], $one['is_deleted']);
            $valid_functions[] = $one['id'];
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);

    //别名
    $fields = explode(',', 'entity_id,alias');
    $fp = fopen($path . 'functions_alias.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM functions_aliases';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT function_id,alias FROM functions_aliases WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            if(!in_array($one['function_id'], $valid_functions)) {
                continue;
            }
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);

    //关键词
    $fields = explode(',', 'entity_id,keyword');
    $fp = fopen($path . 'functions_keyword.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM functions_keywords';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT function_id,keyword FROM functions_keywords WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            if(!in_array($one['function_id'], $valid_functions)) {
                continue;
            }
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);

    //知识点
    $fields = explode(',', 'entity_id,knowledge');
    $fp = fopen($path . 'functions_knowledge.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM functions_knowledges';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT function_id,knowledge FROM functions_knowledges WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            if(!in_array($one['function_id'], $valid_functions)) {
                continue;
            }
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);

    //特殊职能
    $fields = explode(',', 'tid,parent,industry_id,name,alias,relations');
    $fp = fopen($path . 'functions_special.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM functions_specials';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT id,parent_id,industry_id,name,alias,relations FROM functions_specials WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);

    //职能大类
    $fields = explode(',', 'cid,name,technical,universal,tids');
    $fp = fopen($path . 'functions_category.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM functions_categories';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT id,name,is_technical,is_universal,tids FROM functions_categories WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            if($one['is_technical'] == 'Y') {
                $one['is_technical'] = 1;
            } else {
                $one['is_technical'] = 0;
            }
            if($one['is_universal'] == 'Y') {
                $one['is_universal'] = 1;
            } else {
                $one['is_universal'] = 0;
            }
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);
}

//职级字典
//tid, bundle, title
function export_title() {
    global $step,$path;
    $fields = explode(',', 'tid,bundle,title');
    $fp = fopen($path . 'title.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM titles';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT id,category_id,name,is_deleted FROM titles WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            if ($one['is_deleted'] == 'Y') {
                continue;
            }
            unset($one['is_deleted']);
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);

    $fields = explode(',', 'entity_id,alias');
    $fp = fopen($path . 'title_alias.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM titles_aliases';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT title_id,alias,is_deleted FROM titles_aliases WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            if ($one['is_deleted'] == 'Y') {
                continue;
            }
            unset($one['is_deleted']);
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);
}

//项目字典|主要产品
//entity_id, product
function export_product() {
    global $step,$path;
    $fields = explode(',', 'entity_id,product');
    $fp = fopen($path . 'product.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM corporations_products';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=0;$i<=$max;$i+=$step) {
        $sql = 'SELECT corporation_id,product FROM corporations_products WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);
}

//公司定制职级
//entity_id,title,text,inner
function export_corporation_title() {
    global $step,$path;
    $fields = explode(',', 'entity_id,title,text,inner');
    $fp = fopen($path . 'corporation_title.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT corporation_id,title,description,`inner` FROM corporations_titles WHERE `inner`>0';
    $rs = get_db_rs($sql);
    foreach($rs as $one) {
        $one = array_map('addslashes', $one);
        fputcsv($fp, $one);
    }
    fclose($fp);
}

//学校
function export_school() {
    global $step,$path;
    $fields = explode(',', 'id,type,name,ename,alias');
    $fp = fopen($path . 'schools.csv','w');
    fputcsv($fp,$fields);

    $sql = 'SELECT max(id) max FROM schools';
    $rs = get_db_row($sql);
    $max = $rs['max'];

    for($i=99999;$i<=$max;$i+=$step) {
        $sql = 'SELECT id,type,name_cn,name_en,alias,is_deleted FROM schools WHERE id in (' . implode(',', range($i+1, $i+$step)) . ')';
        $rs = get_db_rs($sql);
        foreach($rs as $one) {
            if($one['is_deleted'] == 'Y') {
                continue;
            }
            unset($one['is_deleted']);

            $one = array_map('addslashes', $one);
            fputcsv($fp, $one);
        }
    }
    fclose($fp);
}
