<?php
namespace mia\miagroup\Service;

use mia\miagroup\Service\User as UserService;
use mia\miagroup\Model\Comment as CommentModel;

class Comment extends \FS_Service {

    public function __construct() {
        $this->userService = new UserService();
        $this->commentModel = new CommentModel();
    }

    /**
     * 根据评论IDs批量获取评论信息
     */
    public function getBatchComments($commentIds, $field = array('user_info', 'parent_comment'), $status = 1) {
        $commentInfos = $this->commentModel->getBatchComments($commentIds, $status);
        if (empty($commentInfos)) {
            return $this->succ();
        }
        // 收集用户ID和父评论ID
        $userIds = array();
        $fids = array();
        foreach ($commentInfos as $key => $commment) {
            $userIds[] = $commment['user_id'];
            $fids[] = $commment['fid'];
        }
        // 获取用户信息
        if (in_array('user_info', $field)) {
            $users = $this->userService->getUserInfoByUids($userIds)['data'];
        }
        // 获取父评论信息
        if (in_array('parent_comment', $field) && !empty($fids)) {
            $parentComments = self::getBatchComments($fids, array_diff($field, array('parent_comment')), 9);
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
     * 批量查评论数
     */
    public function getBatchCommentNums($subjectIds) {
        if (empty($subjectIds)) {
            return $this->succ();
        }
        $subjectCommentNums = $this->commentModel->getBatchCommentNums($subjectIds);
        return $this->succ($subjectCommentNums);
    }
}
