<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Comment extends \DB_Query {

    protected $dbResource = 'miagroupums';
    //帖子
    protected $tableComment = 'group_subject_comment';
    protected $indexComment = array('id', 'user_id', 'subject_id');

    /**
     * 查询评论
     */
    public function getCommentList($cond, $offset = 0, $limit = 50, $order_by = 'id desc') {
        $this->tableName = $this->tableComment;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //检查是否使用索引，没有索引返回
            if (empty(array_intersect(array_keys($cond), $this->indexComment))) {
                return array();
            }
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','create_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','create_time', $v];
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        $result['count'] = $this->count($where);
        if (intval($result['count']) <= 0) {
            return $result;
        }
        $result['list'] = $this->getRows($where, '*', $limit, $offset, $order_by);
        if (!empty($result['list'])) {
            foreach ($result['list'] as $k => $v) {
                $result['list'][$k]['id'] = $v['id'];
            }
        }
        return $result;
    }
}