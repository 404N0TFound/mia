<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

class GroupUserInfo extends DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'group_user_info';


    /*
     * 获取蜜芽圈用户信息
     * */
    public function getGroupUserInfo($userIds)
    {

    }

    /*
     * 新增蜜芽圈用户信息
     * */
    public function addGroupUserInfo($insertData)
    {
        $data = $this->insert($insertData);
        return $data;
    }
}
