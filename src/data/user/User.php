<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

class User extends DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'users';

    protected $mapping = [];

    /**
     * 获取用户的基本信息
     *
     * @param mix $user_ids
     *            用户ids
     *@param bool $is_shield 是否屏蔽屏蔽用户
     * @return array
     */
    public function getUserInfoByIds($user_ids) {    
        $field = "id,username,nickname,child_birth_day,user_status,cell_phone,child_sex,child_nickname,consume_money,icon,level,is_id_verified,is_cell_verified,mibean_level,create_date,status";
        $where[] = array('id', $user_ids);
        $user_data = $this->getRows($where, $field);
        return $user_data;
    }
    
    /**
     * 新增用户
     */
    public function addUser($userInfo) {
        $data = $this->insert($userInfo);
        return $data;
    }
    
    /**
     * 根据userId更新用户信息
     */
    public function updateUserById($userId, $userInfo) {
        $where = array();
        $where[] = array('id', $userId);
        $data = $this->update($userInfo, $where);
        return $data;
    }
    
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
