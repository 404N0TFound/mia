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
    /**
     * 添加关注
     * @param unknown $userId
     * @param unknown $relationUserId
     * @param number $srouce 新增srouce 参数默认是1 当传递2时 表示是自动关注
     * @return boolean|multitype:number
     */
//     public function save($userId , $relationUserId , $source = 1 ){
//         return $this->appUserRelation->save($userId, $relationUserId, $source);
//     }
    
    /**
     * 判断关注关系
     * @param unknown $iUserId
     * @param unknown $followdUserId
     */
    public function checkUserIsFollowdUser($iUserId, $followdUserId, $status=1)
    {
        return $this->appUserRelation->checkUserIsFollowdUser($iUserId, $followdUserId,$status);
    }
    
    /**
     * 更新关注状态
     * @param unknown $userId
     * @param unknown $relationUserId
     */
    public function updateRelationStatus($userId,$relationUserId,$status=0){
        return $this->appUserRelation->updateRelationStatus($userId,$relationUserId,$status);
    }
    
    /**
     * insert
     */
    public function insertRelation($setData){
        return $this->appUserRelation->insertRelation($setData);
    }
    
    
    /**
     * 取消关注
     * @param unknown $userId
     * @param unknown $relationUserId
     */
    public function remove($userId, $relationUserId)
    {
        return $this->appUserRelation->remove($userId, $relationUserId);
    }
    
    /**用户粉丝与关注关系
     * @param int $relationId被关注用户
     * @param int $userId关注用户
     * @param $page
     * @param $iPageSize
     * @return array|bool
     */
    public function userRelaption($relationId=0, $userId=0, $page, $iPageSize)
    {
        return $this->appUserRelation->userRelaption($relationId, $userId, $page, $iPageSize);
    }
    
    /**
     * @param int $userId关注用户id （查看关注时必须存在）
     * @param int $relationId被关注用户id （查看粉丝时必须存在）
     * @param $page
     * @param $iPageSize
     * @return array|bool
     */
    public function NotLogUserRelaption($userId = 0, $relationId = 0, $page, $iPageSize) {
        return $this->appUserRelation->NotLogUserRelaption($userId, $relationId, $page, $iPageSize);
    }
    

    public function getOtherUserAttenList($loginUserId, $userId, $page, $iPageSize){
        return $this->appUserRelation->getOtherUserAttenList($loginUserId, $userId, $page, $iPageSize);
    }
    
}
