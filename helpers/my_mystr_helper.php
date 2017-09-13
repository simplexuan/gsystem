<?php 
function str_unique($str, $delimiter=',') {
    $res  = array();
    $temp = explode($delimiter, $str);
    foreach($temp as $v){
        if (empty($v)) continue;
        $res[$v] = $v;
    }
    return implode( $delimiter, $res);
}
function str_to_array($str, $delimiter=','){
    $res  = array();
    $temp = explode($delimiter, $str);
    if (empty($temp)) return array();
    foreach($temp as $v){
        if (empty($v)) continue;
        $res[$v] = $v;
    }
    return $res;
}
function ids_to_name($ids, $map_id2name){
    $id_list = str_to_array($ids);
    $id2name = array();
    foreach ($id_list as $id){
        $id2name[] = $map_id2name[$id];
    }
    return implode('/', $id2name);
}
function user2team($ids, $user2team, $map_id2name){
    $id_list = str_to_array($ids);
    $id2name = array();
    foreach ($id_list as $id){
        $name = $map_id2name[$user2team[$id]];
        $id2name[$name] = $name;
    }
    return implode('/', $id2name);
}
function show_is_contract($is_contract){
    $config = &get_config();
    return $config['dictionaries']['is_contract'][$is_contract];
}
function my_htmlspecialchars(&$v, $key){
   $v = htmlspecialchars($v, ENT_QUOTES);
}

/**
 *
 * gbk转utf8
 * @param unknown_type $filename 源文件名
 * @throws Exception
 * return $newname  新文件名
 */
function gbk2utf8($filename){
	if ( ! $new_str = file_get_contents($filename)){
		return FALSE;
	}
	$new_str = str_replace("\r\n", "\n", $new_str);
	$new_str = iconv("gbk", "utf-8//IGNORE", $new_str);
	if ( ! file_put_contents($filename, $new_str)){
		return FALSE;	
	}
	return $filename;
}

/**
 *
 * Enter description here 解析手机
 * @param mixed $phone
 * return 正常格式的手机号或者false
 */
function parse_phone($phone){
	//提取手机号形如 (+86)151-5811*21 77格式的
	if (preg_match("/[0-9A-Za-z_\-\* \(\)\+\/]+/",$phone,$matches)){
		$arr = str_split($matches[0]);
		foreach ($arr as $k=>$v){
			if ( ! preg_match("/\d+/", $v)){
				unset($arr[$k]);
			}
		}
		$phone = implode("", $arr);
	}

	if (preg_match("/1[3458][0-9]\d{8}/",$phone,$matches2)){
		return $matches2[0];
	}else{
		return FALSE;
	}
}


/**
 *
 * Enter description here 提取浮点数
 * @param $str
 * @throws Exception
 * return mixed
 */
function parse_float($str){
	if (preg_match("/[0-9\.]+/", $str,$matches)){
		return $matches[0];
	}
	return "";
}

/**
 *
 * Enter description here 合并相同键值的数组 以$seperator分割
 * @param $arr 3维数组
 * @param $seperator  分隔符
 * @throws Exception
 */
function array_plus($arr, $seperator){
	$arr2 = array();
	foreach ($arr as $k=>$v){
		foreach ($v as $vk => $vv) {
			foreach ($vv as $vvk => $vvv) {
				$arr2[$vk][$vvk] = isset($arr2[$vk][$vvk]) ? ($arr2[$vk][$vvk] . $seperator . $vvv): $vvv;
				
				$arr2[$vk][$vvk] = trim($arr2[$vk][$vvk],$seperator);
			}
		}
	}
	return $arr2;
}

