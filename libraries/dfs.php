<?php
class Dfs {
    
    private $func = 'Dfs';
    private $server = 'dfs';
    private $default_length = 0;
    
    private $append_label = '';
    private $append_groupname = '';
    private $append_filename = '';
    
    private $download_label = '';
    private $download_offset = 0;
    private $download_total_length = 0;
    
    public function __construct() {
        //$this->_log = LoggerManager::getLogger(__CLASS__);
        //$this->gc_dfs->gearman_client($this->server, 500000, 50000000);
        $this->default_length = 10 * 1024 * 1024;
    }
    
    /**
     * 一次性保存文件
     * 
     * DFS接口说明：
     * content: 要保存的文件内容
     * ext: 要保存的文件的扩展名，不需要点号
     * results return：成功返回array("groupname" => "", "filename" => "")，失败返回false
     * 
     * 方法说明：
     * @param string $file 可以是一个文件路径，也可以是文件内容
     * @param string $name 文件的名字，当$file是文件内容的时候需要，用于拿后缀
     * @return array 接口返回的groupname和filename
     */
    public function upload($file, $name = '', $header = array()) {
        
        //根据情况自动分段
        return $this->append($file, 0, $name, '', $header);
        
        /*直接调用append
        
        //不是文件，就当成文件内容
        if(!@is_file($file)) {
            $content = $file;
        } else {
            $content = file_get_contents($file);
        }
        
        $ext = $this->_get_ext($name);
        
        $params = array(
            'p'=>array(
                'content'   => $content,
                'ext'       => $ext,
            ),
            'c'=>'Dfs',
            'm'=>'upload',
        );
        
        $header = empty($header) ? $this->header() : $header;
        $rs = $this->_gc_fore_work($params, $header);
        
        return $rs['response']['results'];
        
        */
    }
    
    /**
     * 分段保存文件
     * 
     * DFS接口说明：
     * content: 要保存的文件内容
     * ext: 要保存的文件的扩展名，不需要点号
     * groupname：分组名称
     * filename：文件地址
     * results return：成功返回array("groupname" => "", "filename" => "")，失败返回false
     * 注：第一次保存文件内容的时候，groupname和filename都置空，或者不传这两个参数
     * 注：后续追加文件内容，需要传递第一次调用本函数返回的groupname和filename参数
     */
    public function append($file, $length = 0, $name = '', $label = '', $header = array()) {
        
        $length = intval($length);
        $header = empty($header) ? $this->header() : $header;
        
        $is_file = true;
        if(strlen($file) > 4096) {
            $is_file = false;
        } elseif(strpos(str_replace('\\','/',$file),'/') === false && strlen($file) > 256) {
            $is_file = false;
        }
        
        if($is_file && @is_file($file)) {
            $rs = $this->_append_file($file, $length, $name, $header);
        } elseif(!empty($label)) {
            $rs = $this->_append_part($file, $length, $name, $label, $header);
        } else {
            $rs = $this->_append_content($file, $length, $name, $header);
        }
        
        return $rs;
    }
    
    /**
     * 上传文件，默认按4M传
     */
    private function _append_file($file, $length, $name, $header) {
        
        if(empty($length)) {
            $length = $this->default_length;
        }
        
        if($name) {
            $ext = $this->_get_ext($name);
        } else {
            $ext = $this->_get_ext($file);
        }
        
        $groupname = '';
        $filename = '';
        
        $handle = @fopen($file, 'r');
        while(!feof($handle)) {
            $buffer = fread($handle, $length);
            
            if($buffer === false) {
                if(!feof($handle)) {
                    return false;
                } else {
                    break;
                }
            }
            
            $params = array(
                'p'=>array(
                    'groupname' => $groupname,
                    'filename'  => $filename,
                    'content'   => $buffer,
                    'ext'       => $ext,
                ),
                'c'=>'Dfs',
                'm'=>'append',
            );
            
            $rs = $this->_gc_fore_work($params, $header);
            
            if($rs['response']['results'] === false) {
                return false;
            }
            
            if(empty($groupname) && empty($filename)) {
                $groupname = $rs['response']['results']['groupname'];
                $filename = $rs['response']['results']['filename'];
            }
        }
        
        return $rs['response']['results'];
    }
    
