<?php
namespace mia\miagroup\Data\Audit;

use \DB_Query;

class DeviceShield extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'app_device_address_shield';

    protected $mapping = array();

    /**
     * 根据设备信息查询屏蔽状态
     */
    public function getDeviceShieldByDeviceInfo($deviceInfo) {
        $where = array();
        if (!empty($deviceInfo['mac'])) {
            $where[] = array(':eq', 'mac', $deviceInfo['mac']);
        }
        if (!empty($deviceInfo['uuid'])) {
            $where[] = array(':eq', 'uuid', $deviceInfo['uuid']);
        }
        if (!empty($deviceInfo['idfa'])) {
            $where[] = array(':eq', 'idfa', $deviceInfo['idfa']);
        }
        $data = $this->getRow($where);
        return $data;
    }
}