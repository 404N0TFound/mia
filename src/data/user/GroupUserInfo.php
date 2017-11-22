<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

class GroupUserInfo extends DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_user_info';

    /*
     * 获取蜜芽圈用户信息
     * */
    public function getGroupUserInfo($userIds)
    {
        $userInfos = [];
        if(empty($userIds)) {
            return false;
        }
        $where = [];
        $where[] = ['user_id', $userIds];
        $fields = 'user_id, address_id';
        $res = $this->getRows($where, $fields);
        if(empty($res)) {
            return false;
        }
        foreach($res as $value) {
            $userInfos[$value['user_id']] = $value;
        }
        return $userInfos;
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

    /*
     * 更新蜜芽圈用户信息
     * */
    public function updateGroupUserInfo($user_id, $setInfo)
    {
        if (empty($user_id) || empty($setInfo)) {
            return false;
        }
        $where = array();
        $setData = array();

        $where[] = ['user_id', $user_id];
        foreach ($setInfo as $k => $v) {
            if (in_array($k, ['ext_info'])) {
                $insertData[$k] = json_encode($v);
            }
        }
        $res = $this->update($setData, $where);
        return $res;
    }
}
