<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

/**
 * 蜜芽圈用户角色分组表
 *
 * @author user
 */
class GroupUserRole extends DB_Query {

    protected $tableName = 'group_user_role';

    protected $dbResource = 'miagroup';

    /**
     * 添加用户分组
     */
    public function addUserGroup($insertData) {
        $data = $this->insert($insertData);
        return $data;
    }
    
    /**
     * 删除用户分组
     */
    public function deleteUserGroup($roleId,$userId = null) {
        $where = array();
        $where[] = ['role_id',$roleId];
        if($userId){
            $where[] = ['user_id',$userId];
        }
        
        $data = $this->delete($where);
        return $data;
    }
    
    
    /**
     * 修改用户分组信息
     */
    public function updateUserGroupByRoleId($roleId, $setInfo) {
        if (empty($roleId) || empty($setInfo)) {
            return false;
        }
    
        $where = array();
        $setData = array();
        
        $where[] = ['role_id', $roleId];
        if($setInfo['role_name']){
            $setData[] = ['role_name',$setInfo['role_name']];
        }
        if($setInfo['user_id']){
            $setData[] = ['user_id',$setInfo['user_id']];
        }
        
        $data = $this->update($setData, $where);
        return $data;
    }
    
    /**
     * 获取用户分组列表
     */
    public function getUserGroup($condition=null) {
        $where = array();
        $result = array();
        
        $groupBy = 'role_id';

        if($condition['role_id']){
            $where[] = ['role_id',$condition['role_id']];
        }
        if($condition['status']){
            $where[] = ['status',$condition['status']];
        }
    
        $field = 'id,role_id,role_name,group_concat(user_id) as user_ids,count(user_id) as user_nums,standard,status,create_time,operator,oper_time';
    
        $countRes = $this->getRows($where, $field, $limit = FALSE, $offset = 0, $orderBy = FALSE, $join = FALSE, $groupBy);
    
        if (!empty($countRes)) {
            foreach ($countRes as $count) {
                $result[$count['role_id']] = $count;
            }
        }
        return $result;
    }

    public function getUserGroupInfo($conditions)
    {
        $where = [];
        $result = [];
        if(!isset($conditions['role_id']) && !isset($conditions['user_id'])) {
            return $result;
        }

        if(isset($conditions['role_id'])){
            $where[] = ['role_id',$conditions['role_id']];
        }
        if(isset($conditions['user_id'])){
            $where[] = ['user_id',$conditions['user_id']];
        }
        if(isset($conditions['status'])){
            $where[] = ['status',$conditions['status']];
        }

        $field = "id,role_id,user_id,role_name";
        $result = $this->getRows($where, $field);

        return $result;
    }



}
