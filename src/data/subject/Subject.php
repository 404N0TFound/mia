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
        $groupBy = 'id';
        $field = 'id, count(1) as num';
        
        $arrRes = $this->getRows($where, $field, $limit = FALSE, $offset = 0, $orderBy = FALSE, $join = FALSE, $groupBy);
        
        if (!empty($arrRes)) {
            foreach ($arrRes as $res) {
                $result[$res['id']] = intval($res['num']);
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
    public function getSubjectInfoByUserId($userId, $currentId = 0, $iPage = 1, $iPageSize = 20){
        
        $offsetLimit = $iPageSize * ($iPage - 1);
        $where[] = ['user_id',$userId];
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
     * 删除帖子
     */
    public function delete($subjectId,$userId){
        //删除帖子
        $setData[] = ['status',0];
        $where[] = ['id',$subjectId];
        $where[] = ['user_id',$userId];
        $affect = $this->update($setData,$where);
        return $affect;
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
     * 根据用户ID获取帖子信息
     */
    public function getSubjectDataByUserId($subjectId, $userId, $status = array(1,2)){
        $where[] = ['id', $subjectId];
        $where[] = ['user_id', $userId];
        $where[] = ['status', $status];
        $data = $this->getRow($where);
        return $data;
    }
    
    /**
     * 获取用户的帖子
     */
    public function getSubjectsByUid($userId){
        if (empty($userId)) {
            return array();
        }
        $where = array();
        $where[] = ['user_id',$userId];
        $where[] = ['status',1];
        $result = $this->getRows($where);
        return $result;
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
    
}
