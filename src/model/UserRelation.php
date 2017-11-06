<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\UserRelation\AppUserRelation;
use mia\miagroup\Data\User\GroupSubjectUserExperts;
/**
 * Description of UserRelation
 *
 * @author user
 */
class UserRelation {

    public $appUserRelation = null;
    public $groupSubjectUserExperts;
    public function __construct() {
        $this->appUserRelation = new AppUserRelation();
        $this->groupSubjectUserExperts = new GroupSubjectUserExperts();
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
    public function addRelation($userId, $relationUserId, $source, $auto_follow = 0) {
        $meRelation = $this->appUserRelation->getRelationByBothUid($userId, $relationUserId);
        if (!empty($meRelation)) {
            //如果是默认关注，则已关注过不再关注
            if ($auto_follow == 0) {
                //更新为关注状态
                $this->appUserRelation->updateRelationStatus($userId, $relationUserId, array('status' => 1, 'create_time' => date('Y-m-d H:i:s')));
            }
        } else {
            //新的关注关系
            $setInfo = array(
                "user_id"           => $userId,
                "replation_user_id" => $relationUserId,
                "status"            => 1,
                "source"            => $source,
            );
            $insertRes = $this->appUserRelation->insertRelation($setInfo);
            //关注官方账号，加1蜜豆
            //长文账号，奖励1蜜豆
            $followIds = \F_Ice::$ins->workApp->config->get('busconf.userrelation.task_follow');
            if(in_array($relationUserId,$followIds)) {
                //送蜜豆
                $mibean = new \mia\miagroup\Remote\MiBean();
                $param['user_id'] = $relationUserId;
                $param['relation_type'] = 'follow_me';
                $param['relation_id'] = $userId;
                $param['to_user_id'] = $userId;
                $res = $mibean->add($param);
                var_dump($res);
            }
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
    public function getAttentionListByUid($userId, $page = 0, $limit = 20, $source = null, $status = 1) {
        if($limit){
            $start = ($page - 1) * $limit;
        }else{
            $limit = false;
            $start = false;
        }
        $data = $this->appUserRelation->getAttentionListByUid($userId, $start, $limit, $source, $status);
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

    /**
     * 获取用户的关注专家列表
     */
    public function getAttentionExpertList($userIds)
    {
        $data = $this->groupSubjectUserExperts->getBatchExpertInfoByUids($userIds);
        return $data;
    }

    /**
     * 查询用户对指定一组用户的关注数量
     */
    public function getUserFollowNum($userIds, $followIds)
    {
        if (empty($userIds) || empty($followIds)) {
            return [];
        }
        $res = $this->appUserRelation->getUserFollowNum($userIds, $followIds);
        return $res;
    }
}
