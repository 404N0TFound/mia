<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Active\Active as ActiveData;

class Active {
    protected $activeData = null;

    public function __construct() {
        $this->activeData = new ActiveData();
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


}
