<?php
namespace mia\miagroup\Service;

use \F_Ice;
use mia\miagroup\Service\User as UserService;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Service\Item as ItemService;
use mia\miagroup\Model\Comment as CommentModel;
use mia\miagroup\Util\EmojiUtil;
use mia\miagroup\Service\News;
use mia\miagroup\Service as Service;
use mia\miagroup\Service\Ums\Koubei as KoubeiUmsService;

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
     * @param $field user_info parent_comment subject
     */
    public function getBatchComments($commentIds, $field = array('user_info', 'parent_comment'), $status = array(1), $supplierHide = true) {
        $commentInfos = $this->commentModel->getBatchComments($commentIds, $status);
        if (empty($commentInfos)) {
            return $this->succ();
        }
        // 收集用户ID和父评论ID
        $userIds = array();
        $fids = array();
        $subjectIds = array();
        foreach ($commentInfos as $key => $commment) {
            $userIds[] = $commment['user_id'];
            $subjectIds[] = $commment['subject_id'];
            if (intval($commment['fid']) > 0) {
                $fids[] = $commment['fid'];
            }
        }
        //商家回复后统一默认为客服用户id
        $commentUserId = F_Ice::$ins->workApp->config->get('busconf.user.miaKefuUid');
        // 获取用户信息
        if (in_array('user_info', $field)) {
            //为了统一修改商家用户信息为客服信息，批量获取用户信息中默认加入客服信息
            array_push($userIds, $commentUserId);
            $users = $this->userService->getUserInfoByUids($userIds)['data'];
            if ($supplierHide == true) {
                //是否有商家用户，如果是商家用户，不返回uid
                $itemService = new ItemService();
                $userMerchantRelations = $itemService->getBatchUserSupplierMapping($userIds)['data'];
                //仓库类型是10，11，12 ，13的商家，回复口碑后，昵称统一显示"蜜芽客服
                $warehouseType = array(10,11,12,13);
                $supplierIds = array();
                foreach ($users as $uid => $user) {
                    if ($userMerchantRelations[$uid]['status'] == 1) {
                        $users[$uid]['id'] = -1;
                        $users[$uid]['supplier_id'] = $userMerchantRelations[$uid]['supplier_id'];;
                        //如果是商家，收集商家id，查仓库类型
                        $supplierIds[] = $userMerchantRelations[$uid]['supplier_id'];
                    }
                }
                $koubeiUmsService = new KoubeiUmsService();
                $warehouseInfo = $koubeiUmsService->getBatchWarehouse($supplierIds)['data'];
            }
        }
        // 获取帖子信息
        if (in_array('subject', $field)) {
            $subjectService = new SubjectService();
            $subjects = $subjectService->getBatchSubjectInfos($subjectIds, 0, array(), array())['data'];
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
            $commentInfo['user_id'] = $commentInfos[$commentId]['user_id'];
            $commentInfo['subject_id'] = $commentInfos[$commentId]['subject_id'];
            $commentInfo['comment'] = $commentInfos[$commentId]['comment'];
            $commentInfo['created'] = $commentInfos[$commentId]['create_time'];
            if (in_array('user_info', $field)) {
                $commentInfo['comment_user'] = $users[$commentInfos[$commentId]['user_id']];
                //如果仓库类型是10，11，12 ，13的商家进行回复，昵称统一修改为蜜芽客服
                if(isset($commentInfo['comment_user']['supplier_id']) && in_array($warehouseInfo[$commentInfo['comment_user']['supplier_id']]['type'], $warehouseType)){
                    $commentInfo['comment_user'] = $users[$commentUserId];
                }
            }
            if (in_array('subject', $field)) {
                $commentInfo['subject'] = $subjects[$commentInfos[$commentId]['subject_id']];
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
    
    /**
     * 发布帖子评论
     */
    public function comment($subjectId, $commentInfo, $checkSensitive = true) {
        if (empty($commentInfo) || intval($subjectId) <= 0) {
            return $this->error(500);
        }
        $commentInfo['comment'] = trim($commentInfo['comment']);
        if ($commentInfo['comment'] == "") {
            return $this->error(500);
        }
        //过滤xss、过滤html标签
        $commentInfo['comment'] = strip_tags($commentInfo['comment'], '<span><p>');
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
        
        if ($checkSensitive == true) {
            //过滤敏感词
            $sensitive_res = $audit->checkSensitiveWords($commentInfo['comment'], 1);
            if ($sensitive_res['code'] == 1127) {
                return $this->error(1127);
            }
            $sensitive_res = $sensitive_res['data'];
            if(!empty($sensitive_res['sensitive_words'])){
                return $this->error(1112, '有敏感内容 "' . implode('","', $sensitive_res['sensitive_words']) . '"，发布失败');
            }
        }
        
        $subjectService = new SubjectService();
        $subjectInfoData = $subjectService->getBatchSubjectInfos(array($subjectId), 0, array())['data'];
        $subjectInfo = $subjectInfoData[$subjectId];
        //如果评论的是口碑贴，对评论内容进行校验
        if (intval($subjectInfo['koubei_id']) > 0) {
            $sensitive_res = $audit->checkKoubeiSensitiveWords($commentInfo['comment'])['data'];
            if(!empty($sensitive_res['sensitive_words'])){
                $commentInfo['status'] = 2; //评论仅自己可见
            }
        }
        //判断是否是第一条回复
        $commentNums = $this->getBatchCommentNums([$subjectId])["data"][$subjectId];

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

        // 评论信息入库
        $commentInfo['subject_id'] = $subjectId;
        $commentInfo['subject_uid'] = $subjectInfo['user_id'];
        $commentInfo['comment'] = $commentInfo['comment'];
        $commentInfo['create_time'] = date("Y-m-d H:i:s", time());
        // 记录评论信息
        $commentInfo['id'] = $this->commentModel->addComment($commentInfo);
        
        // 获取入库的评论,查主库
        $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);
        $comment = $this->getBatchComments(array($commentInfo['id']), array('user_info', 'parent_comment'), array(1, 2))['data'];


        if (!empty($comment[$commentInfo['id']])) {
            $commentInfo = $comment[$commentInfo['id']];//$commentInfo 值覆盖
        }
        if ($commentInfo['status'] == 2) {
            return $this->succ($commentInfo);
        }
        $sendFromUserId = $user_id; // 当前登录人id
        $toUserId = $subjectInfo['user_id'];


        //回复帖子或评论：回复人非帖子作者，发消息给帖子作者
        if ($sendFromUserId != $toUserId) {
            $this->newService->postMessage('img_comment', $toUserId, $sendFromUserId, $commentInfo['id']);
            //赠送用户蜜豆（帖子作者）
            $mibean = new \mia\miagroup\Remote\MiBean();
            $param['user_id'] = $sendFromUserId;//评论者
            $param['relation_type'] = 'receive_comment';
            $param['relation_id'] = $commentInfo['id'];
            $param['to_user_id'] = $toUserId;//帖子作者
            $res = $mibean->add($param);

            //如果是长文：赠送用户蜜豆（评论人）
            if ($subjectInfo['type'] === 'blog') {
                //检查是否互动过
                $isCommented = $this->commentModel->checkComment($subjectId, $sendFromUserId);
                if (count($isCommented) <= 1) {
                    $param_2['user_id'] = $toUserId;//帖子作者
                    $param_2['relation_type'] = 'receive_comment';
                    $param_2['relation_id'] = $commentInfo['id'];
                    $param_2['to_user_id'] = $sendFromUserId;//评论者
                    $param_2['mibean'] = 3;//评论长文，奖励3蜜豆
                    $res = $mibean->add($param_2);
                    //长文奖励成功提示
                    $blogComment = 1;
                }
            }

            //8：00-23：00发送评论，发push
            // $timeZero = strtotime(date("Y-m-d"));
            // $timeNow = time();
            // $period = $timeNow - $timeZero;
            // if (28800 < $period && $period < 82800) {
            //     $nickName = $commentInfo["comment_user"]["nickname"] ? $commentInfo["comment_user"]["nickname"] : $commentInfo["comment_user"]["username"];
            //     $push = new Service\Push();
            //     $push->pushMsg($toUserId, $nickName . "评论了你的帖子", "miyabaobei://subject?id=" . $subjectId);
            // }
        }

        \DB_Query::switchCluster($preNode);
        // 回复评论：被评论人不是帖子作者，被评论人不是评论人自己
        if ($commentInfo['parent_user'] && $commentInfo['parent_user']['user_id'] != $toUserId && $commentInfo['parent_user']['user_id'] != $sendFromUserId) {
            $toUserId = $commentInfo['parent_user']['user_id'];
            $this->newService->postMessage('img_comment', $toUserId, $sendFromUserId, $commentInfo['id']);

            //8：00-23：00发送评论，发push
            // $timeZero = strtotime(date("Y-m-d"));
            // $timeNow = time();
            // $period = $timeNow - $timeZero;
            // if (28800 < $period && $period < 82800) {
            //     $nickName = $commentInfo["comment_user"]["nickname"] ? $commentInfo["comment_user"]["nickname"] : $commentInfo["comment_user"]["username"];
            //     $push = new Service\Push();
            //     $push->pushMsg($toUserId, $nickName . "回复了你的评论", "miyabaobei://subject?id=" . $subjectId);
            // }
        }
        if (isset($blogComment) && $blogComment == 1) {
            return $this->succ($commentInfo, "评论成功+3蜜豆");
        } else {
            return $this->succ($commentInfo, "评论成功");
        }
    }
    
    /**
     * 删除评论
     */
    public function delComment($id, $userId){
        //查询评论信息
        $commentInfo = $this->commentModel->getBatchComments([$id])[$id];
        if($commentInfo['status'] == 0){
            return $this->succ(true);
        }
        if($commentInfo['user_id'] == $userId){
            $data = $this->commentModel->delComment($id, $userId);
            if($data){
                return $this->succ(true);
            }
        }
        return $this->error(1103);
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
        $answerSubjects = $subjectService->getBatchSubjectInfos($subjectIds, $currentId, array('user_info', 'count', 'group_labels'))['data'];
        $commentService = new \mia\miagroup\Service\Comment();
        $commentInfos = $commentService->getBatchComments($commentIds, array('user_info'))['data'];
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

    /**
     * 获取选题评论列表
     */ 
    public function getCommentBySubjectId($subjectId, $user_type = 0, $pageSize = 21, $commentId = 0) {
        $commentArrs = array();
        $commentIds = $this->commentModel->getCommentBySubjectId($subjectId, $user_type, $pageSize, $commentId);
        $comments = $this->getBatchComments($commentIds, array('user_info', 'parent_comment'), array(1, 2))['data'];
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                //处理仅自己可见的评论
                if ($comment['status'] == 2) {
                    if ($this->ext_params['current_uid'] != $comment['user_id']) {
                        continue;
                    }
                }
                $commentArrs[] = $comment;
            }
        }
        return $this->succ($commentArrs);
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
    
    /**
     * 评论原子服务
     */
    public function addComment($params) {
        // 评论信息入库
        if (intval($params['subject_id']) <=0 || intval($params['user_id']) <=0 || empty($params['comment'])) {
            return $this->error(500);
        }
        $commentInfo['subject_id'] = $params['subject_id'];
        $commentInfo['user_id'] = $params['user_id'];
        $commentInfo['subject_uid'] = $params['subject_uid'];
        $commentInfo['comment'] = $params['comment'];
        $commentInfo['fid'] = intval($params['fid']);
        $commentInfo['is_expert'] = $params['is_expert'];
        $commentInfo['create_time'] = date("Y-m-d H:i:s", time());
        // 记录评论信息
        $commentInfo['id'] = $this->commentModel->addComment($commentInfo);
        return $commentInfo;
    }
}
