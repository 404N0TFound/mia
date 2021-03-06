<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

/**
 * 蜜芽圈用户分类表
 *
 * @author user
 */
class GroupUserCategory extends DB_Query {

    protected $tableName = 'group_user_category';

    protected $dbResource = 'miagroup';

    /**
     * 新增用户类型
     */
    public function addCategory($userInfo) {
        $data = $this->insert($userInfo);
        return $data;
    }
    
    /**
     * 批量获取分类用户信息
     */ 
    public function getBatchUserInfoByUids($userIds, $status=array(1)) {
        if (empty($userIds)) {
            return array();
        }
        $result = array();
        $where = array();
        $where[] = ['user_id', $userIds];
        if (!empty($status)) {
            $where[] = ['status', $status];
        }
        $users = $this->getRows($where);
        if (!empty($users) && is_array($users)) {
            foreach ($users as $user) {
                $result[$user['user_id']] = $user;
                if(!empty($user['ext_info'])){
                    $extInfo = json_decode($user['ext_info'],true);
                }
                if(isset($extInfo['label']) && !empty($extInfo['label'])){
                    $result[$user['user_id']]['label'] = $extInfo['label'];
                }
                if(isset($extInfo['desc']) && !empty($extInfo['desc'])) {
                    $extInfo['desc'] = str_replace('#', ' ', $extInfo['desc']);
                    $result[$user['user_id']]['desc'] = trim($extInfo['desc']);
                }
            }
        }
        return $result;
    }
    
    /**
     * 修改用户分类信息
     */
    public function updateUserInfoByUid($userId, $userInfo) {
        if (empty($userId)) {
            return false;
        }
        $where = array();
        $where[] = ['user_id', $userId];
        $data = $this->update($userInfo, $where);
        return $data;
    }
    
    /**
     *批量获取达人用户id
     * @return array() 达人用户id列表
     */
    public function getGroupUserIdList($type, $offset = 0, $count = 10)
    {
        if (empty($type)) {
            return [];
        }
        $where = array();
        $where[] = ['status', 1];
        $where[] = ['type', $type];
        $orderBy = ['create_time DESC'];
        $userIdRes = $this->getRows($where, array('user_id'), $count, $offset, $orderBy);
        $userIdArr = array();
        if ($userIdRes) {
            foreach ($userIdRes as $value) {
                $userIdArr[] = intval($value['user_id']);
            }
        }
        return $userIdArr;
    }
    
}
