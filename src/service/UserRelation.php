<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\UserRelation as UserRelationModel;

class UserRelation extends \mia\miagroup\Lib\Service {

    public $userRelationModel = null;

    public function __construct() {
        parent::__construct();
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
    
    /**
     * 关注用户
     * @param 关注人 $userId
     * @param 被关注人 $toUserId
     */
    public function addAttention($userId,$relationUserId, $source=1)
    {
        //判断当前加关注是否自己
        if($userId == $relationUserId){
            return $this->error(1315);
        }
        //判断关注人是否达到上限 2000
        $checkAchieveCeiling = $this->userRelationModel->getCountBatchUserAtten($userId)[$userId];
        if ($checkAchieveCeiling >= 2000) {
            return $this->error(1324);
        }
        //初始化数据
        $userRelationArr = array("relation_with_me" => 1,"relation_with_him" => 0);
        //判断我所关注用户是否关注我
        $followMeRes = $this->userRelationModel->checkUserIsFollowdUser($userId, $relationUserId);
        if ($followMeRes) {
            $userRelationArr['relation_with_him'] = 1;
        }
        //如果是重复关注
        $followHimCount = $this->userRelationModel->checkUserIsFollowdUser($relationUserId, $userId);
        if ($followHimCount) {
            return $this->succ($userRelationArr);
        }
        
        //取消关注后再次加关注
        $newFollowHimCount = $this->userRelationModel->checkUserIsFollowdUser($relationUserId, $userId, 0);
        if ($newFollowHimCount) {
            //更新为关注状态
            $this->userRelationModel->updateRelationStatus($userId,$relationUserId);
        } else {
            //新的关注关系
            $setInfo = array(
                "user_id"           => $userId,
                "replation_user_id" => $relationUserId,
                "status"            => 1,
                "source"            => $source,
            );
            $insertRes = $this->userRelationModel->insertRelation($setInfo);
            //送蜜豆，蜜芽小天使账号不赠送蜜豆
            if ($relationUserId != 1026069) {
                $mibean = new \mia\miagroup\Remote\MiBean();
                $param['user_id'] = $userId;
                $param['relation_type'] = 'follow_me';
                $param['relation_id'] = $relationUserId;
                $param['to_user_id'] = $relationUserId;
                $mibean->add($param);
            }
        }
        //发消息
        $type = 'single'; //消息类型
        $resourceType = 'group';//消息资源
        $resourceSubType = 'follow';//消息资源子类型
        $sendFromUserId = $userId;//发送UserId
        $toUserId = $relationUserId;//接受UserId
        $news = new \mia\miagroup\Service\News();
        $sendMsgRes = $news->addNews($type, $resourceType, $resourceSubType, $sendFromUserId, $toUserId)['data'];

        if(!$userRelationArr){
            return $this->error(1301);
        }else{
            return $this->succ($userRelationArr);
        }
    }
    
    /**
     * 取消关注
     */
    public function removeRelation($userId, $relationUserId)
    {
        $removeRelationRes = $this->userRelationModel->remove($userId, $relationUserId);
        if ($removeRelationRes) {
            return $this->succ($removeRelationRes);
        } else {
            return $this->error(1302);
        }
    }
    
    /**
     * 我的关注
     * @param $userId 用户id
     * @param $currentUid 登录用户
     * @return array
     */
    public function myAttenTion($userId, $currentUid=0, $page=1, $count=20)
    {
        $arrResult = array('total' => 0,'user_list' =>array());
        //如果是蜜芽小天使账号数据只展示1W
        if ($page > 500 && $userId == 1026069){ //线上蜜芽小天使账号1026069
            return $this->succ($arrResult);
        }
        if ($currentUid == $userId) {
            $userInfo = $this->userRelationModel->userRelaption(0, $currentUid, $page, $count);
        } else {
            if (!$userId) {
                return $this->error(500);
            }
            //用户未登陆时查看别人空间关注用户
            if (!$currentUid) {
                $notLoginUserRelationRes = $this->userRelationModel->NotLogUserRelaption($userId, 0, $page, $count);
                $userInfo = $notLoginUserRelationRes;
            } else {
                $userInfo = $this->userRelationModel->getOtherUserAttenList($currentUid, $userId, $page, $count);
            }
        }
    
        $arrResult['total'] = $userInfo['total'];
        unset($userInfo['total']);
        $arrResult['user_list'] = $userInfo;
        return $this->succ($arrResult);
    }
    
    
}

