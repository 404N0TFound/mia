<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Label\SubjectLabel;
use mia\miagroup\Data\Label\SubjectLabelRelation;
use mia\miagroup\Data\Label\UserLabelRelation;

class Label {

    public $labelData = null;

    public $labelRelation = null;

    public $userLabelRelation = null;

    public function __construct() {
        $this->labelData         = new SubjectLabel();
        $this->labelRelation     = new SubjectLabelRelation();
        $this->userLabelRelation = new UserLabelRelation();
    }

    /**
     * 根据帖子ID批量分组获取标签信息
     */
    public function getBatchSubjectLabels($subjectIds) {
        if (empty($subjectIds)) {
            return array();
        }
        // 获取帖子和标签的关系
        $labelRelationData = new \mia\miagroup\Data\Label\SubjectLabelRelation();
        $labelRelations = $labelRelationData->getBatchSubjectLabelIds($subjectIds);
        if (empty($labelRelations)) {
            return array();
        }
        // 收集标签ID
        $labelIds = array();
        foreach ($labelRelations as $subjectLabelIds) {
            if (!empty($subjectLabelIds)) {
                $labelIds = array_merge($labelIds, $subjectLabelIds);
            }
        }
        $labelIds = array_unique($labelIds);
        
        // 获取标签info
        $labelData = new \mia\miagroup\Data\Label\SubjectLabel();
        $labelInfos = $labelData->getBatchLabelInfos($labelIds);
        $subjectLabelInfo = array();
        if(!empty($labelInfos)){
            foreach ($labelRelations as $subjectId => $subjectLabelIds) {
                foreach ($subjectLabelIds as $labelId => $v) {
                    if(!empty($labelInfos[$labelId])){
                        $subjectLabelInfo[$subjectId][$labelId] = $labelInfos[$labelId];
                    }
                }
            }
        }

        return $subjectLabelInfo;
    }

    /**
     * 判断标签记录是否存在(用于图片发布，避免主辅库不同步，从主库查)
     *
     * @param string $labelTitle
     *            标签标题
     * @return bool
     */
    public function checkIsExistByLabelTitle($labelTitle) {
        $LabelRes = $this->labelData->checkIsExistByLabelTitle($labelTitle);
        return $LabelRes;
    }

    /**
     * 保存蜜芽圈标签
     *
     * @param array $labelInfo
     *            标签信息
     * @return int 标签id
     */
    public function addLabel($labelTitle) {
        $data = $this->labelData->addLabel($labelTitle);
        return $data;
    }

    /**
     * 保存蜜芽圈标签关系记录
     *
     * @param array $labelRelationInfo
     *            图片标签关系信息
     * @return bool
     */
    public function saveLabelRelation($labelRelationInfo) {
        $data = $this->labelRelation->saveLabelRelation($labelRelationInfo);
        return $data;
    }
    
    /**
     * 批量获取标签信息
     */
    public function getBatchLabelInfos($labelIds){
        $data = $this->labelData->getBatchLabelInfos($labelIds);
        return $data;
    }
    
    /**
     * 获取标签ID
     */
    public function getLabelID(){
        $data = $this->labelData->getLabelID();
        return $data;
    }

    /**
     * 根据userId获取标签
     */
    public function getLabelListByUid($userId)
    {
        $data = $this->labelRelation->getLabelListByUid($userId);
        return $data;
    }


    /**
     * 根据标签ID获取帖子列表
     */
    public function getSubjectListByLableIds($lableIds,$page=1,$limit=10)
    {
        $start = ($page-1)*$limit;
        $data = $this->labelRelation->getSubjectListByLableIds($lableIds,$start,$limit);
        return $data;
    }
    
    

    /**
     * 加关注
     */
    public function addLableRelation($userId, $labelIds)
    {
        $labelIds = count($labelIds) > 50 ? array_slice($labelIds, 0, 50) : $labelIds;
        if (is_array($labelIds) && !empty($labelIds)) {
            foreach ($labelIds as $labelId) {
                //获取关注状态
                $relateInfo = $this->userLabelRelation->getLableRelationByUserId($userId, $labelId);
                if (empty($relateInfo)) {
                    //新的关注关系
                    $setInfo = array(
                        "user_id"  => $userId,
                        "label_id" => $labelId,
                        "status"   => 1,
                        'create_time' => date('Y-m-d H:i:s')
                    );
                    $this->userLabelRelation->addUserLabelRelate($setInfo);
                } else if ($relateInfo['status'] == 0) { //曾经关注过
                    $this->userLabelRelation->updateUserLabelRelate($relateInfo['id'], $labelId, array('status' => 1, 'create_time' => date('Y-m-d H:i:s')));
                }
            }
        }
        return true;
    }

    /**
     * 取消关注
     */
    public function removeLableRelation($userId, $labelId)
    {
        $relateInfo = $this->userLabelRelation->getLableRelationByUserId($userId, $labelId);
        if ($meRelation['status'] == 1) {
            //更新为非关注状态
            $this->userLabelRelation->updateUserLabelRelate($relateInfo['id'], $labelId, array('status' => 0, 'update_time' => date('Y-m-d H:i:s')));
        }
        return true;
    }

    public function getRecommendLables($page=1,$limit=10,$userType='')
    {
        $start = ($page-1)*$limit;
        $data = $this->labelData->getRecommendLables($start,$limit,$userType='');
        return $data;
    }

}
