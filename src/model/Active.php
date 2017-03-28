<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Active\Active as ActiveData;
use \mia\miagroup\Data\Active\ActiveSubject as ActiveSubjectData;
use \mia\miagroup\Data\Active\ActiveSubjectRelation as RelationData;

class Active {
    protected $activeData = null;
    protected $activeSubjectData = null;
    protected $relationData = null;

    public function __construct() {
        $this->activeData = new ActiveData();
        $this->activeSubjectData = new ActiveSubjectData();
        $this->relationData = new RelationData();
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
    
    //批量获取活动下图片计数（图片数，发帖用户数）(暂时用这个方法，数据导入关联表后用新方法)
    public function getBatchActiveSubjectCounts($activeIds) {
        $data = $this->activeSubjectData->getBatchActiveSubjectCounts($activeIds);
        return $data;
    }
    
//     //批量获取活动下图片计数（图片数，发帖用户数）（数据导入关联表后可以改方法）
//     public function getBatchActiveSubjectCounts($activeIds) {
//         $data = $this->relationData->getBatchActiveSubjectCounts($activeIds);
//         return $data;
//     }
    
    /**
     * 根据帖子id批量获取活动帖子信息
     */
    public function getActiveSubjectBySids($subjectIds, $status) {
        $subjectArr = $this->relationData->getActiveSubjectBySids($subjectIds, $status);
        return $subjectArr;
    }
    
    /**
     * 根据活动id批量获取帖子信息
     */
    public function getBatchActiveSubjects($activeIds, $type = 'all', $page, $limit) {
        $subjectArrs = $this->relationData->getBatchActiveSubjects($activeIds, $type, $page, $limit);
        return $subjectArrs;
    }
    
    /**
     * 新增活动帖子关联数据
     */
    public function addActiveSubjectRelation($insertData){
        $data = $this->relationData->addActiveSubjectRelation($insertData);
        return $data;
    }
    
    /**
     * 更新活动帖子
     */
    public function upActiveSubject($relationData,$relationId){
        $data = $this->relationData->updateActiveSubjectRelation($relationData,$relationId);
        return $data;
    }

}