    /**
     * 上传完整文件内容，默认按4M传
     */
    private function _append_content($file, $length, $name, $header) {
        
        if(empty($length)) {
            $length = $this->default_length;
        }
        
        $ext = $this->_get_ext($name);
        
        $groupname = '';
        $filename = '';
        
        $start = 0;
        
        if(empty($file)) {
            $buffer = $file;
        } else {
            $buffer = substr($file, $start, $length);
        }
        
        while($buffer !== false) {
            
            $params = array(
                'p'=>array(
                    'groupname' => $groupname,
                    'filename'  => $filename,
                    'content'   => $buffer,
                    'ext'       => $ext,
                ),
                'c'=>'Dfs',
                'm'=>'append',
            );
            
            $rs = $this->_gc_fore_work($params, $header);
            
            if($rs['response']['results'] === false) {
                return false;
            }
            
            if(empty($groupname) && empty($filename)) {
                $groupname = $rs['response']['results']['groupname'];
                $filename = $rs['response']['results']['filename'];
            }
            
            $start += $length;
            $buffer = substr($file, $start, $length);
        }
        
        return $rs['response']['results'];
    }
    
    /**
     * 上传部分文件内容
     */
    private function _append_part($file, $length, $name, $label, $header) {
        
        if(empty($length)) {
            $length = $this->default_length;
        }
        
        $ext = $this->_get_ext($name);
        
        if($this->append_label != $label) {
            $this->append_label = $label;
            $this->append_groupname = '';
            $this->append_filename = '';
        }
        
        $start = 0;
        
        if(empty($file)) {
            $buffer = $file;
        } else {
            $buffer = substr($file, $start, $length);
        }
        
        while($buffer !== false) {
            
            $params = array(
                'p'=>array(
                    'groupname' => $this->append_groupname,
                    'filename'  => $this->append_filename,
                    'content'   => $buffer,
                    'ext'       => $ext,
                ),
                'c'=>'Dfs',
                'm'=>'append',
            );
            
            $rs = $this->_gc_fore_work($params, $header);
            
            if ($rs['response']['results'] === false) {
                return false;
            }
            
            if (empty($this->append_groupname) && empty($this->append_filename)) {
                $this->append_groupname = $rs['response']['results']['groupname'];
                $this->append_filename = $rs['response']['results']['filename'];
            }
            
            $start += $length;
            $buffer = substr($file, $start, $length);
        }
        
        return $rs['response']['results'];
    }
    
    
    /**
     * 获取文件信息
     * 
     * DFS接口说明：
     * groupname：分组名称
     * filename：文件地址
     * results return：成功返回array("size" => int)，失败返回false
     * 
     * 方法说明：
     * @param string $groupname 接口返回的groupname
     * @param string $filename 接口返回的filename
     * @return int 文件大小
     */
    public function info($groupname, $filename, $header = array()) {
        
        $params = array(
            'p'=>array(
                'groupname' => $groupname,
                'filename'  => $filename,
            ),
            'c'=>'Dfs',
            'm'=>'info',
        );
        
        $header = empty($header) ? $this->header() : $header;
        $rs = $this->_gc_fore_work($params, $header);
        
        if(empty($rs['response']['results']['size'])) {
            return false;
        }
        
        return $rs['response']['results']['size'];
    }
    
    /**
     * 获取文件内容
     * 
     * DFS接口说明：
     * groupname：分组名称
     * filename：文件地址
     * offset：内容开始位置，0开始
     * length：要获取的内容长度
     * results return：成功返回文件内容，失败返回false
     * 注：offset和length两个参数用于大文件分段获取内容
     * 注：如果要获取整个文件的内容，可不传offset和length参数，也可以将这两个参数传0值
     * 注：文件的整体长度通过info接口获取
     */
    public function download($groupname, $filename, $label = '', $length = 0, $header = array()) {
        
        $header = empty($header) ? $this->header() : $header;
        $length = intval($length);
        
        if(!empty($label)) {
            $content = $this->_download_more($groupname, $filename, $label, $length, $header);
        } else {
            $content = $this->_download_one($groupname, $filename, $length, $header);
        }
        
        return $content;
    }
    
    /**
     * 全部下载完一次性返回内容，多用于拿文件
     */
    private function _download_one($groupname, $filename, $length, $header) {
        
        $total_length = $this->info($groupname, $filename, $header);
        
        if ($total_length === false) {
            return false;
        }
        
        //如果没传length，默认大于4M文件采用分段
        if ( (empty($length) || $length > $this->default_length)
                && $total_length > $this->default_length) {
            $length = $this->default_length;
        }
        
        $content = '';
        
        $params = array(
            'p'=>array(
                'groupname' => $groupname,
                'filename'  => $filename,
                'offset'    => 0,
                'length'    => 0,
            ),
            'c'=>'Dfs',
            'm'=>'download',
        );
        
        if (!empty($length)) {
            $params['p']['length'] = $length;
            
            while ($params['p']['offset'] + 1 < $total_length) {
                
                //当剩余长度不足的时候，取剩余长度，否则会false
                if ($total_length - $params['p']['offset'] < $length) {
                    $params['p']['length'] = $total_length - $params['p']['offset'];
                }
                
                $rs = $this->_gc_fore_work($params, $header);
                if ($rs['response']['results'] === false) {
                    return false;
                }
                
                $content .= $rs['response']['results'];
                
                $params['p']['offset'] += $params['p']['length'];
            }
            
        } else {
            $rs = $this->_gc_fore_work($params, $header);
            $content = $rs['response']['results'];
        }
        
        return $content;
    }
    
