<?php
namespace mia\miagroup\Data\Comment;

class SubjectComment extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subjects';

    protected $mapping = array();

    public function selectCommentByIds($commentIds, $status = 1) {
        if (empty($commentIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'id', $commentIds);
        if (!empty($status) && !is_array($status)) {
            $where[] = array('i:eq', 'status', $status);
        } else {
            if (!empty($status) && is_array($status)) {
                $where[] = array('i:in', 'status', $status);
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
        $subComments = $this->getRows($where, $field, false, false, 'subject_id');
        // 循环取出每个分组的前3个ID，合并为一个数组
        $commIds = array();
        $subCommentsLimit = array(); // 存以选题ID为键的值为限制了条数后的评论ID数组
        foreach ($subComments as $comm) {
            $ids = explode(',', $comm['ids']);
            $commIds = array_merge($commIds, array_slice($ids, 0, $count));
            $subCommentsLimit[$comm['subject_id']] = array_slice($ids, 0, $count);
        }
        return $subCommentsLimit;
        
        // 没有评论，直接返回空数组
        if (empty($commIds)) {
            return array();
        }
        
        $comments = $this->getBatchComments($commIds, array('user_info', 'parent_comment'));
        
        // 将批量查询出来的评论，按照对应的选题ID分配下去
        $subRelationComm = array();
        foreach ($subCommentsLimit as $key => $commArray) {
            foreach ($commArray as $cid) {
                $subRelationComm[$key][] = $comments[$cid];
            }
        }
        
        return $subRelationComm;
    }
    
    /**
     * 添加评论
     */
    public function addComment($setData){
        
        $data = $this->insert($setData);
        return $data;
    }
    
    
}