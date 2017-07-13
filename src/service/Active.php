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
    public function getActiveList($page, $limit, $fields = array('count'), $status = [1])
    {
        $activeRes = array();
        if($page == 1){
            $activeStatus = array(2,1);
            $activeInfos = $this->activeModel->getFirstPageActive($status,$activeStatus);
        }else{
            $page = $page - 1;
            $activeStatus = array('active_status'=>3);
            $activeInfos = $this->activeModel->getActiveList($page, $limit, $status,$activeStatus);
        }
    
        if (empty($activeInfos)) {
            return $this->succ(array());
        }
    
        $activeIds = array_keys($activeInfos);
        $activeCount = $this->activeModel->getBatchActiveSubjectCounts($activeIds);
    
        foreach($activeInfos as $activeInfo){
            $tmp = $activeInfo;
            $tmp['img_nums'] = 0;
            $tmp['user_nums'] = 0;
            if (in_array('count', $fields)) {
                $tmp['img_nums'] = isset($activeCount[$activeInfo['id']]['img_nums']) ? $activeCount[$activeInfo['id']]['img_nums'] : 0 ;
                $tmp['user_nums'] = isset($activeCount[$activeInfo['id']]['user_nums']) ? $activeCount[$activeInfo['id']]['user_nums'] : 0 ;
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
        $activeInfos = $this->activeModel->getActiveList(1, 1, $status, $condition);
        if (empty($activeInfos[$activeId])) {
            return $this->succ(array());
        }
        $activeRes = $activeInfos[$activeId];
        
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
    public function getCurrentActive($limit=0) {
        $condition = array('active_status' => 2);
        $activeRes = array();
        // 获取所有活动基本信息
        $activeArr = $this->activeModel->getActiveList(0, false, array(1), $condition);
        if (empty($activeArr)) {
            return $this->succ($activeRes);
        }

        //如果传入数量，则需要过滤掉没有小图的活动，用到发帖页
        if($limit > 0){
            foreach($activeArr as $key=>$active){
                if(!isset($active['icon_img'])){
                    continue;
                }
                $activeRes[$key] = $active;
            }
            $activeRes = array_slice($activeRes,0,$limit);
        }else{
            //如果没有传入数量，则获取所有的活动，无需过滤没有小图的
            $activeRes = $activeArr;
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
        if(isset($activeInfo['image_count_limit'])){
            $extInfo['image_count_limit']= $activeInfo['image_count_limit'];
            unset($activeInfo['image_count_limit']);
        }
        
        if(isset($activeInfo['text_lenth_limit'])){
            $extInfo['text_lenth_limit']= $activeInfo['text_lenth_limit'];
            unset($activeInfo['text_lenth_limit']);
        }

        if(!empty($activeInfo['image_info'])){
            $extInfo['image']= $activeInfo['image_info'];
            unset($activeInfo['image_info']);
        }
        if(!empty($activeInfo['cover_img_info'])){
            $extInfo['cover_img']= $activeInfo['cover_img_info'];
            unset($activeInfo['cover_img_info']);
        }
        if(!empty($activeInfo['icon_img_info'])){
            $extInfo['icon_img']= $activeInfo['icon_img_info'];
            unset($activeInfo['icon_img_info']);
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
        
        if(!empty($activeInfo['cover_img_info'])){
            $extInfo['cover_img']= $activeInfo['cover_img_info'];
            unset($activeInfo['cover_img_info']);
        }
        
        if(!empty($activeInfo['icon_img_info'])){
            $extInfo['icon_img']= $activeInfo['icon_img_info'];
            unset($activeInfo['icon_img_info']);
        }
        
        if(isset($activeInfo['image_count_limit'])){
            $extInfo['image_count_limit']= $activeInfo['image_count_limit'];
            unset($activeInfo['image_count_limit']);
        }
        if(isset($activeInfo['text_lenth_limit'])){
            $extInfo['text_lenth_limit']= $activeInfo['text_lenth_limit'];
            unset($activeInfo['text_lenth_limit']);
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
        $subjectIds = $this->activeModel->getBatchActiveSubjects($activeId, $type, $page, $limit);
        if(empty($subjectIds)){
            return $this->succ($data);
        }
        $subjectService = new SubjectService();
        $subjects = $subjectService->getBatchSubjectInfos($subjectIds,$currentId)['data'];
        $data['subject_lists'] = !empty($subjects) ? array_values($subjects) : array();
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

