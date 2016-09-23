<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\User\User as UserData;
use mia\miagroup\Data\User\GroupSubjectUserExperts;
use mia\miagroup\Data\User\AppDeviceToken as AppDeviceTokenData;
use mia\miagroup\Data\User\HeadLineUserCategory as HeadLineUserCategoryData;

class User {

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
        $userData = new UserData();
        $data = $userData->addUser($userInfo);
        return $data;
    }
    
    /**
     * 设置头条抓取用户分类
     */
    public function setHeadlineUserCategory($userId, $category) {
        $headLineUserCategory = new HeadLineUserCategoryData();
        $data = $headLineUserCategory->getDataByUid($userId);
        if (!empty($data)) {
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
}
