<?php
namespace mia\miagroup\Data\Active;

use Ice;

class ActivePrizeRecordData extends \DB_Query {

    protected $dbResource = 'miagroup';
    protected $tableName = 'group_active_prize_record';


    /*
     * 获取活动奖励列表
     * */
    public function getActiveWinPrizeRecord($active_id, $user_id, $conditions = [])
    {
        if(empty($active_id)) {
            return false;
        }
        $where = [];
        $where[] = ['active_id', $active_id];
        $where[] = ['status', 1];
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
        if(!empty($conditions['type'])) {
            // 奖励类型
            $where[] = ['prize_type', $conditions['type']];
        }
        $field = 'prize_type,active_id,subject_id,user_id,prize_num';
        $orderBy = 'id desc';
        $arrRes = $this->getRows($where, $field, FALSE, 0, $orderBy);
        return $arrRes;
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
