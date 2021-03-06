<?php
namespace mia\miagroup\Data\Subject;

use Ice;

class Subject extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subjects';

    protected $mapping = array('id' => 'i', 'title' => 's', 'text' => 's', 'image_url' => 's', 'ext_info' => 's', 'show_age' => 'i', 'user_id' => 'i', 'status' => 'i', 'is_fine' => 'i', 'fancied_count' => 'i', 'created' => 's', 'active_id' => 'i', 'comment_count' => 'i', 'share_count' => 'i', 'shield_text' => 's', 'update_time' => 's', 'regulate' => 'f', 'is_top' => 'i', 'top_time' => 's');

    /**
     * 批量查图片信息
     */
    public function getBatchSubjects($subjectIds, $status = array(1, 2)) {
        if (empty($subjectIds)) {
            return array();
        }
        $where = array();
        $where[] = array('id', $subjectIds);
        if (!empty($status)) {
            $where[] = array('status', $status);
        }
        $subjectsArrs = $this->getRows($where);
        if (empty($subjectsArrs)) {
            return array();
        }
        $result = array();
        $emojiUtil = new \mia\miagroup\Util\EmojiUtil();
        foreach ($subjectsArrs as $v) {
            if ($v['status'] == 2) { // 视频转码中按正常处理
                $v['status'] = 1;
            }
            $v['title'] = $emojiUtil->emoji_html_to_unified($v['title']);
            $v['text'] = $emojiUtil->emoji_html_to_unified($v['text']);
            $v['text'] = str_replace('&nbsp;', ' ', $v['text']);
            preg_match_all('/<p[^>]*>(?:(?!<\/p>)[\s\S])*<\/p>/si', $v['text'], $output);
            if (!empty($output[0])) {
                $tmp_text = [];
                foreach ($output[0] as $tmp) {
                    $tmp = strip_tags($tmp, '');
                    if (!empty($tmp)) {
                        $tmp_text[] = $tmp;
                    }
                }
                $tmp_text = implode("\n", $tmp_text);
                $v['text'] = $tmp_text ? $tmp_text : $v['text'];
            }
            $v['text'] = strip_tags($v['text']);
            $v['ext_info'] = json_decode($v['ext_info'], true);
            if (!is_array($v['ext_info'])) {
                $v['ext_info'] = json_decode($v['ext_info'], true);
            }
            $result[$v['id']] = $v;
        }
        return $result;
    }


    /**
     * 批量获取用户发布的帖子数
     *
     * @param type $userIds            
     * @return type
     */
    public function getBatchUserSubjectCounts($userIds) {
        $result = array();
        
        $where[] = ['user_id', $userIds];
        $where[] = ['status', 1];
        $groupBy = 'user_id';
        $field = 'user_id, count(1) as num';

        $arrRes = $this->getRows($where, $field, $limit = FALSE, $offset = 0, $orderBy = FALSE, $join = FALSE, $groupBy);
        if (!empty($arrRes)) {
            foreach ($arrRes as $res) {
                $result[$res['user_id']] = intval($res['num']);
            }
        }
        return $result;
    }

    /*
     * 增加帖子
     */
    public function addSubject($insertData) {
        $data = $this->insert($insertData);
        return $data;
    }

    /**
     * 更新帖子
     *
     * @param type $subjectId   int/aray      
     * @param type $setData            
     * @param type $where            
     * @param type $orderBy            
     * @param type $limit            
     * @return int
     */
    public function updateSubject($setData, $subjectId) {
        if (empty($setData)) {
            return false;
        }
        $where[] = ['id', $subjectId];
        $data = $this->update($setData, $where);
        return $data;
    }
    
    /**
     * 设置图片为推荐图片
     * @param array $subjects
     * @param int $setStatus
     * @return boolean
     */
    public function setSubjectRecommendStatus($ids, $setStatus = 1)
    {    
        $setData[] = ['update_time',date('Y-m-d H:i:s')];
        $setData[] = ['is_fine',$setStatus];   
        $where[] = ['id',$ids];
        
        $affectRow = $this->update($setData,$where);
        if ($affectRow) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 更新帖子计数
     */
    public function updateSubjectCount($subjectId, $num, $countType='view_num') {
        if (empty($subjectId)) {
            return false;
        }

        $sql = "update $this->tableName set $countType = $countType+$num where id = $subjectId";
        $result = $this->query($sql);
        return $result;
    }
    
    /**
     * 获取用户的帖子ID
     */
    public function getSubjectInfoByUserId($userId, $currentId = 0, $iPage = 1, $iPageSize = 20, $conditions = []){
        
        $offsetLimit = $iPageSize * ($iPage - 1);
        $where[] = ['user_id',$userId];
        if(!empty($conditions['source'])) {
            $where[] = ['source', $conditions['source']];
        }
        if ($userId == $currentId) { 
            //查看自己空间显示正在转码中的视频
            $where[] = ['status',array(1,2)];
            $orderBy = 'id DESC';
            $result = $this->getRows($where,'id',$iPageSize,$offsetLimit,$orderBy);
        } else {
            $where[] = ['status',1];
            $orderBy = 'id DESC';
            $result = $this->getRows($where,'id',$iPageSize,$offsetLimit,$orderBy);
        }
        $subject_id = array_column($result, 'id');
        return $subject_id;
    }
    
    /**
     * 精选帖子的ids
     * @param int $iPage 页码
     * @param int $iPageSize 一页多少个
     * @return array 帖子ids
     */
    public function getRrecommendSubjectIds($iPage=1, $iPageSize=21){
        $offsetLimit = $iPageSize * ($iPage - 1);     
        $where[] = ['status',1];
        $where[] = ['is_fine',1];
        $where[] = [':>','update_time',date("Y-m-d",strtotime("last month"))];
        $orderBy = 'top_time desc, update_time desc';
        $subjectsArrs = $this->getRows($where,'id',$iPageSize,$offsetLimit,$orderBy);
        return array_column($subjectsArrs, 'id');
    }
    
    /**
     * 删除或屏蔽帖子
     */
    public function deleteSubjects($subjectIds,$status,$shieldText=''){
        $setData = array();
        $where = array();
        //删除帖子
        $setData[] = ['status',$status];
        if(!empty($shieldText)){
            $setData[] = ['shield_text',$shieldText];
        }
        $where[] = ['id',$subjectIds];
        
        $affect = $this->update($setData,$where);
        return $affect;
    }
    
    /**
     * 批量更新帖子的数量
     */
    public function updateSubjectComment($commentNumArr){
        if(empty($commentNumArr)){
            return $this->error(500);
        }
    
        $ids = implode(',', array_keys($commentNumArr));
        $sql = "UPDATE $this->tableName set comment_count = CASE id ";
        foreach ($commentNumArr as $subjectId => $commentNums) {
            $sql .= sprintf("WHEN %d THEN %d ", $subjectId, $commentNums);
        }
        $sql .= "END WHERE id IN ($ids)";
    
        $result = $this->query($sql);
        return $result;
    }
    
    /**
     * 帖子置顶/取消置顶
     */
    public function setSubjectTopStatus($subjectIds,$status){
        //如果是取消置顶的，则需要把置顶时间更新为0
        if($status == 0){
            $top_time = '0000-00-00 00:00:00';
        }else{
            $top_time = date('Y-m-d H:i:s',time());
        }
        $setData[] = ['is_top',$status];
        $setData[] = ['top_time',$top_time];
        $where[] = ['id',$subjectIds];
        $affect = $this->update($setData,$where);
        return $affect;
    }
    
    /**
     * 获取帖子置顶数量
     */
    public function getSubjectTopNum(){
        $where[] = ['status',1];
        $where[] = ['is_fine',1];
        $where[] = ['is_top',1];
        $where[] = [':!=','top_time','0000-00-00 00:00:00'];
        $count = $this->count($where);
        return $count;
    }
    
    /**
     * UMS
     * 取消推荐
     */
    public function cacelSubjectIsFine($subjectId){
        $where[] = ['id',$subjectId];
        $setData[] = ['is_fine',0];
        $setData[] = ['update_time','0000-00-00 00:00:00'];
        $setData[] = ['is_top',0];
        $setData[] = ['top_time','0000-00-00 00:00:00'];
        
        $affect = $this->update($setData,$where);
        return $affect;
    }
    
    /**
     * 获取用户在某个时间段内的发帖排行
     */
    public function getPushlishRankByTime($user_ids, $start_time, $end_time, $conditon = [], $offset = 0, $limit = false) {
        if (!is_array($user_ids) && empty($user_ids)) {
            return array();
        }
        $where = [];
        $where[] = ['user_id', $user_ids];
        $where[] = ['status', 1];
        if (!empty($start_time)) {
            $where[] = [':ge', 'created', $start_time];
        }
        if (!empty($end_time)) {
            $where[] = [':lt', 'created', $end_time];
        }
        foreach ($conditon as $k => $v) {
            switch ($k) {
                default:
                    $where[] = [$k, $v];
            }
        }
        $field = 'user_id, count(id) as pub_count';
        $order_by = array('pub_count DESC');
        $group_by = 'user_id';
        $data = $this->getRows($where, $field, $limit, $offset, $order_by, false, $group_by);
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['user_id']] = $v['pub_count'];
            }
        }
        return $result;
    }
    
    /**
     * 获取帖子表的最大ID
     */
    public function getMaxSubjectId($condition = array()) {
        $where = [];
        foreach ($condition as $k => $v) {
            switch ($k) {
                default:
                    $where[] = [$k, $v];
            }
        }
        $data = $this->getRow($where, 'max(id)');
        return $data;
    }
    
    /**
     * 获取帖子列表
     */
    public function getSubjectList($condition, $offset = 0, $limit = 10) {
        $where = [];
        $order_by = 'id desc';
        foreach ($condition as $k => $v) {
            switch ($k) {
                case 'id_begin':
                    $where[] = [':gt', 'id', $v];
                    $order_by = 'id asc';
                    break;
                case 'start_time':
                    $where[] = [':gt', 'created', $v];
                    break;
                case 'end_time';
                    $where[] = [':le', 'created', $v];
                    break;
                default:
                    $where[] = [$k, $v];
            }
        }
        $data = $this->getRows($where, 'id', $limit, $offset, $order_by);
        $data = array_column($data, 'id');
        return $data;
    }

    /*
     * 获取plus用户素材列表
     * */
    public function getUserMaterialIds($item_ids, $user_id, $limit, $offset, $condition) {
        if(empty($item_ids) || !is_array($item_ids)) {
            return array();
        }
        $where = [];
        if(!empty($condition['source'])) {
            $where[] = ['group_subjects.source', $condition['source']];
        }
        if(!empty($condition['status'])) {
            $where[] = ['group_subjects.status', $condition['status']];
        }
        if(!empty($condition['type'])) {
            $where[] = ['group_subject_point_tags.type', $condition['type']];
        }
        if(!empty($user_id)) {
            $where[] = ['group_subjects.user_id', $user_id];
        }
        if(!empty($item_ids)) {
            $where[] = ['group_subject_point_tags.item_id', $item_ids];
        }
        $fields = 'group_subjects.id';
        $orderBy = array('group_subjects.id DESC');
        $groupBy = array('group_subjects.id');
        $join[] = 'LEFT JOIN group_subject_point_tags ON group_subjects.id = group_subject_point_tags.subject_id';
        $data = $this->getRows($where, $fields, $limit, $offset, $orderBy, $join, $groupBy);
        $res = array_column($data, 'id');
        return $res;
    }

    public function getFirstSubject($userIds, $source = 1, $need_time = false, $timeStart = "")
    {
        $where = [];
        $where[] = ['user_id', $userIds];
        $where[] = ['source', $source];
        if (!empty($timeStart)) {
            $where[] = [':gt', 'created', $timeStart];
        }

        $user_str = implode(",",$userIds);
        if (!empty($timeStart)) {
            $join = "JOIN (SELECT MIN(id) min_id FROM group_subjects WHERE `user_id` IN (" . $user_str . ") AND created > '" . $timeStart . "' AND source = " . $source . " GROUP BY user_id) subjects_b ON subjects_b.min_id = group_subjects.id";;
        } else {
            $join = "JOIN (SELECT MIN(id) min_id FROM group_subjects WHERE `user_id` IN (" . $user_str . ") AND source = " . $source . " GROUP BY user_id) subjects_b ON subjects_b.min_id = group_subjects.id";;
        }

        $data = $this->getRows($where, "id,user_id,created", FALSE, 0, "id ASC", $join);
        $return = [];
        foreach ($data as $val) {
            if ($need_time) {
                $return[$val["user_id"]] = [
                    "id" => $val["id"],
                    "time" => $val["created"]
                ];
            } else {
                $return[$val["user_id"]] = $val["id"];
            }
        }
        return $return;
    }

    /**
     * 获取用户最新发帖
     * @param $userIds
     * @return array
     */
    public function getLastSubjectsByUids($userIds)
    {
        $where = [];
        $where[] = ['user_id', $userIds];

        $groupBy = array('user_id');
        $data = $this->getRows($where, "max(id) max_id", FALSE, 0, "id DESC", FALSE, $groupBy);
        if(empty($data)) {
            return [];
        }
        $return = [];
        foreach ($data as $v) {
            $return[] = $v["max_id"];
        }
        return $return;
    }
}
