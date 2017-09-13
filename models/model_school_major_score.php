<?php

/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2017/5/31
 * Time: 14:54
 */
class model_school_major_score extends Gsystem_model
{

    private $year = 2011; // 年份起始点
    private $specialties_major_id = 4000000; // 专科ID起始点
    private $none_rank = 9999999999; // 未知排名

    /*
	 * 根据学校ID获取学校详情
	 * @param int $region_id 省份ID
	 * @param int $score 分数
	 * @param int $type 学校类型，0是非211,985 1是211,2是985 3是211 985 其他都是不需要判断
	 * @param int $studenttype_id 科目分类 -1 所有，0 其他，1 理科，2 文科，3 综合，4 艺术类，5体育类
	 * @param int $order 排序 1 录取分从高到低，2 录取分从低到高,不传或其他默认e橙排序
	 * @param int $page 页数
	 * @param int $page_size 每页条数
     * return array
	 */
    public function getSchoolList($region_id, $score, $type = -1, $studenttype_id = 1, $order = 3, $page = 1, $page_size = 10){
        if(empty($region_id) || empty($score)) return [];

        $cache_key = 'SCHOOL_' . md5('getSchoolList_' . serialize([$region_id, $score, $type, $studenttype_id, $order, $page, $page_size]));
        if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
            // 科目分类
            $studenttype_sql = '';
            if($studenttype_id != -1){
                $studenttype_sql = " and tt.studenttype_id = {$studenttype_id} ";
            }

            $sql_count = "SELECT count(1) total FROM"
                . " (SELECT school_id, min(min_score) AS min_score"
                . " FROM school_major_score tt WHERE tt.region_id = {$region_id} "
                . $studenttype_sql . " AND tt. YEAR > {$this->year} AND min_score <= {$score}"
                . " AND school_id > 0 GROUP BY school_id ) t1,schools t2 WHERE t1.school_id = t2.id";

            $sql = "SELECT t1.school_id,t1.major_total,t1.min_score,t2.name_cn school_name,t2.type,t2.edudirectly,IFNULL(t3.rank1,9999999999) rank FROM"
                . " (SELECT school_id, count(DISTINCT major_id) major_total, min(min_score) AS min_score"
                . " FROM school_major_score tt WHERE tt.region_id = {$region_id} "
                . $studenttype_sql . " AND tt. YEAR > {$this->year} AND min_score <= {$score}"
                . " AND school_id > 0 GROUP BY school_id ) t1 INNER JOIN schools t2 on t1.school_id = t2.id LEFT JOIN school_rank t3 on t1.school_id = t3.school_id";

            // 学校类型
            switch($type){
                case 0:
                    $sql .= ' and type = 0';
                    $sql_count .= ' and type = 0';
                   break;
                case 1:
                    $sql .= ' and type = 1';
                    $sql_count .= ' and type = 1';
                    break;
                case 2:
                    $sql .= ' and type = 2';
                    $sql_count .= ' and type = 2';
                    break;
                case 3:
                    $sql .= ' and type in (1,2)';
                    $sql_count .= ' and type in (1,2)';
                    break;
                default:
                    $sql .= '';
                    $sql_count .= '';
            }

            // 排序
            switch($order){
                case 1:
                    $sql .= ' order by min_score desc';
                    break;
                case 2:
                    $sql .= ' order by min_score asc';
                    break;
                default:
                    $sql .= ' order by min_score desc,rank';
            }

            $total = $this->getListBysql($sql_count);
            $list = [];
            $limit_page = ($page - 1) * $page_size;
            if($total[0]['total'] > 0){
                $sql .= ' limit ' . $limit_page . ',' . $page_size ;
                $data = $this->getListBysql($sql);

                foreach($data as $val){
                    $list[] = [
                        'school_id'         => $val['school_id'],
                        'major_total'       => $val['major_total'],
                        'min_score'         => $val['min_score'],
                        'school_name'       => $val['school_name'],
                        'type'              => $val['type'],
                        'edudirectly'       => intval($val['edudirectly']),
                        'rank'              => $val['rank'] == $this->none_rank ? 0:$val['rank'],
                    ];
                }

            }

            $result = [
                'total' => $total[0]['total'],
                'current_page' => $page,
                'page_size' => $page_size,
                'has_undergraduate' => 1,
                'list' => !empty($list) ? $list: [],
            ];

            $this->cache->memcached->save($cache_key, json_encode($result), 604800);
        }

