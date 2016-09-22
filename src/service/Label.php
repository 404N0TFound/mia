<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Label as LabelModel;
use mia\miagroup\Service\Subject as SubjectService;

class Label extends \mia\miagroup\Lib\Service {

    public $labelModel = null;

    public function __construct() {
        parent::__construct();
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
     */
    public function saveLabelRelation($labelRelationInfo) {
        $data = $this->labelModel->saveLabelRelation($labelRelationInfo);
        return $this->succ($data);
    }

    /**
     * 保存蜜芽圈标签
     */
    public function addLabel($labelTitle) {
        $labelResult = $this->labelModel->checkIsExistByLabelTitle($labelTitle);
        if (empty($labelResult)) {
            // 如果没有存在，则保存该自定义标签
            $insertId = $this->labelModel->addLabel($labelTitle);
        } else {
            $insertId = $labelResult['id'];
        }
        return $this->succ($insertId);
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
     * 获取我关注的所有标签
     */
    public function getAllAttentLabel($userId)
    {
        $labelIds = $this->labelModel->getLabelListByUid($userId);
        $labelInfos = $this->getBatchLabelInfos($labelIds);
        return $this->succ($labelInfos);
    }
    
    /**
     * 批量获取标签下的帖子
     */
    public function getBatchSubjectIdsByLabelIds($labelIds,$currentUid,$page=1,$limit=10)
    {
        $subjectIds = $this->labelModel->getSubjectListByLableIds($labelIds,$page,$limit);
        $subjectService = new SubjectService();
        $data = $subjectService->getBatchSubjectInfos($subjectIds,$currentUid,array('user_info', 'count', 'comment', 'group_labels', 'praise_info'));
        return $this->succ($data);
    }
    
    /**
     * 关注标签
     */
    public function focusLabel($userId, $labelIds) {
        if (empty($labelIds) || empty($userId)) {
            return $this->succ(array());
        }
        $data = $this->labelModel->addLableRelation($userId, $labelIds);
        return $this->succ($data);
    }
    
    /**
     * 取消关注标签
     */
    public function cancelFocusLabel($userId, $labelId) {
        if (empty($labelIds) || empty($userId)) {
            return $this->succ(array());
        }
        $data = $this->labelModel->removeLableRelation($userId,$lableId);
        return $this->succ($data);
    }
    
    /**
     * 获取全部归档标签
     */
    public function getArchiveLalbels() {
        
    }
    
    /**
     * 获取新人推荐标签
     */
    public function getNewUserRecommendLabels($page=1,$count=10) {
        $labelIds = $this->labelModel->getRecommendLables($page,$count,'is_new');
        $labelInfos = $this->getBatchLabelInfos($labelIds);
        return $this->succ($labelInfos);
    }
    
    /**
     * 获取全部推荐标签
     */
    public function getRecommendLabels() {
        $labelIds = $this->labelModel->getRecommendLables($page,$count,'is_recommend');
        $labelInfos = $this->getBatchLabelInfos($labelIds);
        return $this->succ($labelInfos);
    }
}
