<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

class User extends DB_Query {

    protected $dbResource = 'miagroup';

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
    public function getUserInfoByIds($user_ids, $is_shield = false) {
        if ($is_shield) {
            
            if (is_array($user_ids)) {
                $user_ids_str = implode(',', $user_ids);
            } else {
                $user_ids_str = $user_ids;
            }
            
            $sql = 'select u.id,u.username,u.nickname,u.child_birth_day,u.user_status,u.cell_phone,u.child_sex,u.child_nickname,u.consume_money,u.icon,u.level,u.is_id_verified,u.is_cell_verified,u.mibean_level,u.create_date,u.status from users as u left join user_shield as us on u.id=us.user_id where (us.user_id is null or us.status = 0) and u.id in (' . $user_ids_str . ')';
            $user_data = $this->query($sql);
        } else {
            
            $field = "id,username,nickname,child_birth_day,user_status,cell_phone,child_sex,child_nickname,consume_money,icon,level,is_id_verified,is_cell_verified,mibean_level,create_date,status";
            $where[] = array('id', $user_ids);
            $user_data = $this->getRows($where, $field);
        }
        
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
        $where[] = array('username', $userName);
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
