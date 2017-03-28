<?php
namespace mia\miagroup\Service;

use \F_Ice;
use mia\miagroup\Model\Active as ActiveModel;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Service\Label as LabelService;
use mia\miagroup\Util\NormalUtil;

class Active extends \mia\miagroup\Lib\Service {

    public $activeModel = null;

    public function __construct() {
        parent::__construct();
        $this->activeModel = new ActiveModel();
    }

    /**
     * 获取活动列表
     */
    public function getActiveList($page, $limit, $fields = array('count')) {
        $activeRes = array();
        // 获取活动列表
        $activeInfos = $this->activeModel->getActiveByActiveIds($page, $limit);
        if (empty($activeInfos)) {
            return $this->succ(array());
        }
        
        $activeIds = array();
        foreach($activeInfos as $activeInfo){
            $activeIds[] = $activeInfo['id'];
        }
        $activeCount = $this->activeModel->getBatchActiveSubjectCounts($activeIds);
        
        foreach($activeInfos as $activeInfo){
            $tmp = $activeInfo;
            $extInfo = json_decode($activeInfo['ext_info'],true);
            $tmp['top_img'] = $extInfo['image'];
            if (in_array('count', $fields)) {
                $tmp['img_nums'] = $activeCount[$activeInfo['id']]['img_nums'] ;
                $tmp['user_nums'] = $activeCount[$activeInfo['id']]['user_nums'] ;
            }
            $activeRes[] = $tmp;
        }
        return $this->succ($activeRes);
    }
    
    
    /**
     * 获取单条活动信息
     */
    public function getSingleActiveById($activeId, $status = array(1), $fields = array('share_info')) {
        $condition = array('active_ids' => array($activeId));
        $activeRes = array();
        // 获取活动基本信息
        $activeInfos = $this->activeModel->getActiveByActiveIds(1, 1, $status, $condition);
        if (empty($activeInfos[$activeId])) {
            return $this->succ(array());
        }
        $activeRes = $activeInfos[$activeId];
        if(!empty($activeInfos[$activeId]['ext_info'])){
            $extInfo = json_decode($activeInfos[$activeId]['ext_info'],true);
            
            if(!empty($extInfo['labels'])){
                $activeRes['labels'] = $extInfo['labels'];
                $activeRes['label_titles'] = implode(',',array_column($activeRes['labels'], 'title'));
            }
            if(!empty($extInfo['image'])){
                $activeRes['top_img'] = $extInfo['image'];
                $activeRes['top_img_url'] = $activeInfos[$activeId]['top_img'];
            }
        }
        
        if (in_array('share_info', $fields)) {
            // 分享内容
            $shareConfig = \F_Ice::$ins->workApp->config->get('busconf.active');
            $shareDefault = $shareConfig['defaultShareInfo']['active'];
            $share = $shareConfig['activeShare'];
            $shareTitle = $shareDefault['title'];
            $shareContent = "【".$activeRes['title']."】".$activeRes['introduction'];
            $shareDesc = sprintf($shareDefault['desc'],$shareContent);
            $h5Url = sprintf($shareDefault['wap_url'], $activeRes['id']);
            if (empty($shareImage)) {
                $shareImage = $shareDefault['img_url'];
            }
        
            // 替换搜索关联数组
            $replace = array('{|title|}' => $shareTitle, '{|desc|}' => $shareDesc, '{|image_url|}' => $shareImage, '{|wap_url|}' => $h5Url);
            // 进行替换操作
            foreach ($share as $keys => $sh) {
                $shareInfo[] = NormalUtil::buildGroupShare($sh, $replace);
            }
            $activeRes['share_info'] = $shareInfo;
        }
        
        return $this->succ($activeRes);
    }
    
    /**
     * 获取当前在线活动
     */
    public function getCurrentActive() {
        $condition = array('current_time' => date('Y-m-d H:i:s',time()));
        $activeRes = array();
        // 获取活动基本信息
        $activeInfos = $this->activeModel->getActiveByActiveIds(false, 0, array(1), $condition);
        if (empty($activeInfos)) {
            return $this->succ(array());
        }
        if(!empty($activeInfos)){
            foreach($activeInfos as $key=>$activeInfo){
                $tmp = $activeInfo;
                if(!empty($activeInfo['ext_info'])){
                    $extInfo = json_decode($activeInfo['ext_info'],true);
                    if(!empty($extInfo['labels'])){
                        $tmp['labels'] = $extInfo['labels'];
                    }
                }
                $activeRes[$key] = $tmp;
            }
        }

        return $this->succ($activeRes);
    }
    

