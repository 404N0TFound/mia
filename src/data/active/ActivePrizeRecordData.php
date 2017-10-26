<?php
namespace mia\miagroup\Data\Active;

use Ice;

class ActivePrizeRecordData extends \DB_Query {

    protected $dbResource = 'miagroup';
    protected $tableName = 'group_active_prize_record';


    /*
     * 获取活动奖励列表
     * */
    public function getActiveWinPrizeRecord($active_id, $user_id, $limit = 20, $offset = 0, $conditions = [])
    {
        $return = ['list' => [], 'prize_num' => 0];
        if(empty($active_id)) {
            return false;
        }
        $where = [];
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
        if($conditions['type'] == 'count') {
            $field = 'user_id, sum(prize_num) as prize_num';
            $arrRes = $this->getRow($where, $field);
            $return['prize_num'] = $arrRes['prize_num'];
            return $return;
        }
        $orderBy = 'id DESC';
        $arrRes = $this->getRows($where, '*', $limit, $offset, $orderBy);
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
