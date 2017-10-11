<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

class GroupUserInfo extends DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'group_user_info';


    /*
     * 获取蜜芽圈用户收货地址(获取最新一条)
     * */
    public function getGroupUserDelAddress($user_id, $address_id)
    {

    }

    /*
     * 新增蜜芽圈用户收货地址
     * */
    public function addGroupUserDeliAddress($address_id, $conditions = [])
    {

    }
}
