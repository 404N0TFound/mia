<?php
namespace mia\miagroup\Data\Active;

use Ice;

class ActiveSubjectRelation extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_active_relation';
    
    /**
     * 新增活动帖子关联信息
     * @param array $relationSetInfo 活动帖子关联信息
     * @return 成功为id/失败false
     */
    public function addActiveSubjectRelation($relationSetInfo) {
        if(empty($relationSetInfo)){
            return false;
        }
        $data = $this->insert($relationSetInfo);
        return $data;
    }
    
    /**
     * 更新活动帖子关联关系
     * @param array $relationData
     * @param int $relationId 关联id
     * @return 成功影响行数/失败false
     */
    public function updateActiveSubjectRelation($relationData, $relationId){
        if (empty($relationData) || empty($relationId)) {
            return false;
        }
        $where[] = ['id', $relationId];
        
        $setData = array();
        foreach($relationData as $key=>$val){
            $setData[] = [$key,$val];
        }
        $data = $this->update($setData, $where);
        return $data;
    }
    
    /**
     * 批量查活动帖子(全部，热门（活动进行中按推荐排序[recommend]，结束后按热度值排序[hotvalue]）)
     */
    public function getBatchActiveSubjects($activeIds, $type = 'all', $page=1, $limit=20) {
        $result = array();
        $offsetLimit = $page > 1 ? ($page - 1) * $limit : 0;
        $where[] = [$this->tableName.'.active_id',$activeIds];
        $where[] = [$this->tableName.'.status',1];
        
        if($type == 'all'){
            $orderBy = $this->tableName.'.create_time desc';
            $fileds = "*";
        }else{
            $fileds = $this->tableName. '.active_id as active_id, group_subjects.id as subject_id, '.$this->tableName. '.regulate as regulate,'.$this->tableName. '.id as id';
            $join = 'LEFT JOIN group_subjects ON  '.$this->tableName. '.subject_id = group_subjects.id';
            if($type == 'active_over'){
                $orderBy = $this->tableName. '.hot_value desc, group_subjects.update_time desc';
            }elseif($type == 'recommend'){
                $where[] = ['group_subjects.is_fine',1];
                $orderBy = 'group_subjects.top_time desc, group_subjects.update_time desc';
            }
        }
        
        $subjectArrs = $this->getRows($where,$fileds, $limit, $offsetLimit, $orderBy, $join);
        
        if (!empty($subjectArrs)) {
            foreach ($subjectArrs as $subject) {
                $result[$subject['active_id']][] = $subject;
            }
        }
        return $result;
    }
    
    /**
     * 根据帖子id批量获取活动帖子信息
     */
    public function getActiveSubjectBySids($subjectIds, $status=array(1)) {
        if (empty($subjectIds)) {
            return array();
        }
        $where[] = ['status',$status];
        $where[] = ['subject_id',$subjectIds];
    
        $activeSubjectArrs = $this->getRows($where,'*');
        $result = array();
        if (!empty($activeSubjectArrs)) {
            foreach ($activeSubjectArrs as $activeSubject) {
                $result[$activeSubject['subject_id']] = $activeSubject;
            }
        }
        return $result;
    }
    
    /**
     * 批量获取活动下帖子计数（帖子数、发帖用户数）
     *
     * @param array $activeIds
     * @return $result
     */
    public function getBatchActiveSubjectCounts($activeIds) {
        $result = array();
        
        $where[] = ['active_id', $activeIds];
        $where[] = ['status', 1];
        $groupBy = 'active_id';
        
        $field = 'active_id, count(subject_id) as img_nums, count(distinct user_id) as user_nums';
        $countRes = $this->getRows($where, $field, $limit = FALSE, $offset = 0, $orderBy = FALSE, $join = FALSE, $groupBy);
        
        if (!empty($countRes)) {
            foreach ($countRes as $count) {
                $result[$count['active_id']] = $count;
            }
        }
        return $result;
    }
}


