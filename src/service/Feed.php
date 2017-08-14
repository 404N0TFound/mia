<?php
namespace mia\miagroup\Service;

use \F_Ice;
use mia\miagroup\Service\Label as LabelService;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Service\UserRelation as UserRelationService;
use mia\miagroup\Model\Feed as FeedModel;
class Feed extends \mia\miagroup\Lib\Service {

    public $labelService;
    public $subjectService;
    public $userRelationService;
    public $feedModel;
    public function __construct() {
        parent::__construct();
        $this->labelService        = new LabelService();
        $this->subjectService      = new SubjectService();
        $this->userRelationService = new UserRelationService();
        $this->feedModel           = new FeedModel();
    }
    
    /**
     * 获取我发布的帖子
     */
    public function getPersonalSubject($userId, $currentUid = 0, $page = 1, $count = 10) {
        if(empty($userId)){
            return $this->succ(array());
        }
        //获取我发布的帖子列表
        $subjectIds = $this->feedModel->getSubjectListByUids([$userId],$page,$count);
        //获取帖子详细信息
        $subjectsList = $this->subjectService->getBatchSubjectInfos($subjectIds,$currentUid,array('user_info', 'count', 'praise_info', 'album'));
        return $this->succ($subjectsList['data']);
    }
    
    /**
     * 获取我关注用户的帖子
     */
    public function getFeedSubject($userId, $currentUid = 0, $page = 1, $count = 10) {
        if(empty($userId)){
            return $this->succ(array());
        }

        //获取我关注的标签列表
        $lableIdInfo = $this->labelService->getAllAttentLabel($userId,1,11)['data'];
        //获取我关注的用户列表
        $userIds = $this->userRelationService->getAllAttentionUser($userId)['data'];
        $auditService = new \mia\miagroup\Service\Audit();
        foreach ($userIds as $key => $userId) {
            //验证用户是否已屏蔽
            if($auditService->checkUserIsShield($userId)['data']['is_shield']){
                unset($userIds[$key]);
            }
        }
        
        //获取我关注用户的帖子列表
        $source = [\F_Ice::$ins->workApp->config->get('busconf.subject.source.default'), \F_Ice::$ins->workApp->config->get('busconf.subject.source.koubei'), \F_Ice::$ins->workApp->config->get('busconf.subject.source.editor')];
        $subjectIds = $this->feedModel->getSubjectListByUids($userIds,$page,$count,$source);
        //获取帖子详细信息
        $subjectsList = $this->subjectService->getBatchSubjectInfos($subjectIds,$currentUid)['data'];
        $data = [];
        $data['subject_lists'] = array_values($subjectsList);
        $data['label_lists'] = $lableIdInfo;
        return $this->succ($data);
    }
    
    /**
     * 获取我关注专家用户的订阅内容
     */
    public function getExpertFeedSubject($userId, $currentUid = 0, $page = 1, $count = 10) {
        if(empty($userId)){
            return $this->succ(array());
        }
        //获取我关注的专家列表
        $expertUserInfo = $this->userRelationService->getAllAttentionExpert($userId)['data'];
        $expertUserIds = array_keys($expertUserInfo);
        //获取我关注专家的帖子列表
        $subjectIds = $this->feedModel->getSubjectListByUids($expertUserIds,$page,$count,\F_Ice::$ins->workApp->config->get('busconf.subject.source.headline'));
        //获取帖子详细信息
        $subjectsList = $this->subjectService->getBatchSubjectInfos($subjectIds,$currentUid);

        return $this->succ($subjectsList['data']);
    } 
    
    /**
     * 获取我关注标签的帖子
     */
    public function getLabelFeedSubject($userId, $currentUid = 0, $page = 1, $count = 10) {
        if(empty($userId)){
            return $this->succ(array());
        }
        //获取我关注的标签列表
        $lableIdInfo = $this->labelService->getAllAttentLabel($userId,1,11)['data'];
        $lableIds = array_column($lableIdInfo,'id');
        //获取我关注标签的帖子列表
        $subjectInfos = $this->labelService->getLableSubjects($lableIds,$currentUid,$page,$count)['data'];

        $data = [];
        $data['subject_lists'] = array_values($subjectInfos);
        $data['label_lists'] = $lableIdInfo;
        return $this->succ($data);
    }
    
    /**
     * 获取发现页的帖子
     * @param $currentUid 当前登录用户id
     * @param $use_type （all为发现页，carousel为轮播）
     * @param $page 当前页
     * @param $count 每页数量
     */
    public function getDiscoverySubjects($use_type, $currentUid = 0, $page = 1, $count = 10, $referId = 0) {
        if(empty($use_type)){
            return $this->succ(array());
        }
        if ($use_type == 'carousel') {
            $page = 1;
            $count = 5;
        }
        $userIds = array('7509553','13789824');
        $auditService = new \mia\miagroup\Service\Audit();
        foreach ($userIds as $key => $userId) {
            //验证用户是否已屏蔽
            if($auditService->checkUserIsShield($userId)['data']['is_shield']){
                unset($userIds[$key]);
            }
        }
    
        //获取plus用户的帖子列表
        $source = [\F_Ice::$ins->workApp->config->get('busconf.subject.source.plus')];
        $subjectIds = $this->feedModel->getSubjectListByUids($userIds,$page,$count,$source);
        if(intval($referId) > 0){
            array_unshift($subjectIds,$referId);
        }
        
        //获取帖子详细信息
        $subjectsList = $this->subjectService->getBatchSubjectInfos($subjectIds,$currentUid)['data'];
        $data = [];
        $data['subject_lists'] = array_values($subjectsList);
        return $this->succ($data);
    }
    
}