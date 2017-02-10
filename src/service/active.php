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
    public function getActiveList() {
        $activeRes = array();
        // 获取活动列表
        $activeInfos = $this->activeModel->getActiveByActiveIds();
        if (empty($activeInfos)) {
            return $this->succ(array());
        }
        
        return $this->succ($activeRes);
    }
    
    
    /**
     * 获取单条活动信息
     */
    public function getSingleActiveById($activeId) {
        $activeids = array($activeId);
        // 获取活动基本信息
        $activeInfos = $this->activeModel->getActiveByActiveIds($activeids);
    }
    

    /**
     * 发布活动（用于后台活动发布）
     */
    public function issue($activeInfo) {
        if (empty($activeInfo)) {
            return $this->error(500);
        }
        return $this->succ($subjectSetInfo);
    }

    
    /**
     * 更新活动信息（用于后台活动编辑）
     */
    public function updateActive($activeId, $activeInfo) {
        return $this->succ(true);
    }
    
    /**
     * 删除活动（用于后台活动管理删除活动）
     */
    public function deleteActive($activeId,$oprator){
    }
    
}

