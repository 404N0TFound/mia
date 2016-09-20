<?php
namespace mia\miagroup\Service;

use mia\miagroup\model\Audit as AuditModel;
use mia\miagroup\service\User as UserService;

/**
 * 审核服务
 */
class Audit extends \mia\miagroup\Lib\Service {
    
    private $auditModel;
    
    public function __construct() {
        $this->auditModel = AuditModel();
    }
    
    /**
     * 验证用户是否已屏蔽
     */
    public function checkUserIsShield($userId) {
        
    }
    
    /**
     * 验证设备是否已屏蔽
     */
    public function checkIsShieldByDevice($deviceInfo) {
        
    }
    
    /**
     * 验证是否为有效用户
     */
    public function checkIsValidUser($userId) {
        //获取用户信息
        //判断是否有验证手机、邮箱，是否有设置头像、密码
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
        //兼容单条
        if (is_string($textArray)) {
            
            return;
        } else {
            
            return;
        }
    }
}
