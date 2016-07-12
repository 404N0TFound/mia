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
    
    
}