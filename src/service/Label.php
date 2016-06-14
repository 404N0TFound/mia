<?php
namespace mia\miagroup\Service;
class Label extends \FS_Service {
    
    /**
     * 根据帖子ID批量分组获取标签信息
     */
    public function getBatchSubjectLabels($subjectIds) {
        $labelModel = new \mia\miagroup\Model\Label();
        $subjectLabels = $labelModel->getBatchSubjectLabels($subjectIds);
        return $subjectLabels;
    }
}
