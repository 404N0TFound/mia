<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

class GroupDoozer extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_doozer';

    protected $mapping = array();

    /**
     * 推荐列表
     * @return array() 推荐列表
     */
    public function getGroupDoozerList() {
        $where = array();
        $where[] = array(':eq', 'status', 1);
        $userIdRes = $this->getRows($where, array('user_id'));
        $userIdArr = array();
        if ($userIdRes) {
            foreach ($userIdRes as $value) {
                $userIdArr[] = $value['user_id'];
            }
        }
        return $userIdArr;
    }
}
