<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class OperateIssueRecord extends \DB_Query {

    protected $dbResource = 'miagroupums';

    protected $tableName = 'group_operate_issue_record';

    /*
     * 获取奖励列表
     * */
    public function getOperIssueList($user_id = 0, $op_admin = 0, $limit = 50, $offset = 0, $conditions = [])
    {
        $result = ['list' => [], 'total' => 0];
        $where = [];
        $orderBy = 'id DESC';
        if (!empty($user_id)) {
            $where[] = array('user_id', $user_id);
        }
        if(!empty($op_admin)) {
            $where[] = ['op_admin', $op_admin];
        }
        if(!empty($conditions['start_time'])) {
            $where[] = [':ge', 'create_time', $conditions['start_time']];
        }
        if(!empty($conditions['end_time'])) {
            $where[] = [':le', 'create_time', $conditions['end_time']];
        }
        if(!empty($conditions['type'])) {
            $where[] = ['type', $conditions['type']];
        }
        if(!empty($conditions['issue_type'])) {
            $where[] = ['issue_type', $conditions['issue_type']];
        }
        $field = 'id, user_id, op_admin, issue_type, type, coupon_code, mibean, create_time';
        $count = $this->count($where);
        if(empty($count)) {
            return $result;
        }
        $result['total'] = $count;
        $list = $this->getRows($where, $field, $limit, $offset, $orderBy);
        $result['list'] = $list;
        return $result;
    }

    /*
     * 新增奖励记录
     */
    public function addGroupIssue($group_issue_info)
    {
        if (empty($group_issue_info)) {
            return false;
        }
        $res = $this->insert($group_issue_info);
        return $res;
    }
}