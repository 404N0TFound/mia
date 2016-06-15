<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Label\SubjectLabel;
use mia\miagroup\Data\Label\SubjectLabelRelation;

class Label {
    
    public $labelData = null;
    public $labelRelation = null;

    public function __construct() {
	$this->labelData = new SubjectLabel();
	$this->labelRelation = new SubjectLabelRelation();
    }
    
    /**
     * 根据帖子ID批量分组获取标签信息
     */
    public function getBatchSubjectLabels($subjectIds) {
        if (empty($subjectIds)) {
            return array();
        }
        //获取帖子和标签的关系
        $labelRelationData = new \mia\miagroup\Data\Label\SubjectLabelRelation();
        $labelRelations = $labelRelationData->getBatchSubjectLabelIds($subjectIds);
        if (empty($labelRelations)) {
            return array();
        }
        //收集标签ID
        $labelIds = array();
        foreach ($labelRelations as $subjectLabelIds) {
            if (!empty($subjectLabelIds)) {
                $labelIds = array_merge($labelIds, $subjectLabelIds);
            }
        }
        //获取标签info
        $labelData = new \mia\miagroup\Data\Label\SubjectLabel();
        $labelInfos = $labelData->getBatchLabelInfos($labelIds);
        foreach ($labelRelations as $subjectId => $subjectLabelIds) {
            foreach ($subjectLabelIds as $labelId => $v) {
                $labelRelations[$subjectId][$labelId] = $labelInfos[$labelId];
            }
        }
        return $labelRelations;
    }
    
    /**
     * 判断标签记录是否存在(用于图片发布，避免主辅库不同步，从主库查)
     * @param string $labelTitle 标签标题
     * @return bool
     */
    public function checkIsExistByLabelTitle($labelTitle){
	
        $LabelRes = $this->labelData->checkIsExistByLabelTitle($labelTitle);
        return $LabelRes;
    }
    
    /**
     * 保存蜜芽圈标签
     * @param array  $labelInfo 标签信息
     * @return int 标签id
     */
    public function addLabel($labelTitle){
	$data = $this->labelData->addLabel($labelTitle);
	return $data;
    }
    
    /**
     * 保存蜜芽圈标签关系记录
     * @param array $labelRelationInfo 图片标签关系信息
     * @return bool
     */
    public function saveLabelRelation($labelRelationInfo){
	$data = $this->labelRelation->saveLabelRelation($labelRelationInfo);
	return $data;
    }
    
    
}
