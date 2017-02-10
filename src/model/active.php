<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Active\Active as ActiveData;
use \mia\miagroup\Data\Subject\Subject as SubjectData;

class Active {

    protected $subjectData = null;
    protected $activeData = null;

    public function __construct() {
        $this->subjectData = new SubjectData();
        $this->activeData = new ActiveData();
    }

    /**
     * 获取活动列表
    */
    public function getActiveByActiveIds($activeIds = array(), $status = array(1)) {
        // 获取活动的基本信息
        $actives = $this->activeData->getBatchActiveInfos(activeIds,$status);
        
        return $actives;
    }


}
