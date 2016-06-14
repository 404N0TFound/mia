<?php
namespace mia\miagroup\Model;
class Label {
    
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
}
