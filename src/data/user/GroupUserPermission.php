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

//     /**
//      * 批量获取发视频权限
//      */ 
//     public function getVideoPermissionByUids($userIds) {
//         $result = array();
        
//         $where[] = ['status', 1];
//         $where[] = ['user_id', $userIds];
        
//         $data = $this->getRows($where);

//         if (!empty($data)) {
//             foreach ($data as $v) {
//                 $result[$v['user_id']] = $v;
//             }
//         }
//         return $result;
//     }
    
    /**
     * 添加用户权限
     */
    public function addPermission($insertData) {
        $data = $this->insert($insertData);
        return $data;
    }
}
