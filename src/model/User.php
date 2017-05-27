<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\User\User as UserData;
use mia\miagroup\Data\User\GroupSubjectUserExperts;
use mia\miagroup\Data\User\AppDeviceToken as AppDeviceTokenData;
use mia\miagroup\Data\User\HeadLineUserCategory as HeadLineUserCategoryData;
use mia\miagroup\Data\User\GroupSubjectVideoPermission;
use mia\miagroup\Data\User\GroupDoozer as GroupDoozerData;

use mia\miagroup\Data\User\GroupUserCategory as UserCategoryData;
use mia\miagroup\Data\User\GroupUserPermission as UserPermissionData;


class User
{

    public function __construct()
    {
        $this->groupDoozerData = new GroupDoozerData();
    }
    /**
     * 批量获取用户信息
     *
     * @param type $user_ids            
     * @return type
     */
    public function getUserInfoByIds($user_ids) {
        $userData = new UserData();
        return $userData->getUserInfoByIds($user_ids);
    }
    
    /**
     * 批量获取专家信息
     */
    public function getBatchExpertInfoByUids($userIds) {
        $userExperts = new GroupSubjectUserExperts();
        $data = $userExperts->getBatchExpertInfoByUids($userIds);
        return $data;
    }
    
    /**
     * 批量获取视频权限
     */
    public function getVideoPermissionByUids($userIds) {
        $userExperts = new GroupSubjectVideoPermission();
        $data = $userExperts->getVideoPermissionByUids($userIds);
        return $data;
    }
    
    /**
     * 根据userid 获取是否需要发送push
     *
     * @param type $userIds            
     */
    public function getPushSwitchByUserIds($userIds) {
        $app_device_token_data = new AppDeviceTokenData();
        $data = $app_device_token_data->getPushSwitchByUserIds($userIds);
        return $data;
    }

    /**
     * 根据userId获取deviceToken
     *
     * @return array
     * @author 
     **/
    public function getDeviceTokenByUserId($userId)
    {
        $appDeviceTokenData = new AppDeviceTokenData();
        $data = $appDeviceTokenData->getDeviceTokenByUserId($userId);
        return $data;
    }
    
    /**
     * 根据nickname获取用户id
     */
    public function getUidByNickName($nickName) {
        $userData = new UserData();
        $userId = $userData->getUidByNickName($nickName);
        return $userId;
    }
    
    /**
     * 根据username获取用户id
     */
    public function getUidByUserName($userName) {
        $userData = new UserData();
        $userId = $userData->getUidByUserName($userName);
        return $userId;
    }
    
    /**
     * 新增用户
     */
    public function addUser($userInfo) {
        if (empty($userInfo['username'])) {
            return false;
        }
        $userData = new UserData();
        $isExist = $userData->getUidByUserName($userInfo['username']);
        if ($isExist) {
            $data = $isExist;
        } else {
            $data = $userData->addUser($userInfo);
        }
        return $data;
    }
    
    /**
     * 设置头条抓取用户分类
     */
    public function setHeadlineUserCategory($userId, $category) {
        $headLineUserCategory = new HeadLineUserCategoryData();
        $data = $headLineUserCategory->getDataByUid($userId);
        if (!empty($data)) {
            $setData[] = array('category', $category);
            $headLineUserCategory->setDataByUid($userId, $setData);
            return $data['id'];
        } else {
            $data = $headLineUserCategory->addUserCategory($userId, $category);
            return $data;
        }
    }
    
    /**
     * 新增专家
     */
    public function addExpert($expertInfo) {
        $expertData = new GroupSubjectUserExperts();
        $data = $expertData->addExpert($expertInfo);
        return $data;
    }
    
    /**
     * 根据用户ID更新用户信息
     */
    public function updateUserById($userId, $userInfo) {
        $userData = new UserData();
        $res = $userData->updateUserById($userId, $userInfo);
        return $res;
    }
    
    /**
     * 更新专家信息
     */
    public function updateExpertInfoByUid($userId, $expertInfo) {
        if (empty($userId)) {
            return false;
        }
        $setData = array();
        if (!empty($expertInfo['desc'])) {
            $setData[] = array('desc', implode('#', $expertInfo['desc']));
        }
        if (!empty($expertInfo['label'])) {
            $setData[] = array('label', implode('#', $expertInfo['label']));
        }
        if (isset($expertInfo['status'])) {
            $setData[] = array('status', $expertInfo['status']);
        }
        if (isset($expertInfo['modify_author'])) {
            $setData[] = array('modify_author', $expertInfo['modify_author']);
        }
        if (!empty($setData)) {
            $setData[] = array('last_modify', date('Y-m-d H:i:s'));
        }
        $expertData = new GroupSubjectUserExperts();
        $result = $expertData->updateExpertInfoByUid($userId, $setData);
        return $result;
    }

    /**
     * 获取达人推荐理由
     * @param $userIds
     * @return array
     */
    public function getUserRecommendInfo($userIds)
    {
        $doozerData = new GroupDoozerData();
        $conditions['user_id'] = $userIds;
        $recommendInfos = $doozerData->getBatchOperationInfos($conditions);
        foreach ($recommendInfos as $v) {
            $res[$v['user_id']] = $v;
        }
        return $res;
    }

