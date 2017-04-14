<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

/**
 * 蜜芽圈视频权限表
 *
 * @author user
 */
class GroupUserPermission extends DB_Query {

    protected $tableName = 'group_user_permission';

    protected $dbResource = 'miagroup';

    /**
     * 添加用户权限
     */
    public function addPermission($insertData) {
        $data = $this->insert($insertData);
        return $data;
    }
    
    /**
     * 批量获取用户权限
     */ 
    public function getUserPermissionByUids($conditions, $type) {
        $result = array();
        $where = array();
        
        $where[] = ['status', 1];
        $where[] = ['type', $type];
        
        if (!empty($conditions) && isset($conditions['user_id'])) {
            $where[] = ['user_id', $conditions['user_id']];
        }
        
        $data = $this->getRows($where);

        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['user_id']] = $v;
                if(!empty($v['ext_info'])){
                    $extInfo = json_decode($v['ext_info'],true);
                    if(!empty($extInfo['reason'])){
                        $result[$v['user_id']]['reason'] = $extInfo['reason'];
                    }
                }
                unset($result[$v['user_id']]['ext_info']);
            }
        }
        return $result;
    }
    
    /**
     * 修改用户分类信息
     */
    public function updatePermissionByUid($userId, $type, $userInfo) {
        if (empty($userId)) {
            return false;
        }
        $setData = array();
        $extInfo = array();
    
        if (isset($userInfo['status'])) {
            $setData[] = array('status', $userInfo['status']);
        }
    
        if (isset($userInfo['reason'])) {
            $extInfo['reason'] = $userInfo['reason'];
        }
        
        if(!empty($extInfo)){
            $extInfo = json_encode($extInfo);
            $setData[] = array('ext_info', $extInfo);
        }
    
        $where = array();
        $where[] = ['user_id', $userId];
        $where[] = ['type', $type];
        $data = $this->update($setData, $where);
        return $data;
    }

}
