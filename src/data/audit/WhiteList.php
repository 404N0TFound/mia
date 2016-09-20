<?php
namespace mia\miagroup\Data\Audit;

use \DB_Query;

class WhiteList extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_white_list';

    protected $mapping = array();

    /**
     * 根据用户ID、devicetoken查询白名单信息
     */
    public function checkIsWhiteList($userId, $deviceToken) {
        $where = array();
        if (empty($userId) || empty($deviceToken)) {
            return false;
        }
        $where[] = array(':eq', 'user_id', $userId);
        $where[] = array(':eq', 'device_token', $deviceToken);
        $data = $this->getRow($where);
        return $data;
    }
}