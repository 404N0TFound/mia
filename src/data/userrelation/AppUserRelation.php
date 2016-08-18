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
    
    /**
     * 更新关注状态
     * @param unknown $userId
     * @param unknown $relationUserId
     */
    public function updateRelationStatus($userId,$relationUserId,$status=0){
        $setStatus = array(array("status", 1),array("create_time", date('Y-m-d H:i:s')));
        $where = array(array("user_id", $userId), array("replation_user_id", $relationUserId),array('status',$status));
        $setRelationStatus = $this->update($setStatus,$where);
        return $setRelationStatus;
    }
    
    /**
     * insert
     */
    public function insertRelation($setData){
        return $this->insert($setData);
    }
    
    
    /**
     * 判断关注关系
     * @param unknown $iUserId
     * @param unknown $followdUserId
     */
    public function checkUserIsFollowdUser($iUserId, $followdUserId, $status=1)
    {
        if (!is_numeric($iUserId) || !is_numeric($followdUserId) || intval($iUserId) <=0 || intval($followdUserId) <= 0) {
            return false;
        }
        $where = array(array("user_id", $followdUserId), array("replation_user_id", $iUserId),array('status',$status));
        $res = $this->getRow($where);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * 取消关注
     * @param unknown $userId
     * @param unknown $relationUserId
     */
    public function remove($userId, $relationUserId)
    {
        if (!$userId || !$relationUserId) {
            return false;
        }
        
        $where = array(array("user_id", $userId), array("replation_user_id", $relationUserId), array("status", 1));
        $count = $this->count($where);
        if (empty($count)) {
            return false;
        }
        $setStatus = array(array("cancle_time", date('Y-m-d H:i:s')),array("status", 0));
        $setRelationStatus = $this->update($setStatus,$where);
        if(!$setRelationStatus) {
            return false;
        }
        $userRelation = $this->doozerReplation($userId, $relationUserId);
        if (!$userRelation) {
            return false;
        }
        $userRelationArr = array('relation_with_me' => 0, 'relation_with_him'=> 0);
        $followMeRes = $this->checkUserIsFollowdUser($userId, $relationUserId);
        if ($followMeRes == true) {
            $userRelationArr['relation_with_him'] = 1;
        }
        return $userRelationArr;
    }
    
    public function doozerReplation($loginUserId, $userId, $page=0, $iPageSize=0)
    {
        if (!empty($userId) && is_array($userId)) {
            $userId = implode(',', $userId);
        }
        if (!$loginUserId || !$userId) {
            return false;
        }
        $limitOffSet = ($page-1) * $iPageSize;
    
        $sql = "SELECT u.id as user_id, u.username, u.icon, u.level as level_number, u.mibean_level, c.name as level, u.nickname, d1.id as fensi_status, d2.id as atten_status FROM users as u
        LEFT JOIN {$this->tableName} as d1 ON d1.user_id = u.id and  d1.replation_user_id = {$loginUserId}  and d1.status > 0
        LEFT JOIN {$this->tableName} as d2 ON d2.replation_user_id = u.id and d2.user_id = {$loginUserId} and d2.status > 0
        LEFT JOIN user_level_config as c ON c.id = u.level
        WHERE u.status > 0 and u.id in ({$userId})
        ORDER BY FIND_IN_SET(u.id,'{$userId}')";
        
        if ($page && $iPageSize) {
            $sql .= "limit {$limitOffSet} ,{$iPageSize}";
        }
        
        $doorZerRelationList = $this->query($sql);
        if (!empty($doorZerRelationList)) {
            foreach ($doorZerRelationList as $key=>&$value) {
                if($value['fensi_status'] > 0 && $value['atten_status'] > 0) {
                    $value['relation_with_me']  = 1;
                    $value['relation_with_him'] = 1;
                } else {
                    if ($value['fensi_status'] > 0) {
                        $value['relation_with_me']  = 0;
                        $value['relation_with_him'] = 1;
                    } elseif ($value['atten_status'] > 0) {
                        $value['relation_with_me']  = 1;
                        $value['relation_with_him'] = 0;
                    } else {
                        $value['relation_with_me']  = 0;
                        $value['relation_with_him'] = 0;
                    }
                }
    
            }
        }
        return $doorZerRelationList;
    }
    
    /**用户粉丝与关注关系
     * @param int $relationId被关注用户
     * @param int $userId关注用户
     * @param $page
     * @param $iPageSize
     * @return array|bool
     */
    public function userRelaption($relationId = 0, $userId = 0, $page, $iPageSize)
    {
        if (!$relationId && !$userId) {
            return false;
        }
        
        $where_str = ' r.status > 0 ';
        $where[] = array(":>","status",0);
        if ($relationId && is_numeric($relationId)) {
            $where[] = array('replation_user_id',$relationId);
            $where_str .= " AND r.replation_user_id = ".$relationId;
        }
        if ($userId && is_numeric($userId)) {
            $where[] = array('user_id',$userId);
            $where_str .= " AND r.user_id = ".$userId;
        }
        //数量
        $count = $this->count($where);
        if ($count <= 0) {
            $arr = array('total' => $count);
            return $arr;
        }
        $limitOffSet = ($page-1) * $iPageSize;
        $sql = "SELECT u.id as user_id, u.username, u.nickname,u.level as level_number, u.icon, c.name as level, u.mibean_level, (CASE WHEN r1.id IS NULL then 1 ELSE 2 END) as replation_status FROM {$this->tableName} as r ";
        if ($relationId){
            $sql .= " LEFT JOIN users AS u ON u.id = r.user_id ";
        } else {
            $sql .= " LEFT JOIN users AS u ON u.id = r.replation_user_id";
        }
        $sql .= " LEFT JOIN {$this->tableName} as r1 ON r1.replation_user_id = r.user_id and r.replation_user_id = r1.user_id and r1.status > 0 ";
        $sql .= " LEFT JOIN user_level_config as c ON c.id = u.level ";
        $sql .= " WHERE " . $where_str . " ORDER BY r.create_time DESC LIMIT {$iPageSize} OFFSET {$limitOffSet}";
        $userRelation = $this->query($sql);
        
        if (!empty($userRelation)) {
            //获取专家信息
            $userService = new \mia\miagroup\Service\User();
            $user_ids = array_column($userRelation, 'user_id');
            $user_ids = array_filter($user_ids);
            $experts_info = $userService->getBatchExpertInfoByUids($user_ids)['data'];
            
            foreach ($userRelation as $key=>&$value) {
                $userName =  preg_replace('/(miya[\d]{3}|mobile_[\d]{3})([\d]{4})([\d]{4})/',"$1****$3",$value['username']);
                if (!$value['nickname']) {
                    $value['nickname'] = $userName;
                }
    
                if ($value['icon'] != '' && !preg_match("/^(http|https):\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"])*$/",$value['icon'])) {
                    $value['icon'] = \F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $value['icon'];
                }
    
                //用户是否是专家
                if($value['user_id'] > 0){
                    $value['is_experts'] = !empty($experts_info[$value['user_id']]) ? 1 : 0;
                }
    
                if($value['replation_status'] == 2) {
                    $value['relation_with_me']  = 1;
                    $value['relation_with_him'] = 1;
                } else {
                    //粉丝表示状态
                    if ($relationId) {
                        $value['relation_with_me']  = 0;
                        $value['relation_with_him'] = 1;
                    } else {
                        //关注表示状态
                        $value['relation_with_me']  = 1;
                        $value['relation_with_him'] = 0;
                    }
    
                }
    
                unset($value['username']);
                unset($value['replation_status']);
            }
        }
        $userRelation['total'] = $count;
        return $userRelation;
    }
    
    /**
     * @param int $userId关注用户id （查看关注时必须存在）
     * @param int $relationId被关注用户id （查看粉丝时必须存在）
     * @param $page
     * @param $iPageSize
     * @return array|bool
     */
    public function NotLogUserRelaption($userId = 0, $relationId = 0, $page, $iPageSize) {
        if (!$relationId && !$userId) {
            return false;
        }
        
        $where_str = ' r.status > 0 ';
        $where[] = array(":>","status",0);
        if ($relationId && is_numeric($relationId)) {
            $where[] = array('replation_user_id',$relationId);
            $where_str .= " AND r.replation_user_id = ".$relationId;
        }
        if ($userId && is_numeric($userId)) {
            $where[] = array('user_id',$userId);
            $where_str .= " AND r.user_id = ".$userId;
        }
        //数量
        $count = $this->count($where);
        if ($count <= 0) {
            $arr = array('total' => $count);
            return $arr;
        }
        $limitOffSet = ($page-1) * $iPageSize;
    
        $sql = "select u.id as user_id, u.username, u.nickname, u.icon, c.name as level, u.mibean_level, u.level as level_number from {$this->tableName} as r ";
        if ($relationId){
            $sql .= " left join users as u on u.id = r.user_id ";
        } else {
            $sql .= " left join users as u on u.id = r.replation_user_id ";
        }
        $sql .= " left join user_level_config as c on c.id = u.level " . $where_str . " order by r.create_time desc limit {$iPageSize} offset {$limitOffSet}";
        $notLoginUserRelation = $this->query($sql);

        if (!empty($notLoginUserRelation)) {
            //获取专家信息
            $userService = new \mia\miagroup\Service\User();
            $user_ids = array_column($userRelation, 'user_id');
            $user_ids = array_filter($user_ids);
            $experts_info = $userService->getBatchExpertInfoByUids($user_ids)['data'];
            
            foreach ($notLoginUserRelation as $key=>&$value) {
                $userName =  preg_replace('/(miya[\d]{3}|mobile_[\d]{3})([\d]{4})([\d]{4})/',"$1****$3",$value['username']);
                if (!$value['nickname']) {
                    $value['nickname'] = $userName;
                }
    
                if ($value['icon'] != '' && !preg_match("/^(http|https):\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"])*$/",$value['icon'])) {
                    $value['icon'] = \F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $value['icon'];
                }
    
                //用户是否是专家
                if($value['user_id'] > 0){
                    $value['is_experts'] = !empty($experts_info[$value['user_id']]) ? 1 : 0;
                }
                unset($value['username']);
            }
        }
        $notLoginUserRelation['total'] = $count;
        return $notLoginUserRelation;
    }
    
    public function getOtherUserAttenList($loginUserId, $userId, $page, $iPageSize)
    {
        if (!$userId || !$loginUserId || !$page || !$iPageSize) {
            return false;
        }
    
        if (!$userId >0 && !is_numeric($userId)) {
            return false;
        }
    
        if (!$loginUserId >0 && !$loginUserId($userId)) {
            return false;
        }
        
        $where = array(array(':>','status', 0), array('user_id', $userId));
        $count = $this->count($where);
        if ($count <= 0) {
            $arr = array('total' => $count);
            return $arr;
        }
        
        $limitOffSet = ($page-1) * $iPageSize;
        $sql = "select 
        d1.replation_user_id as user_id, u.icon, d2.replation_user_id, d2.id as fensi_status, d3.id as atten_status, u.username, u.nickname, c.name as level, u.mibean_level, u.level as level_number 
        from {$this->tableName} as d1 
        left join {$this->tableName} as d2 on d1.replation_user_id = d2.user_id AND d2.replation_user_id = {$loginUserId} and d2.status > 0 
        left join {$this->tableName} as d3 on d1.replation_user_id = d3.replation_user_id AND d3.user_id = {$loginUserId} and d3.status > 0 
        left join users as u on u.id = d1.replation_user_id 
        left join user_level_config as c on c.id = u.level 
        where d1.status > 0 and d1.user_id={$userId} 
        order by d1.create_time desc 
        limit {$iPageSize} offset {$limitOffSet}";
        
        $otherUserReplationList = $this->query($sql);
        
        if (!empty($otherUserReplationList)) {
            //获取专家信息
            $userService = new \mia\miagroup\Service\User();
            $user_ids = array_column($userRelation, 'user_id');
            $user_ids = array_filter($user_ids);
            $experts_info = $userService->getBatchExpertInfoByUids($user_ids)['data'];
            
            foreach ($otherUserReplationList as $key=>&$value) {
                $userName =  preg_replace('/(miya[\d]{3}|mobile_[\d]{3})([\d]{4})([\d]{4})/',"$1****$3",$value['username']);
                if (!$value['nickname']) {
                    $value['nickname'] = $userName;
                }
    
                if ($value['icon'] != '' && !preg_match("/^(http|https):\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"])*$/",$value['icon'])) {
                    $value['icon'] = \F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $value['icon'];
                }
    
                //用户是否是专家
                if($value['user_id'] > 0){
                    $value['is_experts'] = !empty($experts_info[$value['user_id']]) ? 1 : 0;
                }
    
                if($value['fensi_status'] >0 && $value['atten_status'] >0) {
                    $value['relation_with_me']  = 1;
                    $value['relation_with_him'] = 1;
                } else {
                    if ($value['fensi_status'] > 0) {
                        $value['relation_with_me']  = 0;
                        $value['relation_with_him'] = 1;
                    } elseif ($value['atten_status'] > 0) {
                        $value['relation_with_me']  = 1;
                        $value['relation_with_him'] = 0;
                    } else {
                        $value['relation_with_me']  = 0;
                        $value['relation_with_him'] = 0;
                    }
                }
    
                unset($value['username']);
            }
        }
        $otherUserReplationList['total'] = $count;
        return $otherUserReplationList;
    }
    
    
}