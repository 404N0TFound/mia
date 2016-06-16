<?php
namespace mia\miagroup\Service;
use mia\miagroup\Service\User as UserService;

class Comment extends \FS_Service {
    public function __construct() {
        $this->userService = new UserService();
    }
    
    /**
     * 根据评论IDs批量获取评论信息
     */
    public function getBatchComments($commentIds, $field = array('user_info', 'parent_comment'), $status = 1) {
        $commentInfos = $this->selectCommentByIds($commentIds, $status);
        if (empty($commentInfos)) {
            return array();
        }
        //收集用户ID和父评论ID
        $userIds = array();
        $fids = array();
        foreach ($commentInfos as $key => $commment) {
            $userIds[] = $commment['user_id'];
            $fids[] = $commment['fid'];
        }
        //获取用户信息
        if (in_array('user_info', $field)) {
            $users = $this->userService->getUserInfoByUids($userIds);
        }
        //获取父评论信息
        if (in_array('parent_comment', $field) && !empty($fids)) {
            $parentComments = self::getBatchComments($fids, array_diff($field, array('parent_comment')), 9);
        }
        //拼装结果集
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
        return $result;
    }
    
    /**
     * 根据subjectids批量分组获取帖子的评论
     */
    public function getBatchCommentList($subjectIds, $count = 3) {
        $this->db_read = $this->load->database('read', true);
        //将参数分割为字符串
        $subIds = implode(',', $subjectIds);
//        $sql = "SELECT subject_id,GROUP_CONCAT(id ORDER BY id DESC) AS ids "
//                . "FROM group_subject_comment "
//                . "WHERE subject_id in({$subIds}) AND status=1  "
//                . "GROUP BY subject_id";
        //[修复bug]2015-08-06 3条评论屏蔽掉被屏蔽用户的评论
        $sql = "SELECT c.subject_id,GROUP_CONCAT(c.id ORDER BY c.id DESC) AS ids "
                . "FROM {$this->table_comment} as c "
                . "LEFT JOIN {$this->table_user_shield} as u "
                . "ON c.user_id=u.user_id "
                . "WHERE c.subject_id in({$subIds}) "
                . "AND c.`status`=1 AND (u.status = 0 or u.user_id is null) "
                . "GROUP BY c.subject_id";
        //读取以选题ID分组的每组所有id（id DESC排序）
        $subComments = $this->db_read->query($sql)->result_array();

        //循环取出每个分组的前3个ID，合并为一个数组
        $commIds = array();
        $subCommentsLimit = array(); //存以选题ID为键的值为限制了条数后的评论ID数组
        foreach ($subComments as $comm) {
            $ids = explode(',', $comm['ids']);
            $commIds = array_merge($commIds, array_slice($ids, 0, $count));
            $subCommentsLimit[$comm['subject_id']] = array_slice($ids, 0, $count);
        }
        unset($comm);

        //没有评论，直接返回空数组
        if (empty($commIds)) {
            return array();
        }

        $comments = $this->getBatchComments($commIds, array('user_info', 'parent_comment'));

        //将批量查询出来的评论，按照对应的选题ID分配下去
        $subRelationComm = array(); 
        foreach ($subCommentsLimit as $key => $commArray) {
            foreach ($commArray as $cid) {
                $subRelationComm[$key][] = $comments[$cid];
            }
        }

        return $subRelationComm;
    }
    
    
    
    //选题评论
    public function comment($iSubjectId, $commentInfo) {
        if (empty($commentInfo)) {
            return false;
        }
        if (!is_numeric($iSubjectId) || intval($iSubjectId) <= 0) {
            return false;
        }
        $commentInfo['comment'] = trim(emoji_unified_to_html($commentInfo['comment']));
        if ($commentInfo['comment'] == "") {
            return false;
        }
        $user_id = $commentInfo['user_id'];
        //评论信息入库
        $commentInfo['comment'] = $commentInfo['comment'];
        $commentInfo['create_time'] = date("Y-m-d H:i:s", time());
        $this->db_write = $this->load->database('write', true);
        $insertRes = $this->db_write->set($commentInfo)->insert($this->table_comment);
        if (!$insertRes) {
            return false;
        }
        $commentId = $this->db_write->insert_id();
        $commentInfo['id'] = $commentId;
        //计数信息入库
        $sql = "update {$this->table_subjects} set comment_count = comment_count + 1 where id = {$iSubjectId}";
        $this->db_write->query($sql);
    
        //获取入库的评论
        $comment = $this->getBatchComments(array($commentId), array('user_info', 'parent_comment'));
        if (!empty($comment[$commentId])) {
            $commentInfo = $comment[$commentId];
        }
    
        //发送评论消息
        $subjectInfo = $this->mGroup->getSubjectInfoById($iSubjectId);
    
        $sendFromUserId = $user_id; //当前登录人id
        $toUserId = $subjectInfo['subject_info']['user_info']['user_id'];
        $this->load->model("news_model", "mNews");
        //如果直接评论图片，自己评论自己的图片，不发送消息/push
        if ($sendFromUserId != $toUserId) {
            //发消息
            $this->mNews->addNews('single', 'group', 'img_comment', $sendFromUserId, $toUserId, $commentInfo['id']);
            //发push
            if($subjectInfo['subject_info']['comment_count'] <= 3) {
                $content = $commentInfo['comment_user']['nickname']."刚刚评论了你的帖子";
                $this->push($subjectInfo['subject_info']['id'], $content, $toUserId);
            }
        }
        //赠送用户蜜豆
        if ($sendFromUserId != $toUserId) {
            $this->load->model("mibean_model", "mBean");
            $this->mBean->sendMiYaBean($type = 'receive_comment', $sendFromUserId, $commentInfo['id'], $toUserId);
        }
        //有回复评论的情况下发消息/push
        //如果是回复图片的评论，被评论人和图片发布人或者自己回复自己的评论，不发消息/push
        if ($commentInfo['parent_user'] && $commentInfo['parent_user']['user_id'] != $toUserId && $commentInfo['parent_user']['user_id'] != $sendFromUserId) {
            $toUserId = $commentInfo['parent_user']['user_id'];
            $this->mNews->addNews('single', 'group', 'img_comment', $sendFromUserId, $toUserId, $commentInfo['id']);
            //发push
            if($subjectInfo['subject_info']['comment_count'] <= 3) {
                $content = $commentInfo['comment_user']['nickname']."刚刚回复了你";
                $this->push($subjectInfo['subject_info']['id'], $content, $toUserId);
            }
        }
        return $commentInfo;
    }
    
    
    
    
    
}