function  file_ext($file_name){
    if (is_dir($file_name)) return '';
    $file_real_name = substr($file_name, strrpos($file_name, '/') +1);
    if ( strrpos($file_real_name, '.') === FALSE) return '';
    return substr($file_real_name, strrpos($file_real_name, '.')); 
}
function file_list($dir, $ext='') {
    $file_list = array();
    if(is_dir($dir)){  
        if($dir_handle = opendir($dir)){  
            while (false !== ($file_name = readdir($dir_handle)) ){  
                if($file_name=='.' or $file_name =='..'){  
                    continue;  
                }else{  
                    $file_type = filetype($dir.'/'.$file_name);  
                    if('dir' == $file_type){  
                        $file_list = array_merge($file_list, file_list($dir.'/'.$file_name, $ext));  
                    }elseif('file' == $file_type){  
                        if ($ext == ''){
                            $file_list[$dir.'/'.$file_name] = $dir.'/'.$file_name ; 
                        }else{
                            $raw_ext = file_ext( $dir.'/'.$file_name);
                            if (strcasecmp ( $raw_ext, $ext) ==0){
                                $file_list[$dir.'/'.$file_name] = $dir.'/'.$file_name ;
                            }
                        }

                    }  
                } // if  
            }// while  
        }// if  
    }elseif(is_file($dir)){  
        if ($ext == '')
            $file_list[$dir.'/'.$file_name] = $dir.'/'.$file_name ; 
        else{
            $raw_ext = file_ext( $dir.'/'.$file_name);
            if (strcasecmp ( $raw_ext, $ext) ==0)
                $file_list[$dir.'/'.$file_name] = $dir.'/'.$file_name ;
        }
        //  $file_list[$dir] = $dir;  
    }  
    return $file_list;
}
function  my_html_entity_decode($str){
    return html_entity_decode ($str, ENT_COMPAT, 'UTF-8');
}
function my_strcat($suffix, $str){
    return sprintf('%s%s', $str, $suffix);
}
function my_number_replace($str){
    $patterns = array();
    $patterns[0] = '/一/';
    $patterns[1] = '/二/';
    $patterns[2] = '/三/';
    $patterns[3] = '/四/';
    $patterns[4] = '/五/';
    $patterns[5] = '/六/';
    $patterns[6] = '/七/';
    $patterns[7] = '/八/';
    $patterns[8] = '/九/';
    $patterns[9] = '/十/';
    $replacements = array();
    $replacements[0] ='1';
    for($i=1; $i<9; $i++){
        $replacements[$i] = $i+1;
    }
    $replacements[9]  = '0';
    return  preg_replace($patterns, $replacements, $str);
}
function my_to_k($str){
    return (float)($str)*1.0/1000;
}
function my_to_gender($str){
    $map = array('男'=>'M', '女'=>'F');
    return isset($map[$str])?   $map[$str] : '';
}
function my_to_degree($str){
    $map = array(
            '本科'     => 1,
            '211本科'  => 1,
            '普通本科' => 1,
            '硕士'     => 2,
            '研究生'   => 2,
            //'MBA'      => 2,
            '博士'     => 3,
            '专科'     => 4,
            '大专'     => 4,
            'MBA'      => 6,
            //'中专'     => 4,
            '高中或同等学历'     => 89,
            '高中'     => 89,
            '中专'     => 90,
            '中技'     => 90,
            '技校'     => 90,
            '职高'     => 90,
            );
    return isset($map[$str])?   $map[$str] : '';
}
function my_expect_city($str){
    $results = array();
    $cities = explode('<br/>', $str);
    foreach ($cities as $city){
        $temp = explode('-', $city);
        if (sizeof($temp)>1){
            $results[] = $temp[1];
        }else{
            $results[] = $city;
        }
    }
    return implode(',', $results);
}
function my_to_overseas($str){
    $map = array('有'=>'Y', '无'=>'N');
    return isset($map[$str])?   $map[$str] : 'U';
}
function my_marital($str){
    $map = array('已婚'=>'Y', '未婚'=>'N');
    return isset($map[$str])?   $map[$str] : 'U';
}
function my_current_status($str){
    $map = array('我正在主动找工作，可快速到岗'=>'1',
            '我目前在职，正考虑换个新环境（如有合适的工作机会，到岗时间一个月左右）'=>1,
            '如果有更好的机会，我愿意考虑'=>'2',
            '目前不找工作，看看行情'=>3);
    return isset($map[$str])?   $map[$str] : '0';
}
function work_sort($w1, $w2){
    preg_match_all('/(\d+)/', $w1['start_time'], $matches);
    $w1_start_time = count($matches[1])==2 ? vsprintf('%04d%02d', $matches[1]): '';
    preg_match_all('/(\d+)/', $w2['start_time'], $matches);
    $w2_start_time = count($matches[1])==2 ? vsprintf('%04d%02d', $matches[1]): '';

    if (strcmp($w1_start_time, $w2_start_time) ==0 ){
        if (strcmp($w1['corporation_name'], $w2['corporation_name']) ==0) return 0;
        else{
            return strcmp($w1['corporation_name'], $w2['corporation_name']) >0 ? -1 : 1;
        }
    }

    return strcmp($w1_start_time, $w2_start_time) <0? 1 : -1;
}
/**
 * education_sort 
 * 
 * @param mixed $e1 
 * @param mixed $e2 
 * @access public
 * @return mixed
 */
