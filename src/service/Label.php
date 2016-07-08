<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Label as LabelModel;

class Label extends \FS_Service {

    public $labelModel = null;

    public function __construct() {
        $this->labelModel = new LabelModel();
    }

    /**
     * 根据帖子ID批量分组获取标签信息
     */
    public function getBatchSubjectLabels($subjectIds) {
        $labelModel = new \mia\miagroup\Model\Label();
        $subjectLabels = $labelModel->getBatchSubjectLabels($subjectIds);
        return $this->succ($subjectLabels);
    }

    /**
     * 保存蜜芽圈标签关系记录
     *
     * @param array $labelRelationInfo
     *            图片标签关系信息
     * @return bool
     */
    public function saveLabelRelation($labelRelationInfo) {
        $data = $this->labelModel->saveLabelRelation($labelRelationInfo);
        return $this->succ($data);
    }

    /**
     * 判断标签记录是否存在(用于图片发布，避免主辅库不同步，从主库查)
     *
     * @param string $labelTitle
     *            标签标题
     * @return bool
     */
    public function checkIsExistByLabelTitle($labelTitle) {
        $data = $this->labelModel->checkIsExistByLabelTitle($labelTitle);
        return $this->succ($data);
    }

    /**
     * 保存蜜芽圈标签
     *
     * @param array $labelInfo
     *            标签信息
     * @return int 标签id
     */
    public function addLabel($labelTitle) {
        $data = $this->labelModel->addLabel($labelTitle);
        return $this->succ($data);
    }
    
    /**
     * 批量获取标签信息
     */
    public function getBatchLabelInfos($labelIds){
        if (empty($labelIds)) {
            return $this->succ(array());
        }
        $data = $this->labelModel->getBatchLabelInfos($labelIds);
        return $this->succ($data);
    }
    
    /**
     * 获取标签ID
     */
    public function getLabelID(){
        $data = $this->labelModel->getLabelID($labelIds);
        return $this->succ($data);
    }

}
