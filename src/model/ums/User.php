<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class User extends \DB_Query {

    protected $dbResource = 'miadefaultums';

    protected $tableUsers = 'users';
    
    /**
     * 根据username查询uid
     */
    public function getUidByUserName($userName) {
        if (empty($userName)) {
            return false;
        }
        if (mb_strlen($userName, 'utf8') > 18) {
            $userName = mb_substr($userName, 0, 18, 'utf8');
            $where[] = array(':like_begin','username', $userName);
        } else {
            $where[] = array(':eq','username', $userName);
        }
        
        $data = $this->getRow($where, 'id');
        if (!empty($data)) {
            return $data['id'];
        } else {
            return false;
        }
    }
    
    /**
     * 根据nickname查询uid
     */
    public function getUidByNickName($nickName) {
        if (empty($nickName)) {
            return false;
        }
        $where[] = array('nickname', $nickName);
        $data = $this->getRow($where, 'id');
        if (!empty($data)) {
            return $data['id'];
        } else {
            return false;
        }
    }
}