function education_sort($e1, $e2){
    preg_match_all('/(\d+)/', $e1['start_time'], $matches);
    $e1_start_time = count($matches[1])==2 ? vsprintf('%04d%02d', $matches[1]) : '';
    preg_match_all('/(\d+)/', $e2['start_time'], $matches);
    $e2_start_time = count($matches[1])==2 ? vsprintf('%04d%02d', $matches[1]) : '';
    if (strcmp($e1_start_time, $e2_start_time) ==0 ){
        if (strcmp($e1['school_name'], $e2['school_name']) ==0) return 0;
        else{
            return strcmp($e1['school_name'], $e2['school_name']) >0 ? -1 : 1;
        }
    }

    return strcmp($e1_start_time, $e2_start_time) <0? 1 : -1;
}
function project_sort($e1, $e2){
    preg_match_all('/(\d+)/', $e1['start_time'], $matches);
    $e1_start_time = count($matches[1])==2 ? vsprintf('%04d%02d', $matches[1]) : '';
    preg_match_all('/(\d+)/', $e2['start_time'], $matches);
    $e2_start_time = count($matches[1])==2 ? vsprintf('%04d%02d', $matches[1]) : '';
    if (strcmp($e1_start_time, $e2_start_time) ==0 ){
        if (strcmp($e1['name'], $e2['name']) ==0) return 0;
        else{
            return strcmp($e1['name'], $e2['name']) >0 ? -1 : 1;
        }
    }

    return strcmp($e1_start_time, $e2_start_time) <0? 1 : -1;
}
function array2str($arrParam, $strDelimit='=', $strJoin=','){
    if (!is_array($arrParam)) return $arrParam;
    $arrItems = array();

    foreach($arrParam as $strKey=>$strVal)
    {
        if (is_array($strVal)){
            $arrItems[] = sprintf("(%s)", array2str($strVal, $strDelimit, $strJoin));
        }else 
            $arrItems[] = sprintf("%s%s%s", $strKey, $strDelimit, $strVal);
    }
    return implode($strJoin, $arrItems);
}
function custom_basic(&$basic, $key,  &$config) {
    $encrypt_fields = array('qq', 'msn','email', 'sina','ten', 'tel', 'phone');
    $result    = array('is_add_v'=>$basic['is_add_v'], 'is_validate'=>$basic['is_validate'], 
            'is_public'=>$basic['is_public'],'user_id'=> $basic['user_id']
            );
    $double_fields  = $config['double_fields'];
    $custom_fields  = $config['custom_fields'];
    $cities         = $config['cities']; 
    $user_id        = $config['user_id'];
    foreach($custom_fields as $custom_field=>$field_name){
        if ($custom_field  == 'is_remark' && $field_name =='N') continue;
        if ($custom_field  == 'is_remark' && $field_name =='Y'){
            $result['remarks'] = isset($basic['remarks']) ? $basic['remarks']: array() ;
            continue;
        }
        if ($custom_field  == 'updated_at'){
            $result['updated_at'] = date('ymd', strtotime($basic['updated_at']));
            continue;
        }
        if (in_array($custom_field, $double_fields)){
            list($province, $area) = explode(',', $custom_field);
            $province = trim($province);
            $area = trim($area);
            $sub_item = array();
            if (isset($cities[$basic[$area]])){
                $sub_item[] = $cities[$basic[$area]];
            }
            if (isset($cities[$basic[$province]])){
                $sub_item[] = $cities[$basic[$province]];
            }
            $result[$province]  = implode(' ', $sub_item);
            unset($sub_item);
        }elseif(strrpos($custom_field, ',')) {
            list($from, $to) = explode(',', $custom_field);
            $from = trim($from);
            $to   = trim($to);
            $sub_item = array();
            if (intval($basic[$from])>0){
                $sub_item[]  = $basic[$from];
            }
            if (intval($basic[$to])>0){
                $sub_item[]  = $basic[$to];
            }
            $result[substr($from,0,-5)] = implode('-', $sub_item) ? implode('-', $sub_item)."K": '';
        }else{
            if ($user_id ==1 || $basic['user_id'] == $user_id ||  $basic['editor_id'] == $user_id){
                $result[$custom_field] =  isset($basic[$custom_field])? $basic[$custom_field]: '';
            }else{
                $result[$custom_field] =  isset($basic[$custom_field])? $basic[$custom_field]: '';
                if ($basic['is_public'] =='Y' && in_array($custom_field, $encrypt_fields)){
                    for($i=3; $i < strlen($result[$custom_field]) && $i<16; $i+=2) {
                        $result[$custom_field][$i] = '*'  ;
                    }
                }
            }
        }

    }
    $basic = $result ;
}
function custom_show_fields($fields, $double_fields, $from_to_fields){
    $results = array();
    foreach ($fields as $key=>$value){
        if (in_array($key, $double_fields)){
            $new_key = substr($key, 0, strpos($key, ','));
        }elseif(in_array($key, $from_to_fields)){
            $new_key = substr($key, 0, strpos($key, '_from,'));
        }else{
            $new_key  = $key;
        }
        $results[$new_key] = $value; // array('key'=>$new_key, 'name'=>$value);
    }
    return $results;
}
function my_addcslashes(&$s, $key){
    $s = addslashes($s);
}
function my_compare_func($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a > $b)? 1:-1;
}
function my_array_diff_uassoc($new, $old, $filter=array(), $default=array(), $diff_keys=array(), &$new1=array(),&$old1=array()){
    $diffs  = array();
    foreach ($filter as $field){
        unset($new[$field]);
        unset($old[$field]);
    }
    $new1 =array();
    $old1 =array();
    if ($diff_keys){
        foreach ($diff_keys as $key){
            $new1[$key] = $new[$key];
            $old1[$key] = $old[$key];
        }
        $new = $new1;
        $old = $old1;
    }
    $result = array_diff_uassoc($new, $old, "my_compare_func");
    foreach ($result as $k=>$v){
        $diffs[$k] = $default;
        $diffs[$k]['k'] =$k;
        $diffs[$k]['f'] = json_encode(isset($old[$k])? $old[$k]: '');
        $diffs[$k]['t'] = json_encode($v);
    }
    $result = array_diff_uassoc($old, $new, "my_compare_func");
    foreach ($result as $k=>$v){
        $diffs[$k] = $default;
        $diffs[$k]['k'] = $k;
        $diffs[$k]['f'] = json_encode($v);
        $diffs[$k]['t'] = json_encode(isset($new[$k])? $new[$k]: '');
    }
    return $diffs;
}
function my_encrypt($data, $key){
    require_once APPPATH .'libraries/Xtea.php';
    $crypt = new Xtea();
    $encrypt = $crypt->encrypt($data, $key);
    return str2hex($encrypt);
}
function my_decrypt($data, $key){
    require_once APPPATH .'libraries/Xtea.php';
    $crypt = new Xtea();
    $data = hex2str($data);
    return $crypt->decrypt($data, $key);
}
/** 
 * 十六进制转字符串 
 * @param unknown_type $s 
 */ 
