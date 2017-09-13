<?php
class mht{
    
    public $head = array();//解码后返回的头部信息
    public $body = array();//解码后返回的内容信息
    public $path = '';//保存文件的地方
    
    private $_bq = array('q','quoted-printable','b','base64');
    
    private $charset = '';//内容编码，用于转换头部的中文字符集
    private $arr_index = '';//内容存放的数组索引
    
    public $html = '';//保存html的内容
    public $html_path = '';
    
    private $_is_head = '';
    
    public $CharSet           = 'UTF-8';
    public $ContentType       = 'text/html';
    public $Encoding          = 'base64';
    public $WordWrap          = 0;
    public $LE              = "\n";
    public $attachements    = array();
    public $encodeBody      = '';
    
    public function __construct() {
        //$this->_log = LoggerManager::getLogger(__CLASS__);
        $path = $this->config->item('liepin_path').'preview/related/';
        if(!is_dir($path)) {
            mkdir($path,0777,true);
            chmod($path,0777);
        }
    }
    
    function decode($file, $path = '') {
        
        if(!is_file($file)) {
            return false;
        }
        
        $this->handle = @fopen($file, 'r');
        if(!$this->handle) {
            return $this->error('文件打开失败');
        }
        
        if(empty($path)) {
            $this->path = $this->config->item('liepin_path').'preview/related/' . uniqid() . '/';
            if(!is_dir($this->path)) {
                mkdir($this->path,0777,true);
                chmod($this->path,0777);
            }
        } else {
            if(!is_dir($path)) {
                return false;
            }
            $this->path = $path;
        }
        
        $this->decode_head();
        
        $this->decode_body();
        
        fclose($this->handle);
        
        //$this->html_path = $this->path.'1.html';
        
        //file_put_contents($this->html_path,$this->html);
        
        return $this->html;
    }
    
    /**
     * mht头内容的解码，取出头中有意义的内容
     */
    private function decode_head() {
        
        $this->head = array();
        $this->charset = '';
        $this->_is_head = '';
        
        $this->head['body']['encoding'] = '';
        $this->head['body']['charset'] = '';
        $this->head['body']['content-type'] = '';
        $this->head['body']['boundary'] = '';
        
        while(!feof($this->handle)) {
            if(empty($keep)) {
                $buffer = fgets($this->handle);
            }
            $keep = false;
            if(empty($this->_is_head)) {
                if(trim($buffer) != '') {
                    $this->_is_head = true;
                } else {
                    continue;
                }
            } elseif(trim($buffer) == '') {
                break;
            }
            
            $split_pos = strpos($buffer, ':');
            $title = strtolower(substr($buffer, 0, $split_pos));
            $content = trim(substr($buffer, $split_pos+1));
            
            switch ($title) {
                
                case 'content-transfer-encoding':
                    
                    $this->head['body']['encoding'] = trim($content);
                    break;
                    
                case 'content-type':// 整个mht的 Content类型
                    
                    //$content = $this->decode_mime($content);
                    
                    $content = trim($content);
                    //邮件内容的类型（图文、附件等）
                    preg_match('/([^;]*)/i',$content,$mat);
                    $this->head['body']['content-type'] = strtolower(trim($mat[1]));

                    if (preg_match('/multipart/i', $content)) {// 如果是 multipart 类型，取得 分隔符
                        while($buffer && !preg_match('/boundary=(["|\']?)([^\n\r\'";\\\]*)\1/i', $buffer, $mat)) {
                            $buffer = fgets($this->handle);
                            $first = substr($buffer, 0, 1);
                            if(trim($buffer) != '' && !empty($first) && trim($first) == '') {
                                $buffer = trim($buffer);
                            } else {
                                $keep = true;
                                break;
                            }
                        }
                        if(!$keep) {
                            $this->head['body']['boundary'] = '--' . trim($mat[2]);
                        } else {
                            break;
                        }
                        
                    } else {//对于一般的正文类型，直接取得其编码方法
                        //内容的字符集
                        while ($buffer && !preg_match('/charset=(["|\']?)([^\s\'";\\\]*)\1/i', $buffer, $mat)) {
                            $buffer = fgets($this->handle);
                            $first = substr($buffer, 0, 1);
                            if(trim($buffer) != '' && !empty($first) && trim($first) == '') {
                                $buffer = trim($buffer);
                            } else {
                                $keep = true;
                                break;
                            }
                        }
                        if(!$keep) {
                            $this->head['body']['charset'] = empty($mat[2]) ? '' : strtolower(trim($mat[2]));
                        } else {
                            break;
                        }
                        
                    }
                    break;
                    
                default:
                    break;
            }
            
            
        }
        
        $this->_is_head = false;
        
        return $this->head;
    }
    
