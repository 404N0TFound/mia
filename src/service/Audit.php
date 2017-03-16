<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Audit as AuditModel;
use mia\miagroup\service\User as UserService;
use mia\miagroup\service\Subject as SubjectService;
use mia\miagroup\service\Comment as CommentService;
use mia\miagroup\Util;

/**
 * 审核服务
 */
class Audit extends \mia\miagroup\Lib\Service {
    
    private $auditModel;
    
    public function __construct() {
        parent::__construct();
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
        $isWhite = $this->auditModel->checkIsWhiteList($userId, $deviceToken);
        return $this->succ(array('is_white' => $isWhite));
    }
    
    /**
     * 屏蔽用户
     * @param array $userInfo
     * @param string $deviceToken
     * @param array $delContent
     */
    public function shieldUser($userInfo, $deviceToken, $delContent) {
        //1、如果登录用户不是白名单用户，则没有权限操作
        $whiteStatus = $this->checkIsWhiteUser($userInfo['operator'],$deviceToken);
        if($whiteStatus['data']['is_white'] === false){
            return $this->error(1125);
        }
        //2、检查该用户是否被屏蔽过
        $shieldStatus = $this->auditModel->checkIsShieldUserByUid($userInfo['user_id']);
        if(!empty($shieldStatus)){
            if($shieldStatus['status'] == 1){
                //(1)如果屏蔽过，且当前为屏蔽状态，直接返回true
                return $this->succ(true);
            }elseif($shieldStatus['status'] == 0){
                //(2)如果屏蔽过，且当前为解除屏蔽状态，则更新为屏蔽状态
                $setData = array();
                $setData[] = ['status',1];
                if(isset($userInfo['intro'])){
                    $setData[] = ['intro',$userInfo['intro']];
                }
                $shieldRes = $this->auditModel->updateShieldUserInfo($setData,$userInfo['user_id']);
            }
        }else{
            //(3)如果没有屏蔽过，则插入屏蔽信息
            $shieldRes = $this->auditModel->setUserShield($userInfo);
        }
        
        //3、是否删除该用户下的帖子和评论
        if(empty($delContent)){
            return $this->succ($shieldRes);
        }
        
        $subjectService = new \mia\miagroup\Service\Subject();
        $commentService = new \mia\miagroup\Service\Comment();
        $koubeiService = new \mia\miagroup\Service\Koubei();
        
        foreach($delContent as $content){
            //(1)删除帖子及口碑
            if($content == 1){
                //查看用户是否有帖子信息
                $subjectsArr = $subjectService->getSubjects($userInfo['user_id']);
                //如果有蜜芽帖子，删除
                if(!empty($subjectsArr['data'])){
                    $subjectIds = array();
                    foreach($subjectsArr['data'] as $subject){
                        $subjectIds[] = $subject['id'];
                    }
                    $subjectService->delSubjects($subjectIds,0);
                }
                //查看用户是否有口碑帖子
                $koubeisArr = $koubeiService->getKoubeis($userInfo['user_id']);
                //如果有口碑帖子，删除
                if(!empty($koubeisArr['data'])){
                    $koubeiIds = array();
                    foreach($koubeisArr['data'] as $koubei){
                        $koubeiIds[] = $koubei['id'];
                    }
                    $koubeiService->deleteKoubeis($koubeiIds);
                }
            }
            //(2)删除评论
            if($content == 2){
                //查看用户是否有评论信息
                $commentInfos = $commentService->getComments($userInfo['user_id']);
                //如果有，则删除，同时批量更新评论的帖子的评论数量
                if(!empty($commentInfos['data'])){
                    $commentIds = array();
                    foreach($commentInfos['data'] as $comment){
                        $commentIds[] = $comment['id'];
                    }
                    //（2.1）批量删除评论
                    $status = 0;
                    $commentService->delComments($commentIds,$status);
                    //（2.2）通过评论ids批量获取帖子id
                    $subjectIds =  $commentService->getSubjectIdsByComment($commentIds)['data'];
                    //（2.3）批量更新帖子的评论数
                    //(2.3.1)根据帖子id批量获取帖子评论数
                    $subjectCommentNums = $commentService->getBatchCommentNums($subjectIds)['data'];
                    //（2.3.2）更新帖子的评论数
                    $subjectService->updateSubjectComment($subjectCommentNums);
                }
            }
        }
        
        return $this->succ($shieldRes);
    }
    
    /**
     * 屏蔽帖子
     */
    public function shieldSubject($subjectInfo,$deviceToken) {
        //1、如果登录用户不是白名单用户，则没有权限操作
        $whiteStatus = $this->checkIsWhiteUser($subjectInfo['user_id'],$deviceToken);
        if($whiteStatus['data']['is_white'] === false){
            return $this->error(1125);
        }
        //屏蔽帖子
        $subjectService = new \mia\miagroup\Service\Subject();
        $shieldRes = $subjectService->delSubjects(array($subjectInfo['id']),-1,$subjectInfo['shield_text'])['data'];
        
        return $this->succ($shieldRes);
    }
    