    /**
     * 分批下载，分段返回
     */
    private function _download_more($groupname, $filename, $label, $length, $header) {
        
        if ($this->download_label == $label) {
            
            if ($this->download_total_length == $this->download_offset) {
                $this->download_label = '';
                $this->download_offset = 0;
                $this->download_total_length = 0;
                
                return false;
            }
            
        } else {
            $this->download_label = $label;
            $this->download_offset = 0;
            
            $this->download_total_length = $this->info($groupname, $filename, $header);
            
            if ($this->download_total_length === false) {
                return false;
            }
        }
        
        //如果没传length，默认大于4M文件采用分段
        if ( empty($length) || $length > $this->default_length || $this->download_total_length - $this->download_offset > $this->default_length) {
            $length = $this->default_length;
        }
        
        $content = '';
        
        $params = array(
            'p'=>array(
                'groupname' => $groupname,
                'filename'  => $filename,
                'offset'    => $this->download_offset,
                'length'    => $length,
            ),
            'c'=>'Dfs',
            'm'=>'download',
        );
        
        if (!empty($length)) {
            
            while ($params['p']['offset'] + 1 < $this->download_total_length) {
                
                //当剩余长度不足的时候，取剩余长度，否则会false
                if ($this->download_total_length - $params['p']['offset'] < $length) {
                    $params['p']['length'] = $this->download_total_length - $params['p']['offset'];
                }
                
                $rs = $this->_gc_fore_work($params, $header);
                if ($rs['response']['results'] === false) {
                    return false;
                }
                
                $content .= $rs['response']['results'];
                
                $params['p']['offset'] += $params['p']['length'];
                $this->download_offset = $params['p']['offset'];
            }
            
        } else {
            $rs = $this->_gc_fore_work($params, $header);
            $content = $rs['response']['results'];
        }
        
        return $content;
    }
    
    /**
     * 删除
     */
    public function del($groupname, $filename, $header = array()) {
        $header = empty($header) ? $this->header() : $header;
        $params = array(
            'p'=>array(
                'groupname' => $groupname,
                'filename'  => $filename,
            ),
            'c'=>'Dfs',
            'm'=>'del',
        );
        $rs = $this->_gc_fore_work($params, $header);
        
        return $rs['response']['results'];
    }
    
    /**
     * 获取后缀
     */
    private function _get_ext($name) {
        preg_match('/\.([a-zA-Z0-9]*?)$/', $name, $m);
        if (empty($m[1]))$m[1] = 'txt';
        return $m[1];
    }
    
    /**
     * gc的即时work
     */
    private function _gc_fore_work ($params, $header) {
        try {
            $this->gc_dfs->gearman_client($this->server, 3000, 5000000);
            $rs = $this->gc_dfs->do_job_foreground($this->func, $params, $header);
            
            if ($rs['response']['err_no'] > 0) {
                throw new Exception(sprintf('文件存取失败 err_no:%s,err_msg:%s', $rs['response']['err_no'], $rs['response']['err_msg']),47);
            }
            return $rs;
        } catch (Exception $e) {
           throw new Exception('文件存取失败',47);
        }
    }
    
    public function __get($key) {
        $CI =& get_instance();
        if ($key == 'gc_dfs') {
            $this->load->library('Gearman_Client','',$key);
            return $CI->$key;
        } elseif ($key == 'load') {
            return $CI->$key;
        } elseif ($key == 'uid' || $key == 'user_info') {
            return $CI->$key;
        }
    }
    
    public function header() {
        return array(
            'uid'       => $this->uid[getmypid()],
            'uname'     => $this->user_info[getmypid()]['user_name'],
            'version'   => '1',
            'signid'    => gen_sign_id(),
            'provider'  => 'mail',
            'ip'        => ip2num(get_local_ip()),
            'auth'      =>'sso',
            'token'     =>'',
        );
    }
}