    /**
     * mht正文的解码，其中用到了不少头解码所得来的信息
     */
    private function decode_body() {
        
        $this->html = '';
        $this->body = array ();
        $this->arr_index = 0;
        
        if (!preg_match('/multipart/is', $this->head['body']['content-type'])) {// 如果不是复合类型，可以直接解码
            $tem_body = '';
            
            while(!feof($this->handle)) {
                $buffer = fgets($this->handle);
                
                if($buffer !== false) {
                    if(in_array($this->head['body']['encoding'], $this->_bq)) {
                        $tem_body .= $this->decode_by_bq($this->head['body']['encoding'], $buffer);
                    } else {
                        $tem_body .= $buffer;
                    }
                }
            }
            
            $this->html = $this->iconv($tem_body, $this->head['body']['charset']);
            
            unset ($tem_body);
            
        } else {
            $this->decode_mult($this->head['body']['boundary']); //调用复合类型的解码方法
            $this->hackle();
        }
        
        return $this->body;
    }
    
    /**
     * 用递归的方法实现复合类型mht正文的解码，调用时给出该复合类型的类型、分隔符及内容数组中的开始指针
     */
    private function decode_mult($boundary) {
        $buffer = '';
        while (!feof($this->handle)) {// 这是一个部分的结束标识；
            
            if(strpos($buffer, $boundary) === false) {
                $buffer = fgets($this->handle);
                if($buffer === false)return;
            }
            
            while (strpos($buffer, $boundary) === false) {//找到一个开始标记
                $buffer = fgets($this->handle);
                if($buffer === false)return;
            }
            
            if (strpos($buffer, $boundary . '--') !== false) {//结束标记
                return;
            }
            
            $code_type = '';
            while (!preg_match('/Content-Type:\s*([^\s;]*)/i', $buffer, $mat)){
                //有可能编码方式在前面--hotmail
                if (preg_match('/Content-Transfer-Encoding:\s*([^\s;\'"]*)/i', $buffer, $mat2)){
                    $code_type = strtolower(trim($mat2[1])); // 编码方式
                }
                $buffer = fgets($this->handle);
                if($buffer === false)return;
            }
            $sub_type = trim($mat[1]); // 取得这一个部分的 类型是milt or text ....
            
            if (preg_match('/multipart/is', $sub_type)) {// 该子部分又是有多个部分的；
                while (!preg_match('/boundary=(["|\']?)([^\n\r\'";\\\]*)\1/i', $buffer, $mat)){
                    $buffer = fgets($this->handle);
                    if($buffer === false)return;
                }
                $sub_boundary = '--' . trim($mat[2]); // 子部分的分隔符；
                $this->decode_mult($sub_boundary);
                
            } else {
                $comm = '';
                while (trim($buffer) != '') {
                    
                    //if(!isset($code_type)) $code_type = '';
                    if (preg_match('/Content-Transfer-Encoding:\s*([^\s;\'"]*)/i', $buffer, $mat)){
                        $code_type = strtolower(trim($mat[1])); // 编码方式
                    }
                    
                    if(trim(substr($buffer,0,1)) == '') {
                        $comm = rtrim($comm) . trim($buffer);
                    } else {
                        $comm .= $buffer;
                    }
                    
                    $buffer = fgets($this->handle);
                    if($buffer === false)return;
                    
                } // comm 是编码的说明部分
                
                if (preg_match('/name=\s*([^"\'\s]+)/is', $comm, $mat)){
                    $name = trim($mat[1]);
                }elseif (preg_match('/name=\s*(["|\']?)([^"\']+)\1/is', $comm, $mat)){
                    $name = trim($mat[2]);
                }elseif (preg_match('/name=\s*(["|\']?)(.+?)\1/is', $comm, $mat)){
                    $name = trim($mat[2]);
                }else{
                    $name = '';
                }
                $name = str_replace(array("\n","\r"),'',$name);
                
                if (preg_match('/Content-Disposition:\s?([^;\n]*)/i', $comm, $mat)){
                    $disp = trim($mat[1]);
                }else{
                    $disp = '';
                }
                
                if (preg_match('/charset\s*=\s*(["|\']?)([^\'"\n;]*)\1?/i', $comm, $mat)){
                    $char_set = trim($mat[2]);
                    empty($this->charset) && $this->charset = $char_set;
                }else{
                    $char_set = '';
                }
                
                if (preg_match('/Content-ID:(.*)/i', $comm, $mat)){
                    // 图片的标识符。
                    $content_id = trim($mat[1]," \n\r<>");
                } else {
                    $content_id = '';
                }
                
                /**
                 * 内嵌图片可能不是附件，而是个链接
                 */
                if (preg_match('/Content-Base:\s*([\'"]?)([^;\n]*)\1/is', $comm, $mat)) {
                    $content_base = preg_replace('#\s*#','',trim($mat[2]));
                } else {
                    $content_base = '';
                }
                if (preg_match('/Content-Location:\s*([\'"]?)([^;\n]*)\1/is', $comm, $mat)) {
                    $content_location = preg_replace('#\s*#','',trim($mat[2]));
                } else {
                    $content_location = '';
                }
                
                $this->body[$this->arr_index]['content_id'] = trim($content_id);
                $this->body[$this->arr_index]['content_base'] = $content_base;
                $this->body[$this->arr_index]['content_location'] = $content_location;
                $this->body[$this->arr_index]['content-type'] = trim(strtolower($sub_type));
                $this->body[$this->arr_index]['charset'] = trim($char_set);
                $this->body[$this->arr_index]['disposition'] = trim($disp);
                
                //if(!preg_match('#^text#is',$this->body[$this->arr_index]['content-type'])) {
                if(!in_array(strtolower($this->body[$this->arr_index]['content-type']),array('text/html','text/plain'))) {
                    if(!empty($this->body[$this->arr_index]['content_location'])) {
                        $this->body[$this->arr_index]['name'] = basename($this->body[$this->arr_index]['content_location']);
                    } elseif(!empty($this->body[$this->arr_index]['content_id'])) {
                        if(empty($name)) {
                            $name = $this->body[$this->arr_index]['content_id'];
                        }
                        $this->body[$this->arr_index]['name'] = $name;
                    } else {
                        //$this->_log->warn();
                        $this->body[$this->arr_index]['name'] = '';
                    }
                    $this->body[$this->arr_index]['path'] = $this->path . $this->body[$this->arr_index]['name'];
                }
                
                // 下一行开始取回正文
                $content = '';
                $tmp_buffers = '';
                $buffer = fgets($this->handle);//第一个空行去掉
                
                while (strpos($buffer, $boundary) === false) {
                    $buffer_tmp = $buffer;
                    $buffer = fgets($this->handle);
                    if(in_array($code_type, $this->_bq)) {
                        $buffer_tmp = $this->decode_by_bq($code_type, $buffer_tmp);
                    }
                    
                    //$char_set是空，表示为文件   修改：文件名为中文的时候，char_set不为空
                    //if(!preg_match('#^text#is',$this->body[$this->arr_index]['content-type'])) {
                    if(!in_array(strtolower($this->body[$this->arr_index]['content-type']),array('text/html','text/plain'))) {
                        
                        file_put_contents($this->body[$this->arr_index]['path'], $buffer_tmp,FILE_APPEND);
                        
                    } else {
                        $content .= $buffer_tmp;
                    }
                    
                    if($buffer === false) {
                        break;
                    }
                }
                
                if(!empty($content)) {
                    $this->html = $this->iconv($content, $char_set);
                    unset($this->body[$this->arr_index]);
                }
                
                $this->arr_index++;
            }
        }
    }
    
