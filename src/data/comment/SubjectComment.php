<?php
namespace mia\miagroup\Data\Comment;

class SubjectComment extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_comment';

    protected $mapping = array();

    /**
     * 根据commentIds批量获取评论信息
     */
    public function selectCommentByIds($commentIds, $status = array(1)) {
        if (empty($commentIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'id', $commentIds);
        if (!empty($status) && !is_array($status)) {
            $where[] = array(':eq', 'status', $status);
        } else {
            if (!empty($status) && is_array($status)) {
                $where[] = array(':in', 'status', $status);
            }
        }
        $data = $this->getRows($where);
        $result = array();
        $emojiUtil = new \mia\miagroup\Util\EmojiUtil();
        foreach ($data as $key => $r) {
            $r['comment'] = $emojiUtil->emoji_html_to_unified($r['comment']);
            $result[$r['id']] = $r;
        }
        return $result;
    }
    
    /**
     * 根据subjectids批量分组获取帖子的评论
     */
    public function getBatchCommentList($subjectIds, $count = 3) {
        if (empty($subjectIds)) {
            return array();
        }
        $where = array();
        $field = 'subject_id, GROUP_CONCAT(id ORDER BY id DESC) AS ids';
        $where[] = array(':in', 'subject_id', $subjectIds);
        $where[] = array(':eq', 'status', 1);
        $subComments = $this->getRows($where, $field, false, 0, 'subject_id', false, 'subject_id');
        //循环取出每个分组的前3个ID，合并为一个数组
        $commIds = array();
        $subCommentsLimit = array(); // 存以选题ID为键的值为限制了条数后的评论ID数组
        foreach ($subComments as $comm) {
            $ids = explode(',', $comm['ids']);
            $commIds = array_merge($commIds, array_slice($ids, 0, $count));
            $subCommentsLimit[$comm['subject_id']] = array_slice($ids, 0, $count);
        }
        return $subCommentsLimit;
    }
    
    /**
     * 批量查评论数
     */
    public function getBatchCommentNums($subjectIds)
    {
        if (empty($subjectIds)) {
            return array();
        }
        $where = array();
        $field = 'subject_id, COUNT(id) AS num';
        $where[] = array(':in', 'subject_id', $subjectIds);
        $where[] = array(':eq', 'status', 1);
        $result = $this->getRows($where, $field, false, 0, false, false, 'subject_id');
        //将结果循环为已选题ID为键的以数量为值的一维数组
        $num = array();
        foreach ($result as $r) {
            $num[$r['subject_id']] = $r['num'];
        }
        $result = array();
        foreach ($subjectIds as $subjectId) {
            $result[$subjectId] = intval($num[$subjectId]);
        }
        return $result;
    }
    
    /**
     * 添加评论
     */
    public function addComment($setData){
        
        $data = $this->insert($setData);
        return $data;
    }
    
    //删除评论
    public function delComment($id, $userId) {
        $where[] = ['id', $id];
        $where[] = ['status', 1];
        $where[] = ['user_id', $userId];
        $setInfo[] = ['status', 0];
        $affect = $this->update($setInfo,$where);
        return $affect;
    }
    
    //获取专家评论帖子数
    public function getCommentByExpertId($expertid){
        $sql ="select count(DISTINCT c.subject_id) as num from {$this->tableName} as c, group_subjects as s where c.status=1 and c.user_id={$expertid} and c.subject_id = s.id and s.status = 1";
        //查出该条评论信息
        $result = $this->query($sql);
        return $result[0]['num'];
    }
    
    /**
     * 获取用户的评论
     */
    public function getCommentsByUid($userId){
        if (empty($userId)) {
            return array();
        }
        $where = array();
        $where[] = ['user_id',$userId];
        $where[] = ['status',1];
        $result = $this->getRows($where);
        return $result;
    }
    
    //删除或屏蔽评论
    public function deleteComments($ids,$status,$shieldText) {
        $setData = array();
        $where = array();
        //删除帖子
        $setData[] = ['status',$status];
        if(!empty($shieldText)){
            $setData[] = ['shield_text',$shieldText];
        }
        $where[] = ['id',$ids];
        
        $affect = $this->update($setData,$where);
        return $affect;
    }
    
    /**
     * 获取用户的评论信息
     */
    public function getUserSubjectCommentInfo($userId, $page = 1, $pageSize = 10){
        $offset = $pageSize * ($page - 1);
        $where[] = ['status',1];
        $where[] = ['user_id',$userId];
        $groupBy = 'subject_id';
        $orderBy = "id desc";
        $data = $this->getRows($where,'subject_id, max(id) as id',$pageSize,$offset,$orderBy,false,$groupBy);
        return $data;
    }
    
    //获取选题评论列表
    public function getCommentBySubjectId($subjectId, $user_type = 0, $pageSize = 21, $commentId = 0) {
        if ($commentId > 0) {
            $where[] = [':<','id',$commentId];
        }
        if($user_type == 1){
            $where[] = ['is_expert',1];
        }
        $where[] = ['subject_id',$subjectId];
        $where[] = ['status',[1,2]];
        $orderBy = 'id desc';
        $commentInfo = $this->getRows($where,'id',$pageSize,0,$orderBy);
        $commentIds = array_column($commentInfo, 'id');
        return $commentIds;
    }
    
    /**
     * 获取评论列表
     */
    public function getCommentListByCond($cond, $offset = 0, $limit = 20, $orderBy = false) {
        if (intval($cond['subject_id']) <= 0 && intval($cond['user_id']) <= 0) {
            return array();
        }
        $where = [];
        foreach ($cond as $k => $v) {
            $where[] = $v;
        }
        $data = $this->getRows($where, '*', $limit, $offset, $orderBy);
        return $data;
    }
    
    /**
     * 根据评论id查询帖子
     * @param array $commentIds
     */
    public function getSubjectIdsByComment($commentIds){
        if (empty($commentIds)) {
            return array();
        }
        $where = array();
        $where[] = ['id',$commentIds];
        $result = $this->getRows($where,'distinct subject_id');
        $subjectIds = array();
        foreach ($result as $value) {
            $subjectIds[] = $value['subject_id'];
        }
        return $subjectIds;
    }
    
}