<?php
namespace mia\miagroup\Data\Active;

use Ice;

class ActiveSubjectRelation extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_active_relation';
    protected $tablePointTags = 'group_subject_point_tags';

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
    public function getBatchActiveSubjects($activeId, $type = 'all', $page=1, $limit=20) {
        $result = array();
        $offsetLimit = $page > 1 ? ($page - 1) * $limit : 0;
        $where[] = ['active_id', $activeId];
        $where[] = ['status', 1];

        if ($type == 'all') {
            $orderBy = 'subject_id desc';
        } else {
            $where[] = ['is_recommend', 1];
            if ($type == 'active_over') {
                //过期按hot值排
                $orderBy = 'hot_value desc, create_time desc';
            } else if ($type == 'recommend') {
                //未过期按发布时间排
                $orderBy = 'create_time desc';
            }
        }
        $subjectArrs = $this->getRows($where, 'subject_id', $limit, $offsetLimit, $orderBy);
        if (!empty($subjectArrs)) {
            foreach ($subjectArrs as $subject) {
                $result[] = $subject['subject_id'];
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
        if(!empty($status)) {
            $where[] = ['status',$status];
        }
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

    /**
     * 删除活动帖子关联关系(物理删除)
     * @param array $relationData
     * 影响行数
     */
    public function delSubjectActiveRelation($relationData){
        if (empty($relationData)) {
            return 0;
        }
        $where = array();
        foreach($relationData as $key=>$val){
            $where[] = [$key,$val];
        }
        $res = $this->delete($where, FALSE, 1);
        return $res;
    }

    /*
     * 获取活动发帖用户排行
     * */
    public function getActiveSubjectsRank($active_id, $limit = 20, $offset = 0)
    {
        $groupBy = 'user_id';
        $orderBy = 'count(subject_id) desc';
        $where = [];
        $where[] = ['active_id', $active_id];
        $where[] = ['status', 1];
        $field = 'user_id, count(subject_id) as subject_count';
        $res = $this->getRows($where, $field, $limit, $offset, $orderBy, $join = FALSE, $groupBy);
        return $res;
    }

    /*
     * 获取活动用户发帖列表及发帖数
     * */
    public function getActiveUserSubjectInfos($active_id, $user_id = 0, $status = [1], $limit = 20, $offset = 0, $conditions = [])
    {
        $return = ['list' => [], 'count' => 0];
        if(empty($active_id)) {
            return $return;
        }
        $where = [];
        $where[] = ['active_id', $active_id];
        if (!empty($status)) {
            $where[] = array('status', $status);
        }
        $orderBy = 'id DESC';
        if(!empty($user_id)) {
            $where[] = ['user_id', $user_id];
        }
        if(!empty($conditions['s_time'])) {
            // 开始时间
            $where[] = [':ge', 'create_time', $conditions['s_time']];
        }
        if(!empty($conditions['e_time'])) {
            // 结束时间
            $where[] = [':le', 'create_time', $conditions['e_time']];
        }
        if(!empty($conditions['is_qualified'])) {
            // 审核状态
            $where[] = ['is_qualified', $conditions['is_qualified']];
        }
        if($conditions['sort_type'] == 'user_first') {
            // 排序状态
            $orderBy = 'id ASC';
        }
        if($conditions['type'] == 'count') {
            $count = $this->count($where);
            $return['count'] = $count;
            return $return;
        }
        // 活动关联帖子列表
        $field = 'active_id, subject_id, is_qualified, create_time';
        $res = $this->getRows($where, $field, $limit, $offset, $orderBy);
        $return['list'] = $res;
        return $return;
    }

    /*
     * 活动关联帖更新审核状态
     * */
    public function updateActiveSubjectVerify($setData, $id)
    {
        if (empty($setData)) {
            return false;
        }
        $where = [];
        $where[] = ['id', $id];

        $set_data = [];
        foreach ($setData as $k => $v) {
            $set_data[] = [$k, $v];
        }
        $data = $this->update($set_data, $where);
        return $data;
    }

    /*
     * 根据关联id批量获取关联帖子信息
     * */
    public function getActiveSubjectRelation($ids, $status = [1], $conditions = [])
    {
        if(empty($ids)) {
            return [];
        }
        $where = [];
        $where[] = ['id', $ids];
        if(!empty($status)) {
            $where[] = ['status', 1];
        }
        if(!empty($conditions['is_qualified'])) {
            $where[] = ['is_qualified', $conditions['is_qualified']];
        }
        $fields = 'user_id, subject_id, active_id, is_qualified, create_time';
        $res = $this->getRows($where, $fields);
        return $res;
    }

    /*
     * 获取商品对应的帖子关联信息
     * */
    public function getItemSubjectRelation($active_id, $item_ids, $status = [1], $limit = 20, $offset = 0, $conditions = [])
    {
        if(empty($item_ids) || empty($active_id)) {
            return [];
        }
        $where = $return = [];
        $where[] = [$this->tablePointTags.'.item_id', $item_ids];
        $where[] = [$this->tableName.'.active_id', $active_id];
        $orderBy = $this->tableName.'.id DESC';
        if(!empty($status)) {
            $where[] = [$this->tableName.'.status', $status];
        }
        if(!empty($conditions['is_qualified'])) {
            $where[] = [$this->tableName.'.is_qualified', $conditions['is_qualified']];
        }
        if(!empty($conditions['user_id'])) {
            $where[] = [$this->tableName.'.user_id', $conditions['user_id']];
        }
        if($conditions['sort_type'] == 'user_first') {
            $orderBy = $this->tableName.'.id ASC';
        }
        $fields = $this->tablePointTags.'.item_id,'.$this->tablePointTags.'.subject_id';
        $join = 'left join '.$this->tablePointTags.' on '.$this->tableName.'.subject_id = '.$this->tablePointTags.'.subject_id';
        $res = $this->getRows($where, $fields, $limit, $offset, $orderBy, $join);
        if(empty($res)) {
            return [];
        }
        foreach($res as $v) {
            $return[$v['item_id']][] = $v['subject_id'];
        }
        return $return;
    }
}


