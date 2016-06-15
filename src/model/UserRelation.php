<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\User\AppUserRelation;

/**
 * Description of UserRelation
 *
 * @author user
 */
class UserRelation {

    public $appUserRelation = null;

    public function __construct() {
        $this->appUserRelation = new AppUserRelation();
    }

    /**
     * 批量获取我是否关注了用户
     *
     * @param type $loginUserId            
     * @param type $userIds            
     * @return type
     */
    public function getUserRelationWithMe($loginUserId, $userIds) {
        $data = $this->appUserRelation->getUserRelationWithMe($loginUserId, $userIds);
        return $data;
    }
    
    // 批量获取用户是否关注了我
    public function getMeRelationWithUser($loginUserId, $userIds) {
        $data = $this->appUserRelation->getMeRelationWithUser($loginUserId, $userIds);
        return $data;
    }
    
    // 批量获取粉丝数
    public function getCountBatchUserFanS($userIds) {
        $relationInfos = $this->appUserRelation->getCountBatchUserFanS($userIds);
        return $relationInfos;
    }
    
    // 获取用户的关注数
    public function getCountBatchUserAtten($userIds) {
        $relationInfos = $this->appUserRelation->getCountBatchUserAtten($userIds);
        return $relationInfos;
    }
}
