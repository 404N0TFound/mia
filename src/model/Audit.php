<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\Audit\UserShield as UserShieldData;
use mia\miagroup\Data\Audit\DeviceShield as DeviceShieldData;
use mia\miagroup\Data\Audit\SensitiveWord as SensitiveWordData;
use mia\miagroup\Data\Audit\WhiteList as WhiteListData;

class Audit {
    
    private $userShieldData;
    private $deviceShieldData;
    private $sensitiveWordData;
    private $whiteListData;
    
    public function __construct() {
        $this->userShieldData = new UserShieldData();
        $this->deviceShieldData = new DeviceShieldData();
        $this->sensitiveWordData = new SensitiveWordData();
        $this->whiteListData = new WhiteListData();
    }
    
    /**
     * 根据用户ID查询屏蔽状态
     */
    public function checkIsShieldByUid($userId) {
        $data = $this->userShieldData->getUserShieldByUid($userId);
        if (!empty($data) && $data['status'] == 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 根据设备信息查询屏蔽状态
     */
    public function getDeviceShieldByDeviceInfo($deviceInfo) {
        $data = $this->deviceShieldData->getDeviceShieldByDeviceInfo($deviceInfo);
        if (!empty($data) && $data['status'] == 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 获取全部敏感词表
     */
    public function getAllSensitiveWord() {
        $data = $this->sensitiveWordData->getSensitiveWord();
        $sensitiveWordList = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                //转义文本中可能存在的如"+"、"|"等符号
                $sensitiveWordList[] = preg_quote($v['word']);
            }
        }
        return $sensitiveWordList;
    }
    
    /**
     * 检查是否为白名单用户
     */
    public function checkIsWhiteList($userId, $deviceToken) {
        $data = $this->whiteListData->checkIsWhiteList($userId, $deviceToken);
        if (!empty($data) && $data['status'] == 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 根据用户ID查询屏蔽过的用户（包括status为0和1的）
     */
    public function checkIsShieldUserByUid($userId) {
        $data = $this->userShieldData->getUserShieldByUid($userId);
        if (!empty($data)) {
            return $data;
        } else {
            return false;
        }
    }
    
    /**
     * 屏蔽用户
     */
    public function setUserShield($userInfo) {
        $data = $this->userShieldData->setUserShield($userInfo);
        return $data;
    }
    
    /**
     * 更新屏蔽状态
     * @param array $userInfo
     */
    public function updateShieldUserInfo($userInfo,$userId){
        $data = $this->userShieldData->updateShieldUserInfo($userInfo,$userId);
        return $data;
    }
}