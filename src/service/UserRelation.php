<?php
namespace mia\miagroup\Service;

use \F_Ice;
use mia\miagroup\Model\UserRelation as UserRelationModel;
use mia\miagroup\Service\User as UserService;
use mia\miagroup\Lib\Redis;
use mia\miagroup\Util\NormalUtil;
use mia\miagroup\Service\Audit as AuditService;

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
                $relationInfos[$uid] = 100000;
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
     * @param  $userId int 关注人
     * @param  $relationUserId int 被关注人
     * @param $source int 1是用户关注 2是注册程序关注
     * @return
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
        //获取用户信息
        $auditService = new AuditService();
        $userDubiousStatus = $auditService->checkUserIsDubious($userId)['data'];
        //可疑用户每分钟只能关注5次
        if($userDubiousStatus['is_dubious'] == 1){
            $userRelationKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.userKey.user_attention_dubious.key'),$userId);
            $redis = new Redis();
            //判断是否加过关注
            $checkAttention = $redis->exists($userRelationKey);
            if($checkAttention){
                //获取关注次数
                $count = $redis->get($userRelationKey);
                //如果关注计数超过5，则报错提示
                if($count >= 5){
                    return $this->error(1303);
                }else{
                    //加关注
                    $userRelation = $this->userRelationModel->addRelation($userId,$relationUserId, $source);
                    //计数
                    $redis->incr($userRelationKey);
                }
            }else{
                //加关注
                $userRelation = $this->userRelationModel->addRelation($userId,$relationUserId, $source);
                //计数
                $redis->incr($userRelationKey);
                //限制时间为60秒
                $redis->expire($userRelationKey,60);
            }
        }else{
            //加关注
            $userRelation = $this->userRelationModel->addRelation($userId,$relationUserId, $source);
        }

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
            $news->postMessage("follow", $toUserId, $sendFromUserId);
        }
        if (in_array($relationUserId, \F_Ice::$ins->workApp->config->get('busconf.userrelation.task_follow'))) {
            $taskService = new GroupTask();
            $taskService->checkFollowTask($userId);
        }
        return $this->succ($userRelation);
    }
    
    /**
     * 互相关注
     */
    public function followEachOther($userId, $relationUserId, $source = 1) {
        $this->addRelation($userId, $relationUserId, $source);
        $this->addRelation($relationUserId, $userId, $source);
        return $this->succ(true);
    }

    /**
     * 自动关注
     * @param $userId
     * @param $type string "register"注册关注  "daren"达人一键关注
     * @return mixed
     */
    public function addAutoFollow($userId, $type = 'register')
    {
        if (empty(intval($userId))) {
            return $this->succ([]);
        }
        $auto_operate = 1;
        switch ($type) {
            case 'register':
                $autoFollow = \F_Ice::$ins->workApp->config->get('busconf.userrelation.register_auto_follow');
                break;
            case 'feed':
                $all_auto_follow = $this->getAllAttentionUser($userId, 2, null)['data'];
                $autoFollow = \F_Ice::$ins->workApp->config->get('busconf.userrelation.feed_auto_follow');
                $auto_followed = array_intersect($all_auto_follow, $autoFollow);
                if (!empty($auto_followed)) {
                    //默认关注过，不再继续关注
                    break;
                }
                //官方认证账号，概率性默认关注
                $user_service = new \mia\miagroup\Service\User();
                $offical_cert_users = $user_service->getDoozerByCategory('official_cert')['data'];
                $other_cert_users = array_diff($offical_cert_users, $autoFollow);
                if (!empty($other_cert_users)) {
                    foreach ($other_cert_users as $v) {
                        if (rand(0, 100) <= 10) {
                            $autoFollow[] = $v;
                        }
                    }
                }
                break;
            case 'daren':
                $autoFollow = \F_Ice::$ins->workApp->config->get('busconf.userrelation.follow_daren');
                $auto_operate = 0;
                break;
        }
        if (!empty($autoFollow)) {
            foreach ($autoFollow as $relationUid) {
                $this->userRelationModel->addRelation($userId, $relationUid, 2, $auto_operate);
            }
        }
        if (!empty(array_intersect(\F_Ice::$ins->workApp->config->get('busconf.userrelation.task_follow'), $autoFollow))) {
            $taskService = new GroupTask();
            $taskService->checkFollowTask($userId);
        }
        return $this->succ([]);
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
    public function getAllAttentionUser($userId, $source = null, $status = 1) {
        if(empty($userId)){
            return $this->error(500);
        }
        $userIds = $this->userRelationModel->getAttentionListByUid($userId, 1, false, $source, $status);
        return $this->succ($userIds);
    }
    
    /**
     * 获取我关注所有专家列表
     */
    public function getAllAttentionExpert($userId) {
        if(empty($userId)){
            return $this->error(500);
        }
        $data = array();
        $userIds = $this->getAllAttentionUser($userId)['data'];
        if(empty($userIds)){
            return $this->succ($data);
        }
        $userService = new UserService();
        $expertUserIds = $userService->getBatchCategoryUserInfo($userIds)['data']['doozer'];
        if(empty($expertUserIds)){
            return $this->succ($data);
        }
        foreach ($userIds as $key => $value) {
            if(isset($expertUserIds[$key])){
                $data[$key] = $expertUserIds[$key];
            }
        }
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

    /**
     * 获取用户关注任务，完成情况
     */
    public function getUserTaskFollow($userIds, $followIds)
    {
        if (empty($userIds) || empty($followIds)) {
            return $this->succ([]);
        }
        $check_result = $this->userRelationModel->getUserFollowNum($userIds, $followIds);

        $succ_num = count($followIds);
        $return = [];
        foreach ($userIds as $userId) {
            if (array_key_exists($userId, $check_result) && $check_result[$userId]['num'] == $succ_num) {
                $return[$userId] = [
                    'succ' => 1,
                    'time' => $check_result[$userId]['create_time']
                ];
            } else {
                $return[$userId] = [
                    'succ' => 0,
                    'time' => ""
                ];
            }
        }
        return $this->succ($return);
    }
}

