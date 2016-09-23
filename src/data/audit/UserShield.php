<?php
namespace mia\miagroup\Data\Audit;

use \DB_Query;

class UserShield extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'user_shield';

    protected $mapping = array();

    /**
     * 根据用户ID查询屏蔽状态
     */
    public function getUserShieldByUid($userId) {
        $where = array();
        if (empty($userId)) {
            return false;
        }
        $where[] = array(':eq', 'user_id', $userId);
        $data = $this->getRow($where);
        return $data;
    }
}