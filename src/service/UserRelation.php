<?php
namespace mia\miagroup\Service;

use \FS_Service;
use mia\miagroup\Model\UserRelation as UserRelationModel;

class UserRelation extends FS_Service {

    public $userRelationModel = null;

    public function __construct() {
        $this->userRelationModel = new UserRelationModel();
    }

    /**
     * 批量获取我是否关注了用户
     */
    public function getUserRelationWithMe($loginUserId, $userIds) {
        if (!isset($loginUserId) || !$loginUserId || !isset($userIds) || empty($userIds)) {
            return $this->succ();
        }
        
        $relationStatus = $this->userRelationModel->getUserRelationWithMe($loginUserId, $userIds);
        return $this->succ($relationStatus);
    }
    
    // 批量获取用户是否关注了我
    public function getMeRelationWithUser($loginUserId, $userIds) {
        $relationStatus = $this->userRelationModel->getMeRelationWithUser($loginUserId, $userIds);
        return $this->succ($relationStatus);
    }
    
    // 批量获取粉丝数
    public function countBatchUserFanS($userIds) {
        if (!isset($userIds) || empty($userIds)) {
            return $this->succ();
        }
        
        $relationInfos = $this->userRelationModel->getCountBatchUserFanS($userIds);
        return $this->succ($relationInfos);
    }
    
    // 批量获取关注数
    public function countBatchUserAtten($userIds) {
        if (!isset($userIds) || empty($userIds)) {
            return $this->succ();
        }
        $relationInfos = $this->userRelationModel->getCountBatchUserAtten($userIds);
        return $this->succ($relationInfos);
    }
}

