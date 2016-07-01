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
    public function isReceivedRedbag($redBagId, $uid) {
        $where = array();
        $where[] = ['user_id', $uid];
        $where[] = ['status', 1];
        $where[] = ['redbag_id', $redBagId];
        $data = $this->getRow($where, 'id');
        return $data;
    }
}