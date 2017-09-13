<?php 



require(BASEPATH.'core/Common.php');
require(APPPATH.'config/constants.php');

/*
 * ------------------------------------------------------
 *  Define a shutdown fatal error handler so we can log PHP errors
 * ------------------------------------------------------
 */
register_shutdown_function('_shutdown_function');
set_error_handler('_exception_handler');
 if (function_exists("set_time_limit") == TRUE AND @ini_get("safe_mode") == 0) {
     @set_time_limit(0);
 }

 $CFG =& load_class('Config', 'core');
 $BM  =& load_class('Benchmark', 'core');
 $EXT =& load_class('Hooks', 'core');

 $LANG =& load_class('Lang', 'core');
 $BM->mark('total_execution_time_start');
 $BM->mark('loading_time:_base_classes_start');

 require BASEPATH.'core/Gearman_Manager.php';
 function &get_instance()
 {
     return CI_Gearman_Manager::get_instance();
 }
/* End of file CodeIgniter.php */
/* Location: ./CodeIgniter/core/CodeIgniter.php */
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
