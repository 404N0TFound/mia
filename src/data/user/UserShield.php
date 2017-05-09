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
    
    /**
     * 屏蔽用户
     */
    public function setUserShield($userInfo) {
        if (empty($userInfo)) {
            return false;
        }
        $data = $this->insert($userInfo);
        return $data;
    }
    
    /**
     * 更新屏蔽用户状态
     */
    public function updateShieldUserInfo($userInfo,$userId) {
        if (empty($userInfo) || empty($userId)) {
            return false;
        }
        $where = array();
        $where[] = ['user_id',$userId];
        $data = $this->update($userInfo,$where);
        return $data;
    }
    
    /**
     * 获取屏蔽用户
     */
    public function getShieldUserIdList($page=0,$limit=20) {
        $where = array();
        $where[] = ['status', 1];
        $where[] = ['type', $type];
        $orderBy = ['create_time DESC'];
        $userIdRes = $this->getRows($where, array('user_id'), $limit, $page, $orderBy);
        $userIdArr = array();
        if ($userIdRes) {
            foreach ($userIdRes as $value) {
                $userIdArr[] = $value['user_id'];
            }
        }
        return $userIdArr;
    }
}