<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Active\Active as ActiveData;
use \mia\miagroup\Data\Active\ActiveSubject as ActiveSubjectData;

class Active {
    protected $activeData = null;
    protected $activeSubjectData = null;

    public function __construct() {
        $this->activeData = new ActiveData();
        $this->activeSubjectData = new ActiveSubjectData();
    }

    /**
     * 获取活动列表
    */
    public function getActiveByActiveIds($page, $limit, $status = array(1), $condition = array()) {
        //获取活动的基本信息
        $actives = $this->activeData->getBatchActiveInfos($page, $limit, $status, $condition);
        $activesArr = array();
        if(!empty($actives)){
            foreach($actives as $active){
                $activesArr[$active['id']] = $active;
            }
        }
        return $activesArr;
    }
    
    //创建活动
    public function addActive($insertData){
        $data = $this->activeData->addActive($insertData);
        return $data;
    }
    
    //编辑活动
    public function updateActive($activeInfo, $activeId){
        $data = $this->activeData->updateActive($activeInfo, $activeId);
        return $data;
    }
    
    //删除活动
    public function deleteActive($activeId, $operator){
        $affect = $this->activeData->delete($activeId, $operator);
        return $affect;
    }
    
    //批量获取活动下图片计数（图片数，发帖用户数）
    public function getBatchActiveSubjectCounts($activeIds) {
        $data = $this->activeSubjectData->getBatchActiveSubjectCounts($activeIds);
        return $data;
    }

}
