<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Audit as AuditModel;
use mia\miagroup\service\User as UserService;

/**
 * 审核服务
 */
class Audit extends \mia\miagroup\Lib\Service {
    
    private $auditModel;
    
    public function __construct() {
        $this->auditModel = new AuditModel();
    }
    
    /**
     * 验证用户是否已屏蔽
     */
    public function checkUserIsShield($userId) {
        $isShield = $this->auditModel->checkIsShieldByUid($userId);
        return $this->succ(array('is_shield' => $isShield));
    }
    
    /**
     * 验证设备是否已屏蔽
     */
    public function checkIsShieldByDevice($deviceInfo) {
        $isShield = $this->auditModel->getDeviceShieldByDeviceInfo($deviceInfo);
        return $this->succ(array('is_shield' => $isShield));
    }
    
    /**
     * 验证是否为有效用户
     */
    public function checkIsValidUser($userId) {
        //获取用户信息
        $userService = new \mia\miagroup\Service\User();
        //判断是否有验证手机、邮箱，是否有设置头像、密码
        $userInfo = $userService->getUserInfoByUserId($userId)['data'];
        $isValid = false;
        if (!empty($userInfo['icon']) || !empty($userInfo['is_id_verified']) || !empty($userInfo['is_cell_verified'])) {
            $isValid = true;
        }
        return $this->succ(array('is_valid' => $isValid));
    }
    
    /**
     * 查看用户是否为白名单用户
     */
    public function checkIsWhiteUser($userId, $deviceToken) {
        
    }
    
    /**
     * 屏蔽用户
     */
    public function shieldUser() {
        
    }
    
    /**
     * 屏蔽帖子
     */
    public function shieldSubject() {
        
    }
    
    /**
     * 屏蔽评论
     */
    public function shieldComment() {
        
    }
    
    /**
     * 获取屏蔽原因
     */
    public function getShieldReason() {
        
    }
    
    /**
     * 检查敏感词
     */
    public function checkSensitiveWords($textArray) {
        //获取敏感词
        $sensitiveWord = $this->auditModel->getAllSensitiveWord();
        $sensitiveWord = implode('|', $sensitiveWord);
        //解除敏感词匹配个数限制
        ini_set('pcre.backtrack_limit', -1);
        
        $matchList = array();
        if (is_string($textArray)) { //兼容单条
            preg_match_all("/".$sensitiveWord."/i", $textArray, $match);
            if (isset($match[0]) && !empty($match[0])) {
                $matchList = $match[0];
            }
        } else if (is_array($textArray)) {
            foreach ($textArray as $text) {
                $key = md5($text);
                preg_match_all("/".$sensitiveWord."/i", $text, $match);
                if (isset($match[0]) && !empty($match[0])) {
                    $matchList[$key] = $match[0];
                }
            }
        }
        //单条返回一维数组，多条返回二维数组
        return $this->succ(array('sensitive_words' => $matchList));
    }
}
