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
    public function getBatchUserInfoByUids($conditions) {
        $result = array();
        $where = array();
        $where[] = ['status', $conditions['status']];
        if (!empty($conditions) && isset($conditions['user_id'])) {
            $where[] = ['user_id', $conditions['user_id']];
        }
        $users = $this->getRows($where);
        if (!empty($users)) {
            foreach ($users as $user) {
                $result[$user['type']][$user['user_id']] = $user;
                if($user['category'] == '专家'){
                    $result[$user['type']][$user['user_id']]['is_expert'] = 1;
                }
                if(!empty($user['ext_info'])){
                    $extInfo = json_decode($user['ext_info'],true);
                    if(isset($extInfo['desc']) && !empty($extInfo['desc'])){
                        $result[$user['type']][$user['user_id']]['desc'] = $extInfo['desc'];
                    }
                    if(isset($extInfo['label']) && !empty($extInfo['label'])){
                        $result[$user['type']][$user['user_id']]['label'] = $extInfo['label'];
                    }
                    if(isset($extInfo['last_modify']) && !empty($extInfo['last_modify'])){
                        $result[$user['type']][$user['user_id']]['last_modify'] = $extInfo['last_modify'];
                    }
                    if(isset($extInfo['modify_author']) && !empty($extInfo['modify_author'])){
                        $result[$user['type']][$user['user_id']]['modify_author'] = $extInfo['modify_author'];
                    }
                    if(isset($extInfo['answer_nums']) && !empty($extInfo['answer_nums'])){
                        $result[$user['type']][$user['user_id']]['answer_nums'] = $extInfo['answer_nums'];
                    }
                }
                unset($result[$user['type']][$user['user_id']]['ext_info']);
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
    public function getGroupUserIdList($count = 10)
    {
        $where = array();
        $where[] = ['status', 1];
        $where[] = ['type', 'doozer'];
//         $where[] = ['category', ''];
        $orderBy = ['create_time DESC'];
        $userIdRes = $this->getRows($where, array('user_id'), $count, 0, $orderBy);
        $userIdArr = array();
        if ($userIdRes) {
            foreach ($userIdRes as $value) {
                $userIdArr[] = $value['user_id'];
            }
        }
        return $userIdArr;
    }
    
}