    /**
     * 屏蔽评论
     */
    public function shieldComment($commentInfo,$deviceToken) {
        //1、如果登录用户不是白名单用户，则没有权限操作
        $whiteStatus = $this->checkIsWhiteUser($commentInfo['user_id'],$deviceToken);
        if($whiteStatus['data']['is_white'] === false){
            return $this->error(1125);
        }
        
        $subjectService = new \mia\miagroup\Service\Subject();
        $commentService = new \mia\miagroup\Service\Comment();
        //1、屏蔽评论
        $status = -1;
        $shieldRes = $commentService->delComments(array($commentInfo['id']),$status,$commentInfo['shield_text'])['data'];
        //2、通过评论ids批量获取帖子id
        $subjectIds =  $commentService->getSubjectIdsByComment(array($commentInfo['id']))['data'];
        //3、批量更新帖子的评论数
        //(3.1)根据帖子id批量获取帖子评论数
        $subjectCommentNums = $commentService->getBatchCommentNums($subjectIds)['data'];
        //（3.2）更新帖子的评论数
        $subjectService->updateSubjectComment($subjectCommentNums);
        return $this->succ($shieldRes);
    }
    
    /**
     * 获取屏蔽原因
     */
    public function getShieldReason() {
        $shieldReason = \F_Ice::$ins->workApp->config->get('busconf.audit.shieldReason');
        return $this->succ($shieldReason);
    }
    
    /**
     * 检查敏感词
     * @param $textArray 可以是字符串，也可以是数组
     * @return 当$textArray是字符串返回一维数组，$textArray是数组返回二维数组
     */
    public function checkSensitiveWords($textArray, $shumei = 0)
    {
        $passUid = \F_Ice::$ins->workApp->config->get('busconf.subject.dump_exclude_uids');
        if (in_array($this->ext_params['current_uid'], $passUid)) {
            return $this->succ(array('sensitive_words' => []));
        }
        //数美检测
        if ($shumei == 1) {
            $shumeiService = new Util\ShumeiUtil($this->ext_params);
            if (is_string($textArray)) {
                $checkResult = $shumeiService->checkText($textArray);
                if ($checkResult === true) {
                    $matchList = [];
                } else {
                    $matchList = [$checkResult];
                }
            } else if (is_array($textArray)) {
                $textArray = implode('', $textArray);
                $checkResult = $shumeiService->checkText($textArray);
                if ($checkResult === true) {
                    $matchList = [];
                } else {
                    $matchList = [$checkResult];
                }
//                $matchList = [];
//                foreach ($textArray as $text) {
//                    $key = md5($text);
//                    $checkResult = $shumeiService->checkText($text);
//                    if ($checkResult !== true) {
//                        $matchList[$key] = [$checkResult];
//                    }
//                }
            }
            if (!empty($matchList)) {
                return $this->error(1127);
            }
        }
        //获取敏感词
        $sensitiveWord = $this->auditModel->getAllSensitiveWord();
        if (empty($sensitiveWord) || !is_array($sensitiveWord)) {
            return $this->succ(array('sensitive_words' => array()));
        }
        $sensitiveWord = implode('|', $sensitiveWord);
        //解除敏感词匹配个数限制
        ini_set('pcre.backtrack_limit', -1);
        
        $matchList = array();
        if (is_string($textArray)) { //兼容单条
            $textArray = str_replace(" ", '', $textArray);
            $textArray = str_replace("\t", '', $textArray);
            $textArray = str_replace("\n", '', $textArray);
            $textArray = str_replace("\r\n", '', $textArray);
            preg_match_all("/".$sensitiveWord."/i", $textArray, $match);
            if (isset($match[0]) && !empty($match[0])) {
                $matchList = $match[0];
            }
        } else if (is_array($textArray)) {
            foreach ($textArray as $text) {
                $text = str_replace(" ", '', $text);
                $text = str_replace("\t", '', $text);
                $text = str_replace("\n", '', $text);
                $text = str_replace("\r\n", '', $text);
                $key = md5($text);
                preg_match_all("/".$sensitiveWord."/i", $text, $match);
                if (isset($match[0]) && !empty($match[0])) {
                    $matchList[$key] = $match[0];
                }
            }
        }
        //单条返回一维数组，多条返回二维数组
        if(!empty($matchList)) {
            return $this->error(1127);
        }
        return $this->succ(array('sensitive_words' => $matchList));
    }
    
    /**
     * 检查口碑相关敏感词
     */
    public function checkKoubeiSensitiveWords($textArray) {
        //获取敏感词
        $sensitiveWord = \F_Ice::$ins->workApp->config->get('busconf.audit.koubeiBlackWord');
        if (empty($sensitiveWord) || !is_array($sensitiveWord)) {
            return $this->succ(array('sensitive_words' => array()));
        }
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
