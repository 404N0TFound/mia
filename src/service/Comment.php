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
}
