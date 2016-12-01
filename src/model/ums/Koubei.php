<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Koubei extends \DB_Query {

    protected $dbResource = 'miagroupums';
    //口碑
    protected $tableKoubei = 'koubei';
    protected $indexKoubei = array('id', 'item_id', 'user_id', 'rank_score', 'order_id', 'subject_id', 'create_time');
    //口碑相关蜜芽贴
    protected $tableKoubeiSubjects = 'koubei_subjects';
    protected $tableKoubeiItem = 'group_subject_point_tags';
    protected $indexKoubeiSubjects = array('subject_id', 'item_id', 'user_id', 'is_audited', 'create_time');
    //口碑申诉
    protected $tableKoubeiAppeal = 'koubei_appeal';
    protected $indexKoubeiAppeal = array('koubei_id', 'supplier_id', 'appeal_time');
    
    /**
     * 查询口碑表数据
     */
    public function getKoubeiData($cond, $offset = 0, $limit = 50, $orderBy = '') {
        $this->tableName = $this->tableKoubei;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //检查是否使用索引，没有索引强制加
            if (empty(array_intersect(array_keys($cond), $this->indexKoubei))) {
                $where[] = [':ge','created_time', date('Y-m-d H:i:s', time() - 86400 * 90)];
            }
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'status':
                        $where[] = [$k, $v];
                        if ($v == 2) {
                            $where[] = [':ne','subject_id', 0];
                        }
                        break;
                    case 'start_time':
                        $where[] = [':ge','created_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','created_time', $v];
                        break;
                    case 'comment_start_time':
                        $where[] = [':ge','comment_time', $v];
                        break;
                    case 'comment_end_time':
                        $where[] = [':le','comment_time', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        $result['count'] = $this->count($where);
        if (intval($result['count']) <= 0) {
            return $result;
        }
        $result['list'] = $this->getRows($where, '*', $limit, $offset, $orderBy);
        return $result;
    }
    
    /**
     * 查询口碑表数据
     */
    public function getKoubeiAppealData($cond, $offset = 0, $limit = 50, $orderBy = 'id desc') {
        $this->tableName = $this->tableKoubeiAppeal;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //检查是否使用索引，没有索引强制加
            if (empty(array_intersect(array_keys($cond), $this->indexKoubeiAppeal))) {
                $where[] = [':ge','appeal_time', date('Y-m-d H:i:s', time() - 86400 * 90)];
            }
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','appeal_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','appeal_time', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        $result['count'] = $this->count($where);
        if (intval($result['count']) <= 0) {
            return $result;
        }
        $result['list'] = $this->getRows($where, '*', $limit, $offset, $orderBy);
        return $result;
    }
    
    /**
     * 查询口碑相关帖子表数据
     */
    public function getSubjectData($cond, $offset = 0, $limit = 50, $orderBy = '') {
        $this->tableName = $this->tableKoubeiSubjects;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //检查是否使用索引，没有索引强制加
            if (empty(array_intersect(array_keys($cond), $this->indexKoubeiSubjects))) {
                $where[] = [':ge','create_time', date('Y-m-d H:i:s', time() - 86400 * 90)];
            }
            
            //因为连表查询，两个表中都存在帖子id，所以要选择一个作为查询条件
            if(isset($cond['subject_id'])){
                $cond['koubei_subjects.subject_id'] = $cond['subject_id'];
                unset($cond['subject_id']);
            }
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','create_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','create_time', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        
        $join = 'left join '.$this->tableKoubeiItem. ' as i on ' .$this->tableName . '.subject_id=i.subject_id or i.subject_id is null ';
        $fileds = 'distinct '. $this->tableName. '.subject_id,is_audited ';
        $result['count'] = $this->count($where, $join, $fileds);
        
        if (intval($result['count']) <= 0) {
            return $result;
        }

        $result['list'] = $this->getRows($where, $fileds, $limit, $offset, $orderBy, $join);
        return $result;
    }
    
    /**
     * 查询商家口碑表数据
     */
    public function getSupplierKoubeiData($cond, $orderBy = '') {
        $this->tableName = $this->tableKoubei;
        $result = array();
        $where = array();
        $where[] = [':gt','supplier_id', 0];
        $where[] = ['status',array(1,2)];
        //时间默认为当天
        $startTime = date('Y-m-d',time())." 00:00:00";
        $endTime = date('Y-m-d',time())." 23:59:59";
        $where[] = [':ge','created_time', $startTime];
        $where[] = [':le','created_time', $endTime];
    
        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','created_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','created_time', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
    
        $fileds = "id,supplier_id";
        $result= $this->getRows($where, $fileds, false, 0, $orderBy);
        return $result;
    }
    
    /**
     * 组装商家口碑的各计数
     */
    public function supplierKoubeiStatistics($koubeiIds){
        $koubeiStatistics = array();
        $con = array();
        if(empty($koubeiIds) || !is_array($koubeiIds)){
            return $koubeiStatistics;
        }
        $con['koubei_ids'] = $koubeiIds;
        $con['status'] = array(1,2);
        //生成口碑数
        $koubeiStatistics['koubei'] = $this->getKoubeiStatistics($con);
        $koubeiStatistics['lowscore'] = $this->getKoubeiStatistics($con,'lowscore');
        $koubeiStatistics['mscore'] = $this->getKoubeiStatistics($con,'mscore');
    
        $koubeiStatistics['deal'] = $this->getKoubeiStatistics($con,'deal');
    
        $koubeiStatistics['appeal'] = $this->getKoubeiAppealStatistics($koubeiIds,0);
        $koubeiStatistics['pass'] = $this->getKoubeiAppealStatistics($koubeiIds,1);
        $koubeiStatistics['reject'] = $this->getKoubeiAppealStatistics($koubeiIds,2);
    
        return $koubeiStatistics;
    }
    
    /**
     * 获取商家口碑相关计数
     */
    public function getKoubeiStatistics($cond,$sType=''){
        $this->tableName = $this->tableKoubei;
        $resArr = array();
        $where = array();
    
        if(empty($cond) || empty($cond['koubei_ids'])){
            return $resArr;
        }
    
        $where[] = [':gt','supplier_id', 0];
        $where[] = ['id', $cond['koubei_ids']];
    
        if(isset($con['status'])){
            $where[] = ['status', $con['status']];
        }
        switch ($sType) {
            case 'deal'://回复处理
                $where[] = ['comment_status',1];
                break;
            case 'lowscore'://差评
                $where[] = [':le','score',3];
                break;
            case 'mscore'://机选差评
                $where[] = ['machine_score',1];
                break;
            default://生成口碑
                $where[] = ['id', $cond['koubei_ids']];
        }
    
        $filed = "supplier_id, count(1) as nums";
        $groupBy = "supplier_id";
        $result = $this->getRows($where,$filed,false,0,false,false,$groupBy);
        if(!empty($result)){
            foreach($result as $value){
                $resArr[$value['supplier_id']] = $value['nums'];
            }
        }
    
        return $resArr;
    }
    
    /**
     * 获取商家口碑相关申诉计数
     */
    public function getKoubeiAppealStatistics($koubeiIds,$status){
        $this->tableName = $this->tableKoubeiAppeal;
        $resArr = array();
        $where = array();
    
        if(empty($koubeiIds) || empty($koubeiIds)){
            return $resArr;
        }
    
        $where[] = [':ne','supplier_id', 0];
        $where[] = ['koubei_id', $koubeiIds];
    
        if(isset($status)){
            $where[] = ['status', $status];
        }
    
        $filed = "supplier_id, count(1) as nums";
        $groupBy = "supplier_id";
        $result = $this->getRows($where,$field,false,0,false,false,$groupBy);
        if(!empty($result)){
            foreach($result as $value){
                $resArr[$value['supplier_id']] = $value['nums'];
            }
        }
    
        return $resArr;
    }
}