    /**
     * 查推荐用户列表
     * @params array()
     * @return array() 推荐用户列表
     */
    public function getGroupDoozerList($count = 10)
    {
        return $this->groupDoozerData->getGroupDoozerList($count);
    }
    
    /**
     * 获取分类用户id列表
     * @params array()
     * @return array() 推荐用户列表
     */
    public function getGroupUserIdList($count, $page = 1)
    {
        $userCategory = new UserCategoryData();
        $offset = ($page - 1) * $count;
        $data = $userCategory->getGroupUserIdList($count, $offset);
        return $data;
    }
    
    /**
     * 批量获取分类用户信息
     */
    public function getBatchUserCategory($userIds, $status=array(1)) {
        $userCategory = new UserCategoryData();
        $data = $userCategory->getBatchUserInfoByUids($userIds, $status);
        return $data;
    }
    
    /**
     * 新增用户分类
     */
    public function addCategory($userInfo) {
        $userCategory = new UserCategoryData();
        $data = $userCategory->addCategory($userInfo);
        return $data;
    }
    
    /**
     * 更新用户分类信息
     */
    public function updateUserCategory($userId, $updata) {
        if (empty($userId)) {
            return false;
        }
        
        $userCategory = new UserCategoryData();
        $result = $userCategory->updateUserInfoByUid($userId, $updata);
        return $result;
    }
    
    /**
     * 批量获取用户权限信息
     */
    public function getBatchUserPermission($conditions) {
        $userPermission = new UserPermissionData();
        $data = $userPermission->getUserPermissionByUids($conditions);
        return $data;
    }
    
    /**
     * 新增用户权限
     */
    public function addPermission($userInfo) {
        $userPermission = new UserPermissionData();
        $data = $userPermission->addPermission($userInfo);
        return $data;
    }

    /**
     * 更新用户权限信息
     */
    public function updateUserPermission($userId, $updata, $type) {
        if (empty($userId)) {
            return false;
        }
    
        $userPermission = new UserPermissionData();
        $data = $userPermission->updatePermissionByUid($userId, $updata, $type);
        return $data;
    }
    
    /**
     * 更新达人发布排行榜
     */
    public function updateDoozerRank($type, $rank_list) {
        if (!in_array($type, array('pub_day', 'pub_month')) || empty($rank_list)) {
            return false;
        }
        // 获取rediskey
        $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.userKey.user_doozer_rank.key'), $type);
        $redis = new \mia\miagroup\Lib\Redis();
        //先获取所有数据
        $count = $redis->zcount($key, '-inf', '+inf');
        $data = $redis->zrange($key, 0, $count, true);
        if (!empty($data)) {
            foreach ($data as $user_id => $pub_count) {
                //剔除已掉出榜外的用户
                if (!isset($rank_list[$user_id]) || $rank_list[$user_id] < 5) {
                    echo $user_id, "\n";
                    $redis->zrem($key, $user_id);
                }
            }
        }
        foreach ($rank_list as $user_id => $pub_count) {
            if (empty($user_id) || intval($pub_count) < 5) {
                continue;
            }
            $redis->zadd($key, $pub_count, $user_id);
        }
        $redis->expire($key, \F_Ice::$ins->workApp->config->get('busconf.rediskey.userKey.user_doozer_rank.expire_time'));
        return true;
    }
    
    /**
     * 读取达人发布排行榜
     */
    public function getDoozerRank($type, $page = 1, $count = 10) {
        // 获取rediskey
        $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.userKey.user_doozer_rank.key'), $type);
        $redis = new \mia\miagroup\Lib\Redis();
        $offset = ($page - 1) * $count;
        $data = $redis->zrevrange($key, $offset, $count, true);
        return $data;
    }
    
    /**
     * 更新用户热帖
     */
    public function updateUserHotSubject($user_id, $subject_ids) {
        if (intval($user_id) <= 0 || empty($subject_ids) || !is_array($subject_ids)) {
            return false;
        }
        // 获取rediskey
        $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.userKey.user_hot_subjects.key'), $user_id);
        $redis = new \mia\miagroup\Lib\Redis();
        $redis->set($key, $subject_ids);
        $redis->expire($key, \F_Ice::$ins->workApp->config->get('busconf.rediskey.userKey.user_hot_subjects.expire_time'));
        return true;
    }
    
    /**
     * 读取用户热帖
     */
    public function getUserHotSubjects($user_ids) {
        if (empty($user_ids) || !is_array($user_ids)) {
            return array();
        }
        $keys = [];
        foreach ($user_ids as $user_id) {
            $keys[] = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.userKey.user_hot_subjects.key'), $user_id);
        }
        $redis = new \mia\miagroup\Lib\Redis();
        $data = $redis->mget($keys);
        $result = array();
        foreach ($user_ids as $user_id) {
            $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.userKey.user_hot_subjects.key'), $user_id);
            if (!empty($data[$key])) {
                $result[$user_id] = $data[$key];
            }
        }
        return $result;
    }
}
