<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\UserRelation\AppUserRelation;

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
    
    /**
     * 批量获取用户是否关注了我
     */
    public function getMeRelationWithUser($loginUserId, $userIds) {
        $data = $this->appUserRelation->getMeRelationWithUser($loginUserId, $userIds);
        return $data;
    }
    
    /**
     * 批量获取粉丝数
     */
    public function getCountBatchUserFanS($userIds) {
        $relationInfos = $this->appUserRelation->getCountBatchUserFanS($userIds);
        return $relationInfos;
    }
    
    /**
     * 批量获取用户的关注数
     */
    public function getCountBatchUserAtten($userIds) {
        $relationInfos = $this->appUserRelation->getCountBatchUserAtten($userIds);
        return $relationInfos;
    }
    
    /**
     * 获取两个用户之间的关系
     */
    public function getRelation($userId, $relationUserId) {
        $relationMe = $this->appUserRelation->getRelationByBothUid($userId, $relationUserId);
        $relationHim = $this->appUserRelation->getRelationByBothUid($relationUserId, $userId);
        $relation['relation_with_me'] = $relationMe ? intval($relationMe['status']) : 0;
        $relation['relation_with_him'] = $relationHim ? intval($relationHim['status']) : 0;
        return $relation;
    }
    
    /**
     * 是否关注过
     */
    public function isExistRelation($userId, $relationUserId) {
        $relation = $this->appUserRelation->getRelationByBothUid($userId, $relationUserId);
        return $relation;
    }
    
    /**
     * 加关注
     */
    public function addRelation($userId, $relationUserId, $source) {
        $meRelation = $this->appUserRelation->getRelationByBothUid($userId, $relationUserId);
        if (!empty($meRelation)) {
            //更新为关注状态
            $this->appUserRelation->updateRelationStatus($userId, $relationUserId, array('status' => 1, 'create_time' => date('Y-m-d H:i:s')));
        } else {
            //新的关注关系
            $setInfo = array(
                "user_id"           => $userId,
                "replation_user_id" => $relationUserId,
                "status"            => 1,
                "source"            => $source,
            );
            $insertRes = $this->appUserRelation->insertRelation($setInfo);
        }
        //更新状态查主库
        $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);
        $relation = $this->getRelation($userId, $relationUserId);
        \DB_Query::switchCluster($preNode);
        return $relation;
    }
    
    /**
     * 取消关注
     * @param unknown $userId
     * @param unknown $relationUserId
     */
    public function removeRelation($userId, $relationUserId) {
        $meRelation = $this->appUserRelation->getRelationByBothUid($userId, $relationUserId);
        if ($meRelation['status'] == 1) {
            //更新为非关注状态
            $this->appUserRelation->updateRelationStatus($userId, $relationUserId, array('status' => 0, 'cancle_time' => date('Y-m-d H:i:s')));
        }
        //更新状态查主库
        $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);
        $relation = $this->getRelation($userId, $relationUserId);
        \DB_Query::switchCluster($preNode);
        return $relation;
    }
    
    /**
     * 获取用户的关注列表
     */
    public function getAttentionListByUid($userId, $page = 0, $limit = 20) {
        $start = ($page - 1) * $limit;
        $data = $this->appUserRelation->getAttentionListByUid($userId, $start, $limit);
        return $data;
    }
    
    /**
     * 获取用户的粉丝列表
     */
    public function getFansListByUid($userId, $page = 0, $limit = 20) {
        $start = ($page - 1) * $limit;
        $data = $this->appUserRelation->getFansListByUid($userId, $start, $limit);
        return $data;
    }
}
