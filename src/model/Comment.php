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
    public function getBatchComments($commentIds, $status = 1) {
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

}