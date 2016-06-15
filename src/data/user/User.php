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
     * @return array
     */
    public function getUserInfoByIds($user_ids) {
        if (is_array($user_ids)) {
            $where[] = array(':in', 'id', $user_ids);
        } else {
            $where[] = array(':eq', 'id', $user_ids);
        }
        
        $field = "id,username,nickname,child_birth_day,user_status,cell_phone,child_sex,consume_money,icon,level,is_id_verified,is_cell_verified,mibean_level,create_date,status";
        
        $user_data = $this->getRows($where, $field);
        
        return $user_data;
    }
}
