<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\UserRelation as UserRelationModel;
use mia\miagroup\Service\User as UserService;

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
    
    /**
     * 批量获取用户是否关注了我
     */
    public function getMeRelationWithUser($loginUserId, $userIds) {
        $relationStatus = $this->userRelationModel->getMeRelationWithUser($loginUserId, $userIds);
        return $this->succ($relationStatus);
    }
    
    /**
     * 批量获取粉丝数
     */
    public function countBatchUserFanS($userIds) {
        if (empty($userIds) || !is_array($userIds)) {
            return $this->succ(array());
        }
        $largeFansCountUser = \F_Ice::$ins->workApp->config->get('busconf.user.largeFansCountUser');
        $diffUserIds = array_diff($userIds, $largeFansCountUser);
        $relationInfos = $this->userRelationModel->getCountBatchUserFanS($diffUserIds);
        if (array_intersect($userIds, $largeFansCountUser)) {
            foreach ($largeFansCountUser as $uid) {
                $relationInfos[$uid] = 10000;
            }
        }
        return $this->succ($relationInfos);
    }
    
    /**
     * 批量获取关注数
     */
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
    public function addRelation($userId, $relationUserId, $source = 1)
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
        //是否首次关注
        $data = $this->userRelationModel->isExistRelation($userId, $relationUserId);
        if (empty($data)) {
            $isFirst = true;
        } else {
            $isFirst = false;
        }
        //$isFirst = $data === false ? true : false;
        
        //加关注
        $userRelation = $this->userRelationModel->addRelation($userId,$relationUserId, $source);

        if ($relationUserId != 1026069 && $source == 1 && $isFirst) { //蜜芽小天使账号不赠送蜜豆，不接收消息
            //送蜜豆
            $mibean = new \mia\miagroup\Remote\MiBean();
            $param['user_id'] = $userId;
            $param['relation_type'] = 'follow_me';
            $param['relation_id'] = $relationUserId;
            $param['to_user_id'] = $relationUserId;
            $mibean->add($param);

            //发消息
            $type = 'single'; //消息类型
            $resourceType = 'group';//消息资源
            $resourceSubType = 'follow';//消息资源子类型
            $sendFromUserId = $userId;//发送UserId
            $toUserId = $relationUserId;//接受UserId
            $news = new \mia\miagroup\Service\News();
            $sendMsgRes = $news->addNews($type, $resourceType, $resourceSubType, $sendFromUserId, $toUserId)['data'];
        }
        
        return $this->succ($userRelation);
    }
    
    /**
     * 取消关注
     */
    public function removeRelation($userId, $relationUserId)
    {
        $removeRelationRes = $this->userRelationModel->removeRelation($userId, $relationUserId);
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
    public function myAttenTion($userId, $currentUid = 0, $page = 1, $count = 20)
    {
        $arrResult = array('total' => 0,'user_list' =>array());
        //如果是蜜芽小天使账号数据只展示1W
        if ($page > 500 && $userId == 1026069){ //线上蜜芽小天使账号1026069
            return $this->succ($arrResult);
        }
        //获取关注列表
        $userIds = $this->userRelationModel->getAttentionListByUid($userId, $page, $count);
        //获取用户信息
        $userService = new UserService();
        $userInfos = $userService->getUserInfoByUids($userIds, $currentUid)['data'];
        //获取关注数
        $total = $this->userRelationModel->getCountBatchUserAtten(array($userId));
    
        $arrResult['total'] = isset($total[$userId]) ? intval($total[$userId]) : 0;
        $arrResult['user_list'] = !empty($userInfos) ? array_values($userInfos) : array();
        return $this->succ($arrResult);
    }
    
    /**
     * 我的粉丝
     */
    public function myFans($userId, $currentUid  = 0, $page = 1, $count = 20) {
        $arrResult = array('total' => 0,'user_list' =>array());
        //如果是蜜芽小天使账号数据只展示1W
        if ($page > 500 && $userId == 1026069){ //线上蜜芽小天使账号1026069
            return $this->succ($arrResult);
        }
        //获取粉丝列表
        $userIds = $this->userRelationModel->getFansListByUid($userId, $page, $count);
        //获取用户信息
        $userService = new UserService();
        $userInfos = $userService->getUserInfoByUids($userIds, $currentUid)['data'];
        //获取关注数
        $total = $this->userRelationModel->getCountBatchUserFans(array($userId));
        
        $arrResult['total'] = isset($total[$userId]) ? intval($total[$userId]) : 0;
        $arrResult['user_list'] = !empty($userInfos) ? array_values($userInfos) : array();
        return $this->succ($arrResult);
    }
    
    /**
     * 获取我关注的所有用户
     */
    public function getAllAttentionUser($userId) {
        if(empty($userId)){
            return $this->error(500);
        }
        $userIds = $this->userRelationModel->getAttentionListByUid($userId, 1, false);
        return $this->succ($userIds);
    }
    
    /**
     * 获取我关注所有专家列表
     */
    public function getAllAttentionExpert($userId) {
        if(empty($userId)){
            return $this->error(500);
        }
        $userIds = $this->getAllAttentionUser($userId)['data'];
        $expertUserIds = $this->userRelationModel->getAttentionExpertList($userIds);
        foreach ($userIds as $key => $value) {
            if(isset($expertUserIds[$key])){
                $data[$key] = $expertUserIds[$key];
            }
        }
        $data = !empty($data) ? $data : array();
        return $this->succ($data);
    }

    /**
     * 获取粉丝列表
     */
    public function getFansList($userId, $page, $count)
    {
        $userIds = $this->userRelationModel->getFansListByUid($userId, $page, $count);
        return $this->succ($userIds);
    }
}

