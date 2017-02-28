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
        foreach($activeInfos as $activeInfo){
            $tmp = $activeInfo;
            $extInfo = json_decode($activeInfo['ext_info'],true);
            $tmp['top_img'] = $extInfo['image'];
            if (in_array('count', $fields)) {
                $subjectService = new SubjectService();
                $subjectArr = $subjectService->getActiveSubjects($activeInfo['id'], 'all', 0, null, null)['data'];
                $tmp['img_nums'] = $subjectArr['subject_nums'] > 0 ? $subjectArr['subject_nums'] : 0;
                $tmp['user_nums'] = $subjectArr['user_nums'] > 0 ? $subjectArr['user_nums'] : 0;
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
        $activeInfos = $this->activeModel->getActiveByActiveIds(1, 20, array(1), $condition);
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
        //参加活动的标签
        if (!empty($activeInfo['label_titles'])) {
            $labelService = new LabelService();
            $labelInfoArr = array();
            $labels = array();
            foreach($activeInfo['label_titles'] as $labelTitle){
                $labelInfo = $labelService->getLabelInfoByTitle($labelTitle)['data'];
                if(empty($labelInfo)){
                    continue;
                }
                $labelInfoArr['id'] = $labelInfo['id'];
                $labelInfoArr['title'] = $labelInfo['title'];
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
        
        $insertActiveRes = $this->activeModel->addActive($activeInfo);
        if (!$insertActiveRes) {
            // 发布失败
            return $this->succ(false);
        }
        
        return $this->succ(true);
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
                $labelInfo = $labelService->getLabelInfoByTitle($labelTitle)['data'];
                if(empty($labelInfo)){
                    continue;
                }
                $labelInfoArr['id'] = $labelInfo['id'];
                $labelInfoArr['title'] = $labelInfo['title'];
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
    
}

