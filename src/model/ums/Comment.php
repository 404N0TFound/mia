<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Comment extends \DB_Query {

    protected $dbResource = 'miagroupums';
    //帖子
    protected $tableComment = 'group_subject_comment';
    protected $indexComment = array('id', 'user_id', 'subject_id');
    
    protected $tableKoubei = 'koubei';

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
    
    /**
     * 查询口碑评论
     */
    public function getKoubeiCommentList($cond, $offset = 0, $limit = 50, $order_by = 'id desc') {
        $this->tableName = $this->tableComment;
        $result = array('count' => 0, 'list' => array());
        $join = " LEFT JOIN $this->tableKoubei on $this->tableComment.subject_id  = $this->tableKoubei.subject_id";
        $where[] = [':notnull', "$this->tableKoubei.subject_id"];
        if (!empty($cond)) {
            //检查是否使用索引，没有索引返回
            if (empty(array_intersect(array_keys($cond), $this->indexComment))) {
                return array();
            }
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'user_id':
                        $where[] = ["$this->tableComment.$k", $v];
                        break;
                    case 'koubei_id':
                        $where[] = ["$this->tableKoubei.id", $v];
                        break;
                    case 'item_id':
                        $where[] = ["$this->tableKoubei.$k", $v];
                        break;
                    case 'koubei_start_time':
                        $where[] = [':ge', "$this->tableKoubei.created_time", $v];
                        break;
                    case 'koubei_end_time':
                        $where[] = [':le', "$this->tableKoubei.created_time", $v];
                }
            }
        }
        $result['count'] = $this->count($where, $join);
        if (intval($result['count']) <= 0) {
            return $result;
        }
        if (!empty($order_by)) {
            $order_by = "$this->tableComment.$order_by";
        }
        $result['list'] = $this->getRows($where, "$this->tableComment.id, $this->tableKoubei.id as koubei_id", $limit, $offset, $order_by, $join);
        if (!empty($result['list'])) {
            foreach ($result['list'] as $k => $v) {
                $result['list'][$k]['id'] = $v['id'];
            }
        }
        return $result;
    }
    
    /**
     * 查询评论数量
     */
    public function getCommentCount($cond) {
        $this->tableName = $this->tableComment;
        $where = array();
    
        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','create_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','create_time', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        $result = $this->getRows($where, 'subject_uid,count(1) as nums', false, 0, false,false,'subject_uid');
    
        return $result;
    }
}