        return $result;
    }

    /*
     * 获取分页数据
     * @param array $data 数据
     * @param int $page 页数
     * @param int $page_size 每页条数
     * return array
     */
    public function getPageList($data, $page = 1, $page_size = 10){
        if(empty($data) || !is_array($data)) return [];
        $limit = $page_size;
        $total = count($data);
        if ($total < $page* $page_size){
            $limit = $total - ($page-1) * $page_size;
        }
        return array_values(array_slice($data, ($page-1) * $page_size, $limit));
    }

    /*
     * 根据SQL获取列表
     * @param $string $sql sql
     * return []
     */
    public function getListBysql($sql){
        if(empty($sql)) return [];
        $cache_key = 'SCHOOL_' . md5('getListBysql' . serialize($sql));
        if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
            $list = $this->parse_sql($sql);
            $result = !empty($list) ? $list: [];
            $this->cache->memcached->save($cache_key, json_encode($result), 604800); // 缓存一周
        }

        return $result;
    }


    /*
	 * 根据学校ID获取学校详情
	 * @param int $region_id 省份ID
	 * @param int $score 分数
	 * @param int $school_id 学校ID
     * @param int $studenttype_id 科目分类 -1 所有，0 其他，1 理科，2 文科，3 综合，4 艺术类，5体育类
     * return array
	 */
    public function getMajorList($region_id = '', $score = '', $school_id, $studenttype_id = -1){
        if(empty($school_id)) return [];

        $cache_key = 'SCHOOL_' . md5('getMajorList' . serialize([$region_id, $score, $school_id, $studenttype_id]));
        if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
            $sql  = "SELECT DISTINCT tt.major_id,min(min_score) min_score FROM school_major_score tt ";

            $where = ' where ';
            $and = '';
            if($region_id != -10000000){
                $sql .= $where . " tt.region_id = {$region_id} ";
                $where = '';
                $and = ' and ';
            }

            if($score != -10000000){
                $sql .= $where . $and . " min_score <= {$score}";
                $where = '';
                $and = ' and ';
            }

            // 科目分类
            if($studenttype_id != -1){
                $sql .= $where . $and . " tt.studenttype_id = {$studenttype_id}";
                $where = '';
                $and = ' and ';
            }

            $sql .= $where . $and . " school_id = {$school_id} and tt.year > {$this->year} GROUP BY major_id";

            $list = $this->getListBysql($sql);

            $result = [
                'total' => count($list),
                'list' => array_values($list),
            ];

            $this->cache->memcached->save($cache_key, json_encode($result), 604800);
        }

        return $result;
    }


    /*
	 * 根据专业ID获取学校详情
	 * @param int $major_id 专业ID
	 * @param int $order 排序 1 名次从高到低，2 名次从低到高 其他默认1
	 * @param int $page 页数
	 * @param int $page_size 每页条数
     * return array
	 */
    public function getSchoolsByMajorId($major_id, $order = 3, $page = 1, $page_size = 10){
        if(empty($major_id)) return [];

        $cache_key = 'SCHOOL_' . md5('getSchoolsByMajorId_' . serialize([$major_id, $order, $page, $page_size]));
        if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
            $sql = "select IFNULL(sr.rank1,9999999999) rank,sm.school_id,sm.schoolname,s.type,s.edudirectly from"
                . " school_major_score sm LEFT JOIN school_rank sr on sr.school_id = sm.school_id INNER JOIN schools s"
                . " on s.id = sm.school_id where sm.major_id = {$major_id} and sm.year >={$this->year} GROUP BY sm. school_id";

            // 排序
            switch($order){
                case 1:
                    $sql .= ' order by rank asc';
                    break;
                case 2:
                    $sql .= ' order by rank desc';
                    break;
                default:
                    $sql .= ' order by rank asc';
            }

            $list = $this->getListBysql($sql);

            $data = [];
            foreach($list as $val){
                $data[] = [
                    'school_id'         => $val['school_id'],
                    'school_name'       => $val['schoolname'],
                    'rank'              => $val['rank'] == $this->none_rank ? 0:$val['rank'],
                    'type'              => $val['type'],
                    'edudirectly'       => intval($val['edudirectly']),
                ];
            }

            $result = [
                'total' => count($data),
                'current_page' => $page,
                'page_size' => $page_size,
                'list' => $this->getPageList($data, $page, $page_size),
            ];

            $this->cache->memcached->save($cache_key, json_encode($result), 604800);
        }

        return $result;
    }


    /*
	 * 获取历年学校专业分数数据
	 * @param int $school_id 学校ID
	 * @param int $major_id 专业ID
	 * @param int $region_id 省份ID
	 * @param int $studenttype_id 文理科
	 * @param int $year 年份
     * return array
	 */
    public function getSchoolMajorScores($school_id, $major_id = 0, $region_id = '', $studenttype_id = '', $year = 2012){
        $cache_key = 'SCHOOL_' . md5('getSchoolMajorScores' . serialize([$school_id, $major_id, $region_id, $studenttype_id, $year]));
        if(empty($result = json_decode($this->cache->memcached->get($cache_key), true))) {
            $sql = "select * from school_major_score where school_id = $school_id and major_id = $major_id and `year` >= $year ";

            if(!empty($region_id)) $sql .= " and region_id = $region_id ";
            if($studenttype_id != -1) $sql .= " and studenttype_id = $studenttype_id ";

            $sql .= ' order by `year`';

            $result = $this->getListBysql($sql);
            $this->cache->memcached->save($cache_key, json_encode($result), 604800);
        }

        return $result;
    }
}