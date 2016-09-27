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
    public function getCommentListBySubjectId($subjectId, $user_type = 0, $page = 1, $limit = 20) {
        $commentIds = $this->commentModel->getCommentListBySubjectId($subjectId, $user_type, $page, $limit);
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
        //判断登录用户是否是被屏蔽用户
        $audit = new \mia\miagroup\Service\Audit();
        $is_shield = $audit->checkUserIsShield($user_id)['data'];
        if($is_shield['is_shield']){
            return $this->error(1104);
        }
        //判断用户手机号，邮箱，合作平台账号是否验证或绑定过
        $is_valid = $audit->checkIsValidUser($user_id)['data'];
        if(!$is_valid['is_valid']){
            return $this->error(1115);
        }
        //过滤敏感词
        $sensitive_res = $audit->checkSensitiveWords($commentInfo['comment'])['data'];
        if(!empty($sensitive_res['sensitive_words'])){
            return $this->error(1112);
        }
        //判断是否有父评论
        if (intval($commentInfo['fid']) > 0) {
            $parentInfo = $this->getBatchComments([$commentInfo['fid']])['data'][$commentInfo['fid']];
            if(!empty($parentInfo)) {
                if($parentInfo['status'] != 1) {
                    //父ID 无效
                    return $this->error(1108);
                }
            }
        }
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
        
        // 获取入库的评论,查主库
        $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);
        $comment = $this->getBatchComments(array($commentInfo['id']), array('user_info', 'parent_comment'))['data'];
        \DB_Query::switchCluster($preNode);
        
        if (!empty($comment[$commentInfo['id']])) {
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
    
    /**
     * 删除评论
     */
    public function delComment($id, $userId){
        $data = 0;
        //查询评论信息
        $commentInfo = $this->commentModel->getBatchComments([$id])[$id];
        if($commentInfo['status'] == 0){
            return $this->succ(1);
        }
        if($commentInfo['user_id'] == $userId){
            $data = $this->commentModel->delComment($id, $userId);
        }
        return $this->succ($data);
    }
    
    //获取专家评论数
    public function getCommentByExpertId($expertid){
        $data = $this->commentModel->getCommentByExpertId($expertid);
        return $this->succ($data);
    }
    
    /**
     * 专家的评论信息
     */
    public function expertsSubjects($userId, $currentId, $page, $pageSize){
        $arrSubjects = array("total" => 0, "subject_lists" => array(), "status" => 0);
        //判断登录用户是否是被屏蔽用户
        $auditService = new \mia\miagroup\Service\Audit();
        $userStatus = $auditService->checkUserIsShield($userId)['data'];
        if($userStatus['is_shield']) {
            $arrSubjects['status'] = -1;
            return $this->succ($arrSubjects);
        }
        $commontArrs = $this->commentModel->getUserSubjectCommentInfo($userId, $page, $pageSize);
        $subjectIds = array_column($commontArrs, 'subject_id');
        $commentIds = array_column($commontArrs, 'id');
        $subjectService = new \mia\miagroup\Service\Subject();
        $answerSubjects = $subjectService->getBatchSubjectInfos($subjectIds, $currentId, array('user_info', 'count', 'group_labels'));
        $commentService = new \mia\miagroup\Service\Comment();
        $commentInfos = $commentService->getBatchComments($commentIds, array('user_info'));
        foreach ($commontArrs as $val) {
            if (!empty($answerSubjects[$val['subject_id']]) && !empty($commentInfos[$val['id']])) {
                $subject = $answerSubjects[$val['subject_id']];
                $subject['comment_info'] = array($commentInfos[$val['id']]);
                $arrSubjects['subject_lists'][] = $subject;
            }
        }
        //评论数量
        $countRes = $this->commentModel->getCommentByExpertId($userId);
        if (isset($countRes['c']) && $countRes['c'] > 0) {
            $arrSubjects['total'] = $countRes['c'];
            $arrSubjects['status'] = 1;
        }
        return $this->succ($arrSubjects);
    }
    
    //查出某用户的所有评论
    public function getComments($userId){
        if(!is_numeric($userId) || intval($userId) <= 0){
            return $this->error(500);
        }
        $arrComments = $this->commentModel->getCommentsByUid($userId);
        return $this->succ($arrComments);
    }
    
    /**
     * 批量删除或屏蔽评论
     * @param array $commentIds
     * @param int $status
     * @param string $shieldText
     */
    public function delComments($commentIds,$status,$shieldText=''){
        if(empty($commentIds)){
            return $this->error(500);
        }
        //删除评论
        $result = $this->commentModel->deleteComments($commentIds,$status,$shieldText);
        return $this->succ($result);
    }
    
    /**
     *@todo 根据评论ID获取帖子ID
     *@param    $ids       array   评论ID
     *@return   array
     **/
    public function getSubjectIdsByComment($ids = array())
    {
        if (empty($ids) || !is_array($ids)){
            return $this->error(500);
        }
        $arrSubjects = $this->commentModel->getSubjectIdsByComment($ids);
        return $this->succ($arrSubjects);
    }
}
