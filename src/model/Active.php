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
    
    //活动列表第一页的取所有进行中和未开始活动
    public function getFirstPageActive($status = array(1), $activeStatus){
        $activeRes = array();
        foreach($activeStatus as $value){
            $condition = array();
            $condition['active_status'] = $value;//活动状态（进行中、未开始）
            $activeRes += $this->getActiveList(false, 0, $status,$condition);
        }
        return $activeRes;
    }
    
    //获取活动列表
    public function getActiveList($page, $limit, $status = array(1), $condition){
        $activeRes = $this->activeData->getBatchActiveInfos($page, $limit, $status,$condition);
        $activeArr = array();
        if(!empty($activeRes)){
            foreach($activeRes as $active){
                $activeArr[$active['id']] = $active;
                if(!empty($active['ext_info'])){
                    $extInfo = json_decode($active['ext_info'],true);
                    if(!empty($extInfo['labels'])){
                        $activeArr[$active['id']]['labels'] = $extInfo['labels'];
                    }
                    if(!empty($extInfo['labels'])){
                        $activeArr[$active['id']]['labels'] = $extInfo['labels'];
                        $activeArr[$active['id']]['label_titles'] = implode(',',array_column($extInfo['labels'], 'title'));
                    }
                    if(!empty($extInfo['image'])){
                        $activeArr[$active['id']]['top_img'] = $extInfo['image'];
                        $activeArr[$active['id']]['top_img_url'] = $active['top_img'];
                    }
                    if(!empty($extInfo['cover_img'])){
                        $activeArr[$active['id']]['cover_img'] = $extInfo['cover_img'];
                    }
                    if(!empty($extInfo['icon_img'])){
                        $activeArr[$active['id']]['icon_img'] = $extInfo['icon_img'];
                    }
                    if(!empty($extInfo['image_count_limit'])){
                        $activeArr[$active['id']]['image_count_limit'] = $extInfo['image_count_limit'];
                    }
                    if(!empty($extInfo['text_lenth_limit'])){
                        $activeArr[$active['id']]['text_lenth_limit'] = $extInfo['text_lenth_limit'];
                    }
                }
                
                if(isset($condition['active_status'])){
                    $activeArr[$active['id']]['active_status'] = $condition['active_status'];
                }
            }
        }
        return $activeArr;
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
    
    
    //批量获取活动下图片计数（图片数，发帖用户数）（数据导入关联表后可以改方法）
    public function getBatchActiveSubjectCounts($activeIds) {
        $data = $this->relationData->getBatchActiveSubjectCounts($activeIds);
        return $data;
    }
    
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
