<?php
namespace mia\miagroup\Data\Active;

use Ice;

class ActivePrizeRecordData extends \DB_Query {

    protected $dbResource = 'miagroup';
    protected $tableName = 'group_active_win_prize_record';


    /*
     * 获取活动奖励列表
     * */
    public function getActiveWinPrizeRecord($active_id, $user_id, $conditions = [])
    {
        if(empty($active_id)) {
            return false;
        }
        $where = [];
        $where[] = ['status', 1];
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
        if(!empty($conditions['type'])) {
            // 奖励类型
            $where[] = ['prize_type', $conditions['type']];
        }
        $field = 'prize_type,active_id,subject_id,user_id,prize_num';
        $orderBy = 'id desc';
        $arrRes = $this->getRows($where, $field, FALSE, 0, $orderBy);
        $prize_num = 0;
        if (!empty($arrRes)) {
            foreach ($arrRes as $res) {
                $prize_num += intval($res['prize_num']);
            }
        }
        $result = ['prize_list' => $arrRes, 'prize_num' => $prize_num];
        return $result;
    }

}
