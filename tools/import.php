<?php
/**
 * 
 * @package 
 * @version $id$
 * @copyright Copyright (c) 2012-2013 Yicheng Co. All Rights Reserved.
 * @author Guojing Liu <liuguojing@51chance.com.cn> 
 * @license 
 */

set_time_limit(0);
error_reporting(E_ALL);
ini_set('memory_limit','4096M');
class import {
    protected $_log;
    /**
     * model 
     * 
     * @param mixed $model 
     * @access public
     * @return mixed
     */
    public function model($model) {
        $this->load->model($model);
        if (($last_slash = strrpos($model, '/')) !== FALSE)
        {
            $model = substr($model, $last_slash + 1);
        }
        return $this->$model;
    }
    /**
     * __construct
     *
     * @param string $config
     * @param string $output_dir
     * @access protected
     * @return mixed
     */
    function __construct($config="", $output_dir="", $db="") {
        $this->_log = LoggerManager::getLogger(__CLASS__);
        $this->load->database('', FALSE, TRUE);
        $this->db->conn_id->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->conn_id->query('set names utf8');
        $this->db->save_queries = FALSE;
    }
    private function _get_max_corporation_id() {
        $rs = $this->db->select_max('id')->get('corporations')->row_array();
        return $rs['id'];
    }
    private function _format_corporation_name($name)
    {
        $search = array('（', '）', ' ');
        $replace = array('(', ')', '');
        $formated_name = str_replace($search, $replace, $name);
        $formated_name = strtolower($formated_name);
        return $formated_name;
    }
    function sync_inverted_index()
    {
        $step = 800;
        $max_id = $this->_get_max_corporation_id();

        if (empty($max_id)) {
            return;
        }

        $reduplicate_log = sprintf('/opt/log/gsystem/%s.log', getmypid());
        $reduplicate_fp = fopen($reduplicate_log, 'ab');
        for ($i = 1; $i <= $max_id; $i += $step) {
            $start_id = $i;
            $end_id = $i + $step;
            if ($end_id > $max_id) {
                $end_id = $max_id;
            }

            $corporations = $this->db->where_in('id', range($start_id, $end_id))->get('corporations')->result_array();

            if (empty($corporations)) continue;

            foreach ($corporations as $corporation) { // 优先保证启用/待审核的公司
                if (0 === $corporation['status']) continue; // 过滤掉仅入库的公司
                if (4 === $corporation['status']) continue; // 过滤掉审核不通过的公司
                $corporation_id = (int) $corporation['id'];
                $corporation_name = $this->_format_corporation_name($corporation['name']);
                $inverted_index = array('id'   => $corporation_id, 'name' => $corporation_name);

                $rs = $this->model('Model_corporation_name_inverted')->search_one(array('name' => $corporation_name));
                if (! empty($rs)) {
                    fwrite($reduplicate_fp, sprintf('[%s]%d%s', date('Y-m-d H:i:s'), $corporation_id, PHP_EOL));
                } else {
                    $this->model('Model_corporation_name_inverted')->save(array('corporation_name_inverted' => $inverted_index));
                }
            }

            fwrite($reduplicate_fp, '-------------------gap-------------------' . PHP_EOL);

            foreach ($corporations as $corporation) { // 仅入库的公司
                if (0 !== $corporation['status']) continue; // 过滤掉审核不通过的公司
                $corporation_id = (int) $corporation['id'];
                $corporation_name = $this->_format_corporation_name($corporation['name']);
                $inverted_index = array('id'   => $corporation_id, 'name' => $corporation_name);

                $rs = $this->model('Model_corporation_name_inverted')->search_one(array('name' => $corporation_name));
                if (! empty($rs)) {
                    fwrite($reduplicate_fp, sprintf('[%s]%d%s', date('Y-m-d H:i:s'), $corporation_id, PHP_EOL));
                } else {
                    $this->model('Model_corporation_name_inverted')->save(array('corporation_name_inverted' => $inverted_index));
                }
            }

            fwrite($reduplicate_fp, '-------------------gap-------------------' . PHP_EOL);

            foreach ($corporations as $corporation) { // 审核不通过的公司
                if (4 !== $corporation['status']) continue;
                $corporation_id = (int) $corporation['id'];
                $corporation_name = $this->_format_corporation_name($corporation['name']);
                $inverted_index = array('id'   => $corporation_id, 'name' => $corporation_name);

                $rs = $this->model('Model_corporation_name_inverted')->search_one(array('name' => $corporation_name));
                if (! empty($rs)) {
                    fwrite($reduplicate_fp, sprintf('[%s]%d%s', date('Y-m-d H:i:s'), $corporation_id, PHP_EOL));
                } else {
                    $this->model('Model_corporation_name_inverted')->save(array('corporation_name_inverted' => $inverted_index));
                }
            }
        }
        fwrite($reduplicate_fp, sprintf('[%s]End%s', date('Y-m-d H:i:s'), PHP_EOL));
        fclose($reduplicate_fp);
        return 1;
    }
    function __get($key){
        $CI =& get_instance();
        return $CI->$key;
    }
}