function hex2str($s) { 
    $r = ""; 
    for ( $i = 0; $i<strlen($s); $i += 2) 
    { 
        $x1 = ord($s{$i}); 
        $x1 = ($x1>=48 && $x1<58) ? $x1-48 : $x1-97+10; 
        $x2 = ord($s{$i+1}); 
        $x2 = ($x2>=48 && $x2<58) ? $x2-48 : $x2-97+10; 
        $r .= chr((($x1 << 4) & 0xf0) | ($x2 & 0x0f)); 
    } 
    return $r; 
}

/** 
 * 字符串转十六进制 
 * @param unknown_type $s 
 */ 
function str2hex($s) { 
    $r = ""; 
    $hexes = array ("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f"); 
    for ($i=0; $i<strlen($s); $i++) 
        $r .= ($hexes [(ord($s{$i}) >> 4)] . $hexes [(ord($s{$i}) & 0xf)]); 
    return $r; 
} 
function my_array_unique(&$arr, $fields=array()){
    my_sort($arr, $fields);
    $a = array(array_shift($arr));
    $max_a_idx=0;
    foreach ($arr as $key=>$v){
        $max_a_idx++;
        $idxs = my_in_array($v, $a, $fields);
        if (empty($idxs)){
            array_push($a, $v);
        }else{
            array_push($a, $v);
            array_push($idxs, $max_a_idx);
            $max_idx =  max_array($a, $idxs, $fields);
            foreach ($idxs as $idx){
                if ($idx != $max_idx) unset($a[$idx]);
            }
        }

    }
    $arr=$a;
}
function my_sort(&$a, $field=array()){
    $num_arr = array();
    foreach ($a as $key=>$value){
        $num = my_compute_items_num($value, $field);
        if (isset($num_arr[$num])) {
            array_push($num_arr[$num], $value);
        }else{
            $num_arr[$num] = array($value);
        }
    }
    ksort($num_arr, SORT_NUMERIC);
    $new =array();
    foreach ($num_arr as $arr){
        foreach ($arr as $value){
            $new[] = $value;
            }
    }
    $a = $new;

}
function max_array($a, $idxs, $fields){
    $last_num     = 0;
    $max_idx      = FALSE ;
    foreach ($idxs as $idx){
        $num = my_compute_items_num($a[$idx], $fields);
        if ($num>$last_num){
            $max_idx  = $idx;
            $last_num = $num;
        }

    }
    return $max_idx;
}
function my_compute_items_num($arr, $fields){
    $count =0;
    foreach ($fields as $field){
        if (!isset($arr[$field] )) continue;
        if (is_numeric($arr[$field]) && intval($arr[$field]) <=0) continue;
        if ($arr[$field] == '') continue;
        $count++;
    }
    return $count;
}
function my_not_empty_keys($param, $fields){
    $keys = array();
    foreach ($param as $key=>$value){
        if (!in_array($key, $fields)) continue;
        if ($value)
          $keys[] = $key;
    }
    return $keys;
}
function my_array_cmp($a1, $a2, $fields=array()){
    $equal1 = 0;
    $equal2 = 0;
    $keys = my_not_empty_keys($a1,  $fields);
    foreach($keys as $key){
        if (!in_array($key, $fields)) continue;
        if (!isset($a1[$key]) || !isset($a2[$key])) continue;
        if ($a1[$key] != $a2[$key]){  $equal1=1; break; }
    }

    $keys = my_not_empty_keys($a2, $fields);
    foreach($keys as $key){
        if (!in_array($key, $fields)) continue;
        if (!isset($a1[$key]) || !isset($a2[$key])) continue;
        if ($a1[$key] != $a2[$key]){ $equal2=1; break; }
    }
    return ($equal1 ==1 && $equal2 == 1) ? 1 : 0 ;
}
function my_array_element($haystack, $needle){
    $idxs = array();
    foreach ($haystack as $k=>$v){
        if (my_array_cmp($v, $needle) ==0){
            $idxs[] = $k;
        }
    }
    return $idxs;

}
function my_in_array($needle, $haystack, $fields=array()){
    $res = array();
    foreach ($haystack as $k=>$v){
        if (my_array_cmp($v, $needle, $fields) ==0){
            array_push($res, $k);
        }
    }
    return $res;
}
