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
        if (empty($insertData)) {
            return false;
        }
        foreach ($insertData as $k => $v) {
            if (in_array($k, ['ext_info'])) {
                $insertData[$k] = json_encode($v);
            }
        }
        $insert_id = $this->insert($insertData);
        return $insert_id;
    }
}
