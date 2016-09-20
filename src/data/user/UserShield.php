<?php
namespace mia\miagroup\Data\User;

class UserShield extends \DB_Query{
    
    protected $dbResource = 'miagroup';
    protected $tableName = 'user_shield';
    protected $mapping = [];
    
    //判断用户是否是屏蔽用户
    public function checkIsShieldByUserId($iUserId)
    {
        if (!is_numeric($iUserId) || intval($iUserId) <= 0) {
            return false;
        }
        $where[] = ['user_id', $iUserId];
        $where[] = ['status', 1];
        $userData = $this->getRow($where);
        if (!empty($$userData)) {
            return true;
        } else {
            return false;
        }
    }
    
}