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
                $cond['i.subject_id'] = $cond['subject_id'];
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
        
        $join = 'left join '.$this->tableKoubeiItem. ' as i on ' .$this->tableName . '.subject_id=i.subject_id ';
        $fileds = 'distinct i.subject_id,is_audited ';
        $result['count'] = $this->count($where, $join, $fileds);
        
        if (intval($result['count']) <= 0) {
            return $result;
        }

        $result['list'] = $this->getRows($where, $fileds, $limit, $offset, $orderBy, $join);
        return $result;
    }
    
    /**
     * 口碑蜜芽贴中的通过状态
     */
    public function updateKoubeiSubject($subjectData,$id) {
        $this->tableName = $this->tableKoubeiSubjects;
        $setData = array();
        $where = array();
        $where[] = ['id', $id];
        $setData[] = ['is_audited', $subjectData['status']];
        $result = $this->update($setData, $where);
        return $result;
    }
    
    /**
     * 同步口碑蜜芽贴
     * @param array $subjectInfo
     */
    public function saveKoubeiSubject($subjectInfo)
    {
        $this->tableName = $this->tableKoubeiSubjects;
        $result = $this->insert($subjectInfo);
        return $result;
    }
    
    public function saveKoubeiSubjectItem($koubeiItem){
        $this->tableName = $this->tableKoubeiItem;
        $result = $this->insert($koubeiItem);
        return $result;
    }
    
}