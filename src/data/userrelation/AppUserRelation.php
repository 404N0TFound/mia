<?php
namespace mia\miagroup\Data\UserRelation;

use \DB_Query;

class AppUserRelation extends DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'app_user_relation';

    protected $mapping = array('id' => 'i', 'user_id' => 'i', 'replation_user_id' => 'i', 'create_time' => 's', 'cancle_time' => 's', 'status' => 'i');
    
    // 批量获取我是否关注了用户
    public function getUserRelationWithMe($loginUserId, $userIds) {
        $relationArr = array();
        
        if (is_array($userIds)) {
            $where[] = array(':in', 'replation_user_id', $userIds);
        } else {
            $where[] = array(':eq', 'replation_user_id', $userIds);
        }
        
        $where[] = array(':eq', 'user_id', $loginUserId);
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
    
    // 批量获取用户是否关注了我
    public function getMeRelationWithUser($loginUserId, $userIds) {
        if (is_array($userIds)) {
            
            $where[] = array(':in', 'user_id', $userIds);
        } else {
            $where[] = array(':eq', 'user_id', $userIds);
        }
        
        $where[] = array(':eq', 'replation_user_id', $loginUserId);
        
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

    /*
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
    
    // 获取用户的关注数
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
}