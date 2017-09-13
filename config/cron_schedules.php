<?php

//$cron_schedule['add_plan'] = array(
//    'schedule'  => array(
//        'config_path' => '',            // cron表达式的标识 用于在配置文件或数据库中获取表达式 直接指定时为空
//        'cron_expr'   => '*/2 * * * *'  // 直接指定cron表达式 在配置文件或数据库中获取表达式为空
//    ),
//    'run'       => array(
//        'filepath'  => 'test',          // 文件所在的目录 相对于APPPATH
//        'filename'  => 'Myclass.php',   // 文件名
//        'class'     => 'Myclass',       // 类名 如果只是简单函数 可为空
//        'function'  => 'clear_log',     // 要执行的函数
//        'params'    => array('msg' => 'a cron log show.')          // 需要传递的参数
//    )
//);

//$cron_schedule['sync_800hr'] = array(
    //'schedule'  => array(
        //'config_path' => '',            // cron表达式的标识 用于在配置文件或数据库中获取表达式 直接指定时为空
        //'cron_expr'   => '* * * * *',  // 直接指定cron表达式 在配置文件或数据库中获取表达式为空
    //),
    //'run'       => array(
        //'filepath'  => 'tools/sync/',          // 文件所在的目录 相对于APPPATH
        //'filename'  => 'jd_company.php',   // 文件名
        //'class'     => 'jd_company',       // 类名 如果只是简单函数 可为空
        //'function'  => 'sync_800hr',     // 要执行的函数
        //'params'    =>   FALSE,         // 需要传递的参数
    //)
//);
//$cron_schedule['sync_company'] = array(
    //'schedule'  => array(
        //'config_path' => '',            // cron表达式的标识 用于在配置文件或数据库中获取表达式 直接指定时为空
        //'cron_expr'   => '*/30 * * * *',  // 直接指定cron表达式 在配置文件或数据库中获取表达式为空
    //),
    //'run'       => array(
        //'filepath'  => 'tools/sync/',          // 文件所在的目录 相对于APPPATH
        //'filename'  => 'sync_company.php',   // 文件名
        //'class'     => 'sync_company',       // 类名 如果只是简单函数 可为空
        //'function'  => 'start',     // 要执行的函数
        //'params'    =>   FALSE,         // 需要传递的参数
    //)
//);
$cron_schedule['sync_inverted_index'] = array(
    'schedule'  => array(
        'config_path' => '',            // cron表达式的标识 用于在配置文件或数据库中获取表达式 直接指定时为空
        'cron_expr'   => '20 11 06 11 *',  // 直接指定cron表达式 在配置文件或数据库中获取表达式为空
    ),
    'run'       => array(
        'filepath'  => 'tools/',          // 文件所在的目录 相对于APPPATH
        'filename'  => 'import.php',   // 文件名
        'class'     => 'import',       // 类名 如果只是简单函数 可为空
        'function'  => 'sync_inverted_index',     // 要执行的函数
        'params'    =>   FALSE,         // 需要传递的参数
    )
);
//$cron_schedule['export_inc'] = array(
    //'schedule'  => array(
        //'config_path' => '',            // cron表达式的标识 用于在配置文件或数据库中获取表达式 直接指定时为空
        //'cron_expr'   => '*/3 * * * *'  // 直接指定cron表达式 在配置文件或数据库中获取表达式为空
    //),
    //'run'       => array(
        //'filepath'  => 'tools/export/',          // 文件所在的目录 相对于APPPATH
        //'filename'  => 'exportor.php',   // 文件名
        //'class'     => 'Exportor',       // 类名 如果只是简单函数 可为空
        //'function'  => 'execute',     // 要执行的函数
        //'params'    =>  date('Y-m-d H:i:s', time()-180) ,         // 需要传递的参数
    //)
//);

//$cron_schedule['clear_log'] = ...
//$cron_schedule['create_sitemap'] = ...
//$cron_schedule['backup_database'] = ...