    /**
     * 创建活动（用于后台活动发布）
     */
    public function addActive($activeInfo) {
        if (empty($activeInfo)) {
            return $this->error(500);
        }
        $extInfo = array();
        //参加活动的标签
        if (!empty($activeInfo['label_titles'])) {
            $labelService = new LabelService();
            $labelInfoArr = array();
            $labels = array();
            foreach($activeInfo['label_titles'] as $labelTitle){
                $labelId = $labelService->addLabel($labelTitle)['data'];
                $labelInfoArr['id'] = $labelId;
                $labelInfoArr['title'] = $labelTitle;
                $labels[] = $labelInfoArr;
            }
            if(!empty($labels)){
                $extInfo['labels']= $labels;
                unset($activeInfo['label_titles']);
            }
        }

        if(!empty($activeInfo['image_info'])){
            $extInfo['image']= $activeInfo['image_info'];
            unset($activeInfo['image_info']);
        }
        $activeInfo['ext_info'] = json_encode($extInfo);
        $insertActiveRes = $this->activeModel->addActive($activeInfo);
        
        if($insertActiveRes > 0){
            $insertId = $insertActiveRes;
            $updata = array('asort' => $insertId);
            $this->activeModel->updateActive($updata, $insertId);
            return $this->succ(true);
        }else{
            return $this->succ(false);
        }
    }

    
    /**
     * 更新活动信息（用于后台活动编辑）
     */
    public function updateActive($activeId, $activeInfo) {
        if (empty($activeId) || empty($activeInfo)) {
            return $this->succ(false);
        }
        //更新标签
        if (!empty($activeInfo['label_titles'])) {
            $labelService = new LabelService();
            $labelInfoArr = array();
            $labels = array();
            foreach($activeInfo['label_titles'] as $labelTitle){
                $labelId = $labelService->addLabel($labelTitle)['data'];
                $labelInfoArr['id'] = $labelId;
                $labelInfoArr['title'] = $labelTitle;
                $labels[] = $labelInfoArr;
            }
        }
        $extInfo = array();
        if(!empty($labels)){
            $extInfo['labels']= $labels;
            unset($activeInfo['label_titles']);
        }
        if(!empty($activeInfo['image_info'])){
            $extInfo['image']= $activeInfo['image_info'];
            unset($activeInfo['image_info']);
        }
        
        $activeInfo['ext_info'] = json_encode($extInfo);
        
        $this->activeModel->updateActive($activeInfo, $activeId);
        return $this->succ(true);
    }
    
    /**
     * 删除活动（用于后台活动管理删除活动）
     */
    public function deleteActive($activeId,$oprator){
        if(empty($activeId)){
            return $this->error(500);
        }
        $result = $this->activeModel->deleteActive($activeId,$oprator);
        return $this->succ($result);
    }
    
    /**
     * 新增活动帖子关联数据
     */
    public function addActiveSubjectRelation($relationSetInfo){
        if (empty($relationSetInfo)) {
            return $this->error(500);
        }
        $insertActiveRes = $this->activeModel->addActiveSubjectRelation($relationSetInfo);
        
        if($insertActiveRes > 0){
            return $this->succ(true);
        }else{
            return $this->succ(false);
        }
    }
    
    /**
     * 根据帖子id获取活动帖子信息
     */
    public function getActiveSubjectBySids($subjectIds, $status=array(1)) {
        if(empty($subjectIds)){
            return $this->error(500);
        }
        $subjectArrs = $this->activeModel->getActiveSubjectBySids($subjectIds, $status);
        return $this->succ($subjectArrs);
    }
    
    /**
     * 获取某活动下的所有/精华帖子
     */
    public function getActiveSubjects($activeId, $type='all', $currentId = 0, $page=1, $limit=20){
        $data = array('subject_lists'=>array());
        if($type == 'recommend'){
            //如果是结束活动的精华帖子，需要跟进行中的进行区分
            $activeInfo = $this->getSingleActiveById($activeId)['data'];
            if($activeInfo['end_time'] < date('Y-m-d H:i:s',time())){
                $type = 'active_over';
            }
        }
        $activeIds = array($activeId);
        $subjectArrs = $this->activeModel->getBatchActiveSubjects($activeIds, $type, $page, $limit);
        $subjectIds = array_column($subjectArrs[$activeId], 'subject_id');
        if(!empty($subjectIds)) {
            $subjectService = new SubjectService();
            $subjects = $subjectService->getBatchSubjectInfos($subjectIds,$currentId)['data'];
            $data['subject_lists'] = !empty($subjects) ? array_values($subjects) : array();
        }
        return $this->succ($data);
    }
    
    /**
     * 更新活动帖子信息
     */
    public function upActiveSubject($relationData, $relationId){
        if(empty($relationId)){
            return $this->error(500);
        }
        $result = $this->activeModel->upActiveSubject($relationData, $relationId);
        return $this->succ($result);
    }
    
}