    function hackle() {
        foreach($this->body as $one) {
            if($one['content_location']) {
                $this->html = str_replace($one['content_location'], 'file://'.$one['path'], $this->html);
            } elseif($one['content_id']) {
                $this->html = str_replace('cid:'.$one['content_id'], 'file://'.$one['path'], $this->html);
            }
        }
        
        $this->html = preg_replace(array('#(<meta[^>]*?charset\s*=\s*[\"\'])[^\"\'>]*([\"\'][^>]*?>)#is',
                                            '#(<meta[^>]*?charset\s*=\s*)[^\"\'>]*([^\"\'>\s]*?>)#is'),'$1utf-8$2',$this->html);
        
    }
    
    /**
     * b或q解码
     */
    private function decode_by_bq($encoding, $content,$is_decode_mime = FALSE) {
        switch (strtolower($encoding)) {
            case 'q':
            case 'quoted-printable' :
                $decoded_text = quoted_printable_decode($content);
                if (strtolower($this->charset) == 'windows-1251') {
                    $decoded_text = convert_cyr_string($decoded_text, 'w', 'k');
                }
                
                break;
            case 'b':
            case 'base64' :
                if($this->is_base64($content)) {
                    $decoded_text = base64_decode($content);
                    if($decoded_text === false ) {
                        $decoded_text = $content;
                    }
                    if (strtolower($this->charset) == 'windows-1251') {
                        $decoded_text = convert_cyr_string($decoded_text, 'w', 'k');
                    }
                } else {
                    $decoded_text = $content;
                }
                break;
            default :
                if($is_decode_mime) {
                    $decoded_text = false;
                } else {
                    $decoded_text = $content;
                }
                break;
        }
        return $decoded_text;
    }
    
