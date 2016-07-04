<?php
namespace mia\miagroup\Data\Redbag;

use Ice;

class Redbagtadetail extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'redbag_ta_detail';

    protected $mapping = array();

    /**
     * 记录红包领取信息
     */
    public function addRedbagDetailInfo($redbagData) { 
        $data = $this->insert($redbagData);
        return $data;
    }

    /**
     * 查看用户是否领取过红包
     * @param unknown $redBagId
     * @param unknown $uid
     */
    public function isReceivedRedbag($redBagId, $uid, $status = array(1)) {
        $where = array();
        
        if (intval($redBagId) < 0 || intval($uid) < 0) {
            return false;
        }
        $where[] = ['user_id', $uid];
        if (!empty($status)) {
            $where[] = ['status', $status];
        }
        
        $where[] = ['redbag_id', $redBagId];
        $data = $this->getRow($where, 'id');
        return $data;
    }
}