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
    public function getActiveList($page, $limit) {
        $activeRes = array();
        // 获取活动列表
        $activeInfos = $this->activeModel->getActiveByActiveIds($page, $limit);
        if (empty($activeInfos)) {
            return $this->succ(array());
        }
        return $this->succ($activeRes);
    }
    
    
    /**
     * 获取单条活动信息
     */
    public function getSingleActiveById($activeId) {
        $activeIds = array($activeId);
        $activeRes = array();
        // 获取活动基本信息
        $activeInfos = $this->activeModel->getActiveByActiveIds(1, 1, array(1), $activeIds);
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
        }
        $activeInfo['ext_info'] = json_encode($extInfo);
        unset($activeInfo['label_titles']);
        
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
        }
        $activeInfo['ext_info'] = json_encode($extInfo);
        unset($activeInfo['label_titles']);
        
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

