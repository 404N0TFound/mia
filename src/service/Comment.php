<?php
namespace mia\miagroup\Service;

use mia\miagroup\Service\User as UserService;
use mia\miagroup\Service\Subject;
use mia\miagroup\Model\Comment as CommentModel;
use mia\miagroup\Util\EmojiUtil;
use mia\miagroup\Service\News;

class Comment extends \mia\miagroup\Lib\Service {

    public function __construct() {
        parent::__construct();
        $this->userService = new UserService();
        $this->commentModel = new CommentModel();
        $this->newService = new News();
        $this->emojiUtil = new EmojiUtil();
    }

    /**
     * 根据评论IDs批量获取评论信息
     */
    public function getBatchComments($commentIds, $field = array('user_info', 'parent_comment'), $status = array(1)) {
        $commentInfos = $this->commentModel->getBatchComments($commentIds, $status);
        if (empty($commentInfos)) {
            return $this->succ();
        }
        // 收集用户ID和父评论ID
        $userIds = array();
        $fids = array();
        foreach ($commentInfos as $key => $commment) {
            $userIds[] = $commment['user_id'];
            if (intval($commment['fid']) > 0) {
                $fids[] = $commment['fid'];
            }
        }
        // 获取用户信息
        if (in_array('user_info', $field)) {
            $users = $this->userService->getUserInfoByUids($userIds)['data'];
        }
        // 获取父评论信息
        if (in_array('parent_comment', $field) && !empty($fids)) {
            $parentComments = $this->getBatchComments($fids, array_diff($field, array('parent_comment')), array())['data'];
        }
        // 拼装结果集
        $result = array();
        foreach ($commentIds as $commentId) {
            if (empty($commentInfos[$commentId])) {
                continue;
            }
            $commentInfo = null;
            $commentInfo['id'] = $commentInfos[$commentId]['id'];
            $commentInfo['comment'] = $commentInfos[$commentId]['comment'];
            $commentInfo['created'] = $commentInfos[$commentId]['create_time'];
            if (in_array('user_info', $field)) {
                $commentInfo['comment_user'] = $users[$commentInfos[$commentId]['user_id']];
            }
            if (in_array('parent_comment', $field) && intval($commentInfos[$commentId]['fid']) > 0) {
                $commentInfo['parent_comment'] = $parentComments[$commentInfos[$commentId]['fid']];
                $commentInfo['parent_user'] = $commentInfo['parent_comment']['comment_user'];
                unset($commentInfo['parent_comment']['comment_user']);
            }
            $commentInfo['status'] = $commentInfos[$commentId]['status'];
            $result[$commentId] = $commentInfo;
        }
        return $this->succ($result);
    }

    /**
     * 根据subjectids批量分组获取帖子的评论ids
     */
    public function getBatchCommentList($subjectIds, $count = 3) {
        if (empty($subjectIds)) {
            return $this->succ();
        }
        $commIds = array();
        $subCommentsLimit = array();
        $subjectComments = $this->commentModel->getBatchCommentList($subjectIds, $count);
        foreach ($subjectComments as $subjectId => $ids) {
            $commIds = array_merge($commIds, array_slice($ids, 0, $count));
            $subCommentsLimit[$subjectId] = array_slice($ids, 0, $count);
        }
        // 没有评论，直接返回空数组
        if (empty($commIds)) {
            return $this->succ();
        }
        $comments = $this->getBatchComments($commIds, array('user_info', 'parent_comment'))['data'];
        // 将批量查询出来的评论，按照对应的选题ID分配下去
        $subRelationComm = array();
        foreach ($subCommentsLimit as $key => $commArray) {
            foreach ($commArray as $cid) {
                $subRelationComm[$key][] = $comments[$cid];
            }
        }
        return $this->succ($subRelationComm);
    }
    
    /**
     * 获取帖子的评论列表
     */
    public function getCommentListBySubjectId($subjectId, $page = 1, $limit = 20) {
        $commentIds = $this->commentModel->getCommentListBySubjectId($subjectId, $page, $limit);
        if (empty($commentIds)) {
            return $this->succ(array());
        }
        $commentList = $this->getBatchComments($commentIds);
        return $this->succ($commentList);
    }
    

    /**
     * 批量查评论数
     */
    public function getBatchCommentNums($subjectIds) {
        if (empty($subjectIds)) {
            return $this->succ();
        }
        $subjectCommentNums = $this->commentModel->getBatchCommentNums($subjectIds);
        return $this->succ($subjectCommentNums);
    }
    
    /**
     * 发布帖子评论
     */
    public function comment($subjectId, $commentInfo) {
        if (empty($commentInfo) || intval($subjectId) <= 0) {
            return $this->error(500);
        }
        $commentInfo['comment'] = trim($this->emojiUtil->emoji_unified_to_html($commentInfo['comment']));
        if ($commentInfo['comment'] == "") {
            return $this->error(500);
        }
        $user_id = $commentInfo['user_id'];

        //判断用户是否是专家
        $expert = $this->userService->getBatchExpertInfoByUids(array($user_id));
        $is_expert = 0;
        if(isset($expert['data']) && count($expert['data'])>0)
            $is_expert = 1;

        // 评论信息入库
        $commentInfo['subject_id'] = $subjectId;
        $commentInfo['comment'] = $commentInfo['comment'];
        $commentInfo['is_expert'] = $is_expert;
        $commentInfo['create_time'] = date("Y-m-d H:i:s", time());
        // 记录评论信息
        $commentInfo['id'] = $this->commentModel->addComment($commentInfo);
        
        // 获取入库的评论
        $comment = $this->getBatchComments(array($commentInfo['id']), array())['data'];
        if (!empty($comment[$commentId])) {
            $commentInfo = $comment[$commentInfo['id']];
        }
        
        $this->subjectService = new Subject();
        $subjectInfoData = $this->subjectService->getBatchSubjectInfos(array($subjectId), 0, array())['data'];
        $subjectInfo = $subjectInfoData[$subjectId];
        $sendFromUserId = $user_id; // 当前登录人id
        $toUserId = $subjectInfo['user_id'];
        // 如果直接评论图片，自己评论自己的图片，不发送消息/push
        if ($sendFromUserId != $toUserId) {
            // 发消息
            $this->newService->addNews('single', 'group', 'img_comment', $sendFromUserId, $toUserId, $commentInfo['id']);
            //赠送用户蜜豆
            $mibean = new \mia\miagroup\Remote\MiBean();
            $param['user_id'] = $sendFromUserId;
            $param['relation_type'] = 'receive_comment';
            $param['relation_id'] = $commentInfo['id'];
            $param['to_user_id'] = $toUserId;
            $mibean->add($param);
        }
        // 如果是回复图片的评论，被评论人和图片发布人或者自己回复自己的评论，不发消息/push
        if ($commentInfo['parent_user'] && $commentInfo['parent_user']['user_id'] != $toUserId && $commentInfo['parent_user']['user_id'] != $sendFromUserId) {
            $toUserId = $commentInfo['parent_user']['user_id'];
            $this->newService->addNews('single', 'group', 'img_comment', $sendFromUserId, $toUserId, $commentInfo['id'])['data'];
        }
        
        return $this->succ($commentInfo);
    }
}
