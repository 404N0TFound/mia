<?php
namespace mia\miagroup\Data\Active;

use Ice;

class ActivePrizeRecordData extends \DB_Query {

    protected $dbResource = 'miagroup';
    protected $tableName = 'group_active_prize_record';


    /*
     * 获取活动奖励列表
     * */
    public function getActiveWinPrizeRecord($active_id, $user_id = 0, $limit = 20, $offset = 0, $conditions = [])
    {
        $return = ['list' => [], 'prize_num_list' => []];
        if(empty($active_id)) {
            return false;
        }
        $where = [];
        $groupBy = FALSE;
        $where[] = ['active_id', $active_id];
        if(!empty($user_id)) {
            $where[] = ['user_id', $user_id];
        }
        if(!empty($conditions['s_time'])) {
            // 开始时间
            $where[] = [':ge', 'create_time', $conditions['s_time']];
        }
        if(!empty($conditions['e_time'])) {
            // 结束时间
            $where[] = [':le', 'create_time', $conditions['e_time']];
        }
        if(!empty($conditions['prize_type'])) {
            // 奖励类型
            $where[] = ['prize_type', $conditions['prize_type']];
        }
        if(!empty($conditions['subject_id'])) {
            // 帖子
            $where[] = ['subject_id', $conditions['subject_id']];
        }
        if(!empty($conditions['group_by'])) {
            // 分组
            $groupBy = $conditions['group_by'];
        }
        if($conditions['type'] == 'count') {
            $field = 'user_id, sum(prize_num) as prize_num, subject_id';
            $arrRes = $this->getRows($where, $field, $limit = FALSE, $offset = 0, $orderBy = FALSE, $join = FALSE, $groupBy);
            $return['prize_num_list'] = $arrRes;
            return $return;
        }
        $orderBy = 'id DESC';
        $field = 'active_id, user_id, subject_id, prize_type, prize_num';
        $arrRes = $this->getRows($where, $field, $limit, $offset, $orderBy);
        $return['list'] = $arrRes;
        return $return;
    }

    /*
     * 新增活动奖励记录
     * */
    public function addActivePrizeRecord($insertData)
    {
        if (empty($insertData)) {
            return false;
        }
        $insert_id = $this->insert($insertData);
        return $insert_id;
    }

}
