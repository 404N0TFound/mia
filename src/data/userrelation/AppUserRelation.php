<?php
namespace mia\miagroup\Data\UserRelation;

use \DB_Query;

class AppUserRelation extends DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'app_user_relation';

    protected $mapping = array(
        'id' => 'i', 
        'user_id' => 'i', 
        'replation_user_id' => 'i', 
        'create_time' => 's', 
        'cancle_time' => 's', 
        'status' => 'i'
    );
    
    /**
     * 批量获取我是否关注了用户
     */
    public function getUserRelationWithMe($userId, $userIds) {
        $relationArr = array();
        
        if (is_array($userIds)) {
            $where[] = array(':in', 'replation_user_id', $userIds);
        } else {
            $where[] = array(':eq', 'replation_user_id', $userIds);
        }
        
        $where[] = array(':eq', 'user_id', $userId);
        $fields = "replation_user_id as user_id,status";
        
        $relationStatus = $this->getRows($where, $fields);
        
        if (!empty($relationStatus)) {
            foreach ($relationStatus as $relation) {
                if ($relation['status'] == 1) {
                    $relationArr[$relation['user_id']]['relation_with_me'] = 1;
                } else {
                    $relationArr[$relation['user_id']]['relation_with_me'] = 0;
                }
            }
        }
        return $relationArr;
    }
    
    /**
     * 批量获取用户是否关注了我
     */
    public function getMeRelationWithUser($userId, $userIds) {
        if (is_array($userIds)) {
            
            $where[] = array(':in', 'user_id', $userIds);
        } else {
            $where[] = array(':eq', 'user_id', $userIds);
        }
        
        $where[] = array(':eq', 'replation_user_id', $userId);
        
        $relationStatus = $this->getRows($where, ' user_id,status');
        
        $relationArr = array();
        if (!empty($relationStatus)) {
            foreach ($relationStatus as $relation) {
                if ($relation['status'] == 1) {
                    $relationArr[$relation['user_id']]['relation_with_him'] = 1;
                } else {
                    $relationArr[$relation['user_id']]['relation_with_him'] = 0;
                }
            }
        }
        
        return $relationArr;
    }

    /**
     * 批量获取用户的粉丝数
     */
    public function getCountBatchUserFanS($userIds) {
        $where[] = ['replation_user_id', $userIds];
        $where[] = [':>', 'status', 0];
        
        $field = 'replation_user_id as user_id,count(*) as nums';
        $groupBy = 'replation_user_id';
        
        $relationInfos = $this->getRows($where, $field, FALSE, 0, FALSE, FALSE, $groupBy);
        
        $numArr = array();
        foreach ($relationInfos as $relationInfo) {
            $numArr[$relationInfo['user_id']] = $relationInfo['nums'];
        }
        
        return $numArr;
    }
    
    /**
     * 批量用户的关注数
     */
    public function getCountBatchUserAtten($userIds) {
        $where[] = ['user_id', $userIds];
        $where[] = [':>', 'status', 0];
        $groupBy = 'user_id';
        $field = 'user_id,count(*) as nums ';
        
        $relationInfos = $this->getRows($where, $field, FALSE, 0, FALSE, FALSE, $groupBy);
        
        $numArr = array();
        foreach ($relationInfos as $relationInfo) {
            $numArr[$relationInfo['user_id']] = $relationInfo['nums'];
        }
        
        return $numArr;
    }
    
    /**
     * 更新关注状态
     * @param unknown $userId
     * @param unknown $relationUserId
     */
    public function updateRelationStatus($userId, $relationUserId, $setData){
        $data = array();
        if (!empty($setData['status'])) {
            $data[] = array("status", $setData['status']);
        }
        if (!empty($setData['create_time'])) {
            $data[] = array("status", $setData['create_time']);
        }
        if (!empty($setData['cancle_time'])) {
            $data[] = array("status", $setData['cancle_time']);
        }
        $where = array(array("user_id", $userId), array("replation_user_id", $relationUserId));
        $setRelationStatus = $this->update($data, $where);
        return $setRelationStatus;
    }
    
    /**
     * insert
     */
    public function insertRelation($setData){
        return $this->insert($setData);
    }
    
    /**
     * 获取两人关注关系
     */
    public function getRelationByBothUid($userId, $relationUserId) {
        $where[] = ['user_id', $userId];
        $where[] = ['replation_user_id', $relationUserId];
        $relation = $this->getRow($where);
        return $relation;
    }
    
    /**
     * 获取用户的关注列表
     */
    public function getAttentionListByUid($userId, $start = 0, $limit = 20) {
        $where[] = array(':eq', 'user_id', $userId);
        $where[] = array(':eq', 'status', 1);
        $data = $this->getRows($where, 'replation_user_id', $limit, $start, 'create_time desc');
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[] = $v['replation_user_id'];
            }
        }
        return $result;
    }
    
    /**
     * 获取用户的粉丝列表
     */
    public function getFansListByUid($userId, $start = 0, $limit = 20) {
        $where[] = array(':eq', 'replation_user_id', $userId);
        $where[] = array(':eq', 'status', 1);
        $data = $this->getRows($where, 'user_id', $limit, $start, 'create_time desc');
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[] = $v['user_id'];
            }
        }
        return $result;
    }
}