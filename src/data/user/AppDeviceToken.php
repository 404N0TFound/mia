<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

/**
 * Description of AppDeviceToken
 *
 * @author user
 */
class AppDeviceToken extends DB_Query {

    protected $dbResource = 'miadefault';
    protected $tableName = 'app_device_token';

    protected $mapping = array(
        'id' => 'i', 
        'device_token' => 's', 
        'regid' => 's', 
        'user_id' => 'i', 
        'push_switch' => 'i', 
        'client_type' => 's', 
        'created' => 's', 
        'cpa_platform_id' => 'i', 
        'mac' => 's', 
        'uuid' => 's', 
        'idfa' => 's'
    );

    /**
     * 根据userid 获取是否需要发送push
     *
     * @param type $userIds            
     */
    public function getPushSwitchByUserIds($userIds) {
        $where[] = ['user_id', $userIds];
        $fields = 'push_switch';
        $data = $this->getRow($where, $fields);
        
        return $data;
    }

    /**
     * 根据userid获取device_token
     *
     * @return void
     * @author 
     **/
    public function getDeviceTokenByUserId($userId)
    {
        if (empty($userId)) {
            return [];
        }

        $where = [];
        $fields = 'regid as device_token,client_type';
        $where[] = ['user_id', $userId];
        $data = $this->getRow($where, $fields,'created desc');

        return $data;
    }
}