    /**
     * 验证是否base64
     */
    private function is_base64($str) {
        return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/is', trim($str));
    }
    
    /**
     * 判断是否utf8
     */
    private function is_utf8($word)
    {   //return json_encode($word);
        return mb_check_encoding($word,'UTF-8');
        //if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$word) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$word) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$word) == true)
        //if($word === mb_convert_encoding(mb_convert_encoding($word, "GBK", "UTF-8"), "UTF-8", "GBK"))
        if($word === mb_convert_encoding(mb_convert_encoding($word, "UTF-32", "UTF-8"), "UTF-8", "UTF-32"))
        {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 根据编码转化字符集
     */
    private function iconv(&$target, $from, $to = 'UTF-8') {
        
        //if(empty($target) || strtoupper($from) == 'UTF-8') return $target;
        if(empty($target)) return $target;
        
        if(is_array($target)) {
            foreach ($target as $key=>$val) {
                $target[$key] = $this->iconv($target[$key], $from, $to);
            }
        } else {
            //mb_check_encoding不支持gb18030
            if(in_array(strtolower($from), array('gb2313','gb2312','gb18030'))) {
                $from = 'gbk';
            }
            
            //如果已经是utf8($from已经不可能是utf-8了)
            $encode = (empty($from)) ? 'GBK' : $from;
            
            if(!@mb_check_encoding($target,$encode)) {
                if($this->is_utf8($target)) {
                    return $target;
                } elseif(strtoupper($from) != 'UTF-8'){
                    try{
                        $decoded_text = iconv($from,$to,$target);
                    } catch (Exception $e) {
                        $decoded_text = false;
                    }
                    if($decoded_text !== false) {
                        return $decoded_text;
                    }
                }
            } else {
                if(empty($from) && $this->is_utf8($target)) {
                    return $target;
                }
                $from = $encode;
            }
            
            if(empty($from) && function_exists('mb_convert_encoding')) {
                //return mb_convert_encoding($target, $to, 'auto');
                $target = mb_convert_encoding($target, $to, 'GB2312,GBK,GB18030,ASCII,ISO-8859-1,7bit,8bit,Windows-1251,Windows-1252,utf-8');
            } else {
                if(empty($from)) return $target;
                
                try{
                    $decoded_text = iconv($from,$to,$target);
                } catch (Exception $e) {
                    $decoded_text = false;
                }
                
                if($decoded_text === false && function_exists('mb_convert_encoding')) {//转换失败
                    try{
                        $decoded_text = mb_convert_encoding($target, $to, $from);
                    } catch (Exception $e) {
                        $decoded_text = mb_convert_encoding($target, $to, 'GB2312,GBK,GB18030,ASCII,ISO-8859-1,7bit,8bit,Windows-1251,Windows-1252,utf-8');
                    }
                }
                $target = $decoded_text;
            }
        }
        
        return $target;
    }
    /**
     * 编码mht，附件放在指定的目录里，不递归查找文件了
     */
    function encode($content, $attachment_path = '') {
        
        $this->encodeBody = $content;
        
        $this->attachements = array();
        
        if($attachment_path && is_dir($attachment_path)) {
            exec('ls '.$attachment_path, $list, $return);
            if($return != 0) {
                $attachment_path = '';
            } else {
                foreach($list as $key=>$val) {
                    $list[$key] = sprintf('%s/%s',$attachment_path,$val);
                    $this->AddAttachment($list[$key], $val);
                }
            }
        }
        
        return $this->createHeader().$this->createBody();
    }
    
    function createHeader() {
        $result = '';
        // Set the boundaries
        $this->boundary = md5(uniqid(time()));
        
        $result .= $this->HeaderLine('From', '<由 Microsoft Internet Explorer 5 保存>');
        $result .= $this->HeaderLine('Subject', '');
        $result .= $this->HeaderLine('Date', self::RFCDate());
        $result .= $this->HeaderLine('MIME-Version', '1.0');
        $result .= $this->GetMIME();
        $result .= $this->HeaderLine('X-MimeOLE', 'Produced By Microsoft MimeOLE V6.00.2900.5512');
        $result .= $this->LE.$this->LE;
        
        return $result;
    }
    function createBody() {
        $this->SetWordWrap();
        $body = '';
        if(empty($this->attachements)) {
            $body .= $this->EncodeString($this->encodeBody, $this->Encoding);
        } else {
            $body .= $this->GetBoundary($this->boundary, '', '', '');
            $body .= $this->EncodeString($this->encodeBody, $this->Encoding);
            $body .= $this->LE;
            $body .= $this->AttachAll();
        }
        
        return $body;
    }
    public function AddAttachment($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream') {
        if ( !@is_file($path)) {
            throw new Exception('文件无效');
        }
        
        if(empty($name)) {
            $name = basename($path);
        }
        
        $filename = $name;
        
        $cid = md5(uniqid(time()).$name);
        
        $this->encodeBody = str_replace($name,'cid:'.$cid,$this->encodeBody);
        
        $this->attachements[] = array(
            0 => $path,
            1 => $filename,
            2 => $name,
            3 => $encoding,
            4 => $type,
            5 => false,  // isStringAttachment
            6 => 'inline',
            7 => $cid
        );
        
        return true;
    }
    private function AttachAll() {
        // Return text of body
        $mime = array();
        $cidUniq = array();
        $incl = array();

        // Add all attachments
        foreach ($this->attachements as $attachment) {
            // Check for string attachment
            $bString = $attachment[5];
            if ($bString) {
                $string = $attachment[0];
            } else {
                $path = $attachment[0];
            }

            if (in_array($attachment[0], $incl)) { continue; }
            $filename    = $attachment[1];
            $name        = $attachment[2];
            $encoding    = $attachment[3];
            $type        = $attachment[4];
            $disposition = $attachment[6];
            $cid         = $attachment[7];
            $incl[]      = $attachment[0];
            if ( $disposition == 'inline' && isset($cidUniq[$cid]) ) { continue; }
            $cidUniq[$cid] = true;

            $mime[] = sprintf("--%s%s", $this->boundary, $this->LE);
            $mime[] = sprintf("Content-Type: %s; name=\"%s\"%s", $type, $this->EncodeHeader($this->SecureHeader($name)), $this->LE);
            $mime[] = sprintf("Content-Transfer-Encoding: %s%s", $encoding, $this->LE);

            if($disposition == 'inline') {
                $mime[] = sprintf("Content-ID: <%s>%s", $cid, $this->LE);
            }

            $mime[] = sprintf("Content-Disposition: %s%s", $disposition, $this->LE.$this->LE);

            // Encode as string attachment
            if($bString) {
                $mime[] = $this->EncodeString($string, $encoding);
                $mime[] = $this->LE.$this->LE;
            } else {
                $mime[] = $this->EncodeFile($path, $encoding);
                $mime[] = $this->LE.$this->LE;
            }
        }

        $mime[] = sprintf("--%s--%s", $this->boundary, $this->LE);

        return join('', $mime);
    }
    private function EncodeFile($path, $encoding = 'base64') {
        if (is_file($path) && !is_readable($path)) {
            throw new Exception('文件无效');
        }
        if (function_exists('get_magic_quotes')) {
            function get_magic_quotes() {
                return false;
            }
        }
        if (PHP_VERSION < 6) {
            $magic_quotes = get_magic_quotes_runtime();
            //set_magic_quotes_runtime(0);
            ini_set('magic_quotes_runtime',0);
        }
        
        $file_buffer  = file_get_contents($path);
        $file_buffer  = $this->EncodeString($file_buffer, $encoding);
        if (PHP_VERSION < 6) {
            //set_magic_quotes_runtime($magic_quotes);
            ini_set('magic_quotes_runtime',$magic_quotes);
        }
        return $file_buffer;
    }
    public function SecureHeader($str) {
        $str = str_replace("\r", '', $str);
        $str = str_replace("\n", '', $str);
        return trim($str);
    }
    public function EncodeHeader($str, $position = 'text') {
        $x = 0;

        switch (strtolower($position)) {
            case 'phrase':
                if (!preg_match('/[\200-\377]/', $str)) {
                    // Can't use addslashes as we don't know what value has magic_quotes_sybase
                    $encoded = addcslashes($str, "\0..\37\177\\\"");
                    if (($str == $encoded) && !preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $str)) {
                        return ($encoded);
                    } else {
                        return ("\"$encoded\"");
                    }
                }
                $x = preg_match_all('/[^\040\041\043-\133\135-\176]/', $str, $matches);
                break;
            case 'comment':
                $x = preg_match_all('/[()"]/', $str, $matches);
                // Fall-through
            case 'text':
            default:
                $x += preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
                break;
        }

        if ($x == 0) {
            return ($str);
        }

        $maxlen = 75 - 7 - strlen($this->CharSet);
        // Try to select the encoding which should produce the shortest output
        if (strlen($str)/3 < $x) {
            $encoding = 'B';
            if (function_exists('mb_strlen') && $this->HasMultiBytes($str)) {
                // Use a custom function which correctly encodes and wraps long
                // multibyte strings without breaking lines within a character
                $encoded = $this->Base64EncodeWrapMB($str);
            } else {
                $encoded = base64_encode($str);
                $maxlen -= $maxlen % 4;
                $encoded = trim(chunk_split($encoded, $maxlen, "\n"));
            }
        } else {
            $encoding = 'Q';
            $encoded = $this->EncodeQ($str, $position);
            $encoded = $this->WrapText($encoded, $maxlen, true);
            $encoded = str_replace('='.$this->LE, "\n", trim($encoded));
        }

        $encoded = preg_replace('/^(.*)$/m', " =?".$this->CharSet."?$encoding?\\1?=", $encoded);
        $encoded = trim(str_replace("\n", $this->LE, $encoded));

        return $encoded;
    }
    public function EncodeString ($str, $encoding = 'base64') {
        $encoded = '';
        switch(strtolower($encoding)) {
            case 'base64':
                $encoded = chunk_split(base64_encode($str), 76, $this->LE);
                break;
            case '7bit':
            case '8bit':
                $encoded = $this->FixEOL($str);
                //Make sure it ends with a line break
                if (substr($encoded, -(strlen($this->LE))) != $this->LE)
                    $encoded .= $this->LE;
                break;
            case 'binary':
                $encoded = $str;
                break;
            case 'quoted-printable':
                $encoded = $this->EncodeQP($str);
                break;
            default:
                //$this->_log->warn('');//TODO
                throw new Exception('文件无效');
                break;
        }
        return $encoded;
    }
    public function EncodeQP($string, $line_max = 76, $space_conv = false) {
        if (function_exists('quoted_printable_encode')) { //Use native function if it's available (>= PHP5.3)
            return quoted_printable_encode($string);
        }
        $filters = stream_get_filters();
        if (!in_array('convert.*', $filters)) { //Got convert stream filter?
            return $this->EncodeQPphp($string, $line_max, $space_conv); //Fall back to old implementation
        }
        $fp = fopen('php://temp/', 'r+');
        $string = preg_replace('/\r\n?/', $this->LE, $string); //Normalise line breaks
        $params = array('line-length' => $line_max, 'line-break-chars' => $this->LE);
        $s = stream_filter_append($fp, 'convert.quoted-printable-encode', STREAM_FILTER_READ, $params);
        fputs($fp, $string);
        rewind($fp);
        $out = stream_get_contents($fp);
        stream_filter_remove($s);
        $out = preg_replace('/^\./m', '=2E', $out); //Encode . if it is first char on a line, workaround for bug in Exchange
        fclose($fp);
        
        return $out;
    }
    public function EncodeQPphp( $input = '', $line_max = 76, $space_conv = false) {
        $hex = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
        $lines = preg_split('/(?:\r\n|\r|\n)/', $input);
        $eol = "\r\n";
        $escape = '=';
        $output = '';
        while( list(, $line) = each($lines) ) {
            $linlen = strlen($line);
            $newline = '';
            for($i = 0; $i < $linlen; $i++) {
                $c = substr( $line, $i, 1 );
                $dec = ord( $c );
                if ( ( $i == 0 ) && ( $dec == 46 ) ) { // convert first point in the line into =2E
                    $c = '=2E';
                }
                if ( $dec == 32 ) {
                    if ( $i == ( $linlen - 1 ) ) { // convert space at eol only
                        $c = '=20';
                    } else if ( $space_conv ) {
                        $c = '=20';
                    }
                } elseif ( ($dec == 61) || ($dec < 32 ) || ($dec > 126) ) { // always encode "\t", which is *not* required
                    $h2 = floor($dec/16);
                    $h1 = floor($dec%16);
                    $c = $escape.$hex[$h2].$hex[$h1];
                }
                if ( (strlen($newline) + strlen($c)) >= $line_max ) { // CRLF is not counted
                    $output .= $newline.$escape.$eol; //  soft line break; " =\r\n" is okay
                    $newline = '';
                    // check if newline first character will be point or not
                    if ( $dec == 46 ) {
                        $c = '=2E';
                    }
                }
                $newline .= $c;
            } // end of for
            $output .= $newline.$eol;
        } // end of while
        return $output;
    }
    private function GetBoundary($boundary, $charSet, $contentType, $encoding) {
        $result = '';
        if($charSet == '') {
            $charSet = $this->CharSet;
        }
        if($contentType == '') {
            $contentType = $this->ContentType;
        }
        if($encoding == '') {
            $encoding = $this->Encoding;
        }
        $result .= $this->TextLine('--' . $boundary);
        $result .= sprintf("Content-Type: %s; charset = \"%s\"", $contentType, $charSet);
        $result .= $this->LE;
        $result .= $this->HeaderLine('Content-Transfer-Encoding', $encoding);
        $result .= $this->LE;
    
        return $result;
    }
    public function SetWordWrap() {
        if($this->WordWrap < 1) {
            return;
        }
        $this->encodeBody = $this->WrapText($this->encodeBody, $this->WordWrap);
    }
    public function WrapText($message, $length, $qp_mode = false) {
        $soft_break = ($qp_mode) ? sprintf(" =%s", $this->LE) : $this->LE;
        // If utf-8 encoding is used, we will need to make sure we don't
        // split multibyte characters when we wrap
        $is_utf8 = (strtolower($this->CharSet) == "utf-8");
    
        $message = $this->FixEOL($message);
        if (substr($message, -1) == $this->LE) {
          $message = substr($message, 0, -1);
        }
    
        $line = explode($this->LE, $message);
        $message = '';
        for ($i=0 ;$i < count($line); $i++) {
            $line_part = explode(' ', $line[$i]);
            $buf = '';
            for ($e = 0; $e<count($line_part); $e++) {
                $word = $line_part[$e];
                if ($qp_mode and (strlen($word) > $length)) {
                    $space_left = $length - strlen($buf) - 1;
                    if ($e != 0) {
                        if ($space_left > 20) {
                            $len = $space_left;
                            if ($is_utf8) {
                                $len = $this->UTF8CharBoundary($word, $len);
                            } elseif (substr($word, $len - 1, 1) == "=") {
                                $len--;
                            } elseif (substr($word, $len - 2, 1) == "=") {
                                $len -= 2;
                            }
                            $part = substr($word, 0, $len);
                            $word = substr($word, $len);
                            $buf .= ' ' . $part;
                            $message .= $buf . sprintf("=%s", $this->LE);
                        } else {
                            $message .= $buf . $soft_break;
                        }
                        $buf = '';
                    }
                    while (strlen($word) > 0) {
                        $len = $length;
                        if ($is_utf8) {
                            $len = $this->UTF8CharBoundary($word, $len);
                        } elseif (substr($word, $len - 1, 1) == "=") {
                            $len--;
                        } elseif (substr($word, $len - 2, 1) == "=") {
                            $len -= 2;
                        }
                        $part = substr($word, 0, $len);
                        $word = substr($word, $len);
                        
                        if (strlen($word) > 0) {
                            $message .= $part . sprintf("=%s", $this->LE);
                        } else {
                            $buf = $part;
                        }
                    }
                } else {
                    $buf_o = $buf;
                    $buf .= ($e == 0) ? $word : (' ' . $word);
                    
                    if (strlen($buf) > $length and $buf_o != '') {
                        $message .= $buf_o . $soft_break;
                        $buf = $word;
                    }
                }
            }
            $message .= $buf . $this->LE;
        }
        
        return $message;
    }
    private function FixEOL($str) {
        $str = str_replace("\r\n", "\n", $str);
        $str = str_replace("\r", "\n", $str);
        $str = str_replace("\n", $this->LE, $str);
        return $str;
    }
    public function UTF8CharBoundary($encodedText, $maxLength) {
        $foundSplitPos = false;
        $lookBack = 3;
        while (!$foundSplitPos) {
            $lastChunk = substr($encodedText, $maxLength - $lookBack, $lookBack);
            $encodedCharPos = strpos($lastChunk, "=");
            if ($encodedCharPos !== false) {
                // Found start of encoded character byte within $lookBack block.
                // Check the encoded byte value (the 2 chars after the '=')
                $hex = substr($encodedText, $maxLength - $lookBack + $encodedCharPos + 1, 2);
                $dec = hexdec($hex);
                if ($dec < 128) { // Single byte character.
                    // If the encoded char was found at pos 0, it will fit
                    // otherwise reduce maxLength to start of the encoded char
                    $maxLength = ($encodedCharPos == 0) ? $maxLength :
                    $maxLength - ($lookBack - $encodedCharPos);
                    $foundSplitPos = true;
                } elseif ($dec >= 192) { // First byte of a multi byte character
                    // Reduce maxLength to split at start of character
                    $maxLength = $maxLength - ($lookBack - $encodedCharPos);
                    $foundSplitPos = true;
                } elseif ($dec < 192) { // Middle byte of a multi byte character, look further back
                    $lookBack += 3;
                }
            } else {
                // No encoded character found
                $foundSplitPos = true;
            }
        }
        return $maxLength;
    }
    public function GetMIME() {
        $result = '';
        
        if(empty($this->attachements)) {
            $result .= $this->HeaderLine('Content-Transfer-Encoding', $this->Encoding);
            $result .= sprintf("Content-Type: %s; charset=\"%s\"%s", $this->ContentType, $this->CharSet, $this->LE);
        } else {
            $result .= sprintf("Content-Type: %s;%s\ttype=\"text/html\";%s\tboundary=\"%s\"%s", 'multipart/related', $this->LE, $this->LE, $this->boundary, $this->LE);

        }
        
        return $result;
    }
    public function HeaderLine($name, $value) {
        return $name . ': ' . $value . $this->LE;
    }
    public function TextLine($value) {
        return $value . $this->LE;
    }
    public static function RFCDate() {
        $tz = date('Z');
        $tzs = ($tz < 0) ? '-' : '+';
        $tz = abs($tz);
        $tz = (int)($tz/3600)*100 + ($tz%3600)/60;
        $result = sprintf("%s %s%04d", date('D, j M Y H:i:s'), $tzs, $tz);
        
        return $result;
    }
    /**
     * 记录错误信息，并返回false
     */
    private function error($str = '') {
        //$this->_log->warn($str);
        $this->err_str = $str;
        return false;
    }
    public function __get($key) {
        $CI =& get_instance();
        
        return $CI->$key;

    }
}
