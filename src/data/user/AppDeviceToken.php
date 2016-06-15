<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

/**
 * Description of AppDeviceToken
 *
 * @author user
 */
class AppDeviceToken extends DB_Query {

    protected $tableName = 'app_device_token';

    protected $dbResource = 'miagroup';

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
        $where[] = [
            'user_id',
            $userIds
        ];
        $fields = 'push_switch';
        $data = $this->getRow($where, $fields);
        
        return $data;
    }
}
