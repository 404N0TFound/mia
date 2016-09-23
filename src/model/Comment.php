<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Comment\SubjectComment;

class Comment {

    private $subjectCommentData;

    public function __construct() {
        $this->subjectCommentData = new SubjectComment();
    }
    
    /**
     * 根据评论ids批量获取评论
     */
    public function getBatchComments($commentIds, $status = array(1)) {
        $comments = $this->subjectCommentData->selectCommentByIds($commentIds, $status);
        return $comments;
    }
    
    /**
     * 根据subjectids批量分组获取帖子的评论ids
     */
    public function getBatchCommentList($subjectIds, $count = 3) {
        $subjectComments = $this->subjectCommentData->getBatchCommentList($subjectIds, $count);
        return $subjectComments;
    }
    
    /**
     * 添加评论
     */
    public function addComment($setData){
        $data = $this->subjectCommentData->addComment($setData);
        return $data;
    }
    
    /**
     * 根据subjectIds批量分组查评论数
     */
    public function getBatchCommentNums($subjectIds) {
        $subjectCommentNums = $this->subjectCommentData->getBatchCommentNums($subjectIds);
        return $subjectCommentNums;
    }

    /**
     * 获取根据帖子ID获取评论列表
     */
    public function getCommentListBySubjectId($subjectId, $user_type = 0, $page = 1, $limit = 20) {
        if($user_type == 1){
            $where['is_expert'] = array('is_expert', 1);
        }
        $where['subject_id'] = array(':eq', 'subject_id', $subjectId);
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $subjectComment = $this->subjectCommentData->getCommentListByCond($where, $offset, $limit, 'id desc');
        $result = array();
        foreach ($subjectComment as $comment) {
            $result[] = $comment['id'];
        }
        return $result;
    }
    
    //删除评论
    public function delComment($id, $userId) {
        $affect = $this->subjectCommentData->delComment($id, $userId);
        return $affect;
    }
    
    //获取专家评论数
    public function getCommentByExpertId($expertid){
        return $this->subjectCommentData->getCommentByExpertId($expertid);
    }
    
    /**
     * 获取用户的评论信息
     */
    public function getUserSubjectCommentInfo($userId, $page = 1, $pageSize = 10){
        $result = $this->subjectCommentData->getUserSubjectCommentInfo($userId, $page, $pageSize);
        return $result;
    }
    
}