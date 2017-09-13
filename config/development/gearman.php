<?php
// Example PHP config
$gearman_config = array(
		'GearmanManager'=>array(
			'pid_file'    => '/opt/log/gsystem.worker_manager.pid',
			'auto_update' => 1,
			'worker_dir'  => 'workers,workers_39',
			'max_worker_lifetime'=> 3600*2,
			'max_runs_per_worker'=> 3000000000,
			'count'              => 1,
            'host' => array('localhost:4730'),
			//'user' => 'nobody',
			'log_file'=>'/opt/log/gsystem.gearman_manager.log',
			'include' =>'',
			'exclude'=>'import',
			'dedicated_count'=>10,
			'prefix'=>'',
			'function_prefix'=>'gsystem_',
			//'daemon' => TRUE,
			'verbose' =>'v',
			'dedicated_only' => TRUE,
			//'mode' => 'cli',
			),
        //"basic" => array(
            //'dedicated_count'=>20,
            //),
		);
