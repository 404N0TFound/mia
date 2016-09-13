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
    public function getPersonalSubject($userId, $page = 1, $count = 10) {
        if(empty($userId)){
            return [];
        }
        //获取我发布的帖子列表
        $subjectIds = $this->feedModel->getSubjectListByUids([$userId],$page,$count);
        //获取帖子详细信息
        $subjectsList = $this->subjectService->getBatchSubjectInfos($subjectIds,$userId);
        return $this->succ($subjectsList['data']);
    }
    
    /**
     * 获取我关注用户的帖子
     */
    public function getFeedSubject($userId, $page = 1, $count = 10) {
        if(empty($userId)){
            return [];
        }
        //获取我关注的用户列表
        $userIds = $this->userRelationService->getAllAttentionUser($userId)['data'];
        //获取我关注用户的帖子列表
        $subjectIds = $this->feedModel->getSubjectListByUids($userIds,$page,$count);
        //获取帖子详细信息
        $subjectsList = $this->subjectService->getBatchSubjectInfos($subjectIds,$userId);

        return $this->succ($subjectsList['data']);
    }
    
    /**
     * 获取我关注专家用户的帖子
     */
    public function getExpertFeedSubject($userId, $page = 1, $count = 10) {
        if(empty($userId)){
            return [];
        }
        //获取我关注的专家列表
        $expertUserIds = $this->userRelationService->getAllAttentionExpert($userId)['data'];
        //获取我关注专家的帖子列表
        $subjectIds = $this->feedModel->getSubjectListByUids($expertUserIds,$page,$count);
        //获取帖子详细信息
        $subjectsList = $this->subjectService->getBatchSubjectInfos($subjectIds,$userId);

        return $this->succ($subjectsList['data']);
    } 
    
    /**
     * 获取我关注标签的帖子
     */
    public function getLabelFeedSubject($userId, $page = 1, $count = 10) {
        if(empty($userId)){
            return [];
        }
        //获取我关注的标签列表
        $lableIds = $this->labelService->getAllAttentLabel($userId)['data'];
        //获取我关注标签的帖子列表
        $subjectIds = $this->labelService->getBatchSubjectIdsByLabelIds($lableIds,$page,$count)['data'];
        //获取帖子详细信息
        $subjectsList = $this->subjectService->getBatchSubjectInfos($subjectIds,$userId);

        return $this->succ($subjectsList['data']);
    }
}