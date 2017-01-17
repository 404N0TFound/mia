<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Label\SubjectLabel;
use mia\miagroup\Data\Label\SubjectLabelRelation;
use mia\miagroup\Data\Label\UserLabelRelation;
use mia\miagroup\Data\Label\SubjectLabelCategoryRelation;
class Label {

    public $labelData = null;

    public $labelRelation = null;

    public $userLabelRelation = null;

    public $labelCagegoryRelation = null;

    public function __construct() {
        $this->labelData         = new SubjectLabel();
        $this->labelRelation     = new SubjectLabelRelation();
        $this->userLabelRelation = new UserLabelRelation();
        $this->labelCagegoryRelation = new SubjectLabelCategoryRelation();
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
                        unset($labelInfos[$labelId]['hot_pic']);
                        unset($labelInfos[$labelId]['is_recommend']);
                        unset($labelInfos[$labelId]['hot_small_pic']);
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
    public function getLabelListByUid($userId,$page=1,$limit=10)
    {
        $start = ($page-1)*$limit;
        $data = $this->userLabelRelation->getLabelListByUid($userId,$start,$limit);
        return $data;
    }


    /**
     * 根据标签ID获取帖子列表
     */
    public function getSubjectListByLableIds($lableIds,$page=1,$limit=10,$is_recommend=0)
    {
        $start = ($page-1)*$limit;
        $data = $this->labelRelation->getSubjectListByLableIds($lableIds,$start,$limit,$is_recommend);
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
                    $this->userLabelRelation->updateUserLabelRelate($relateInfo['id'], array('status' => 1, 'create_time' => date('Y-m-d H:i:s')));
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
        if ($relateInfo['status'] == 1) {
            //更新为非关注状态
            $this->userLabelRelation->updateUserLabelRelate($relateInfo['id'], array('status' => 0, 'update_time' => date('Y-m-d H:i:s')));
        }
        return true;
    }


    /**
     * 获取标签列表
     */
    public function getRecommendLables($page=1,$limit=10,$userType='')
    {
        $start = ($page-1)*$limit;
        $data = $this->labelData->getRecommendLables($start,$limit,$userType);
        return $data;
    }

    /**
     * 获取某个标签关联的标签
     */
    public function getRelateLabels($labelId,$page=1,$limit=10) {
        $start = ($page-1)*$limit;
        $categoryIds = $this->labelCagegoryRelation->getLabelCategory($labelId);
        $categoryLabels = $this->labelCagegoryRelation->getLabelsByCategoryIds(array_values($categoryIds), $start, $limit);
        $labelIds = array();
        foreach ($categoryLabels as $labels) {
            if ($labels['label_id'] != $labelId) {
                $labelIds[] = $labels['label_id'];
            }
        }
        $labelIds = array_slice($labelIds, 0, 6);
        $labelInfos = $this->getBatchLabelInfos($labelIds);
        return $labelInfos;
    }

    /**
     * 获取单条标签信息
     */
    public function getBatchLabelInfo($labelId)
    {
        $data = $this->getBatchLabelInfos(array($labelId))[$labelId];
        return $data;
    }

    /**
     * 查询用户和标签的关注关系
     */
    public function getLableRelationByUserId($userId,$lableId)
    {
        $data = $this->userLabelRelation->getLableRelationByUserId($userId,$lableId);
        return $data;
    }

    /**
     * 获取标签帖子置顶状态
     */
    public function getLableSubjectsTopStatus($lableId, $subjectIds)
    {
        $data = $this->labelRelation->getLableSubjectsTopStatus($lableId,$subjectIds);
        return $data;
    }

    /**
     * 查找被推荐的分类标签
     */
    public function getCategoryLables($page=1,$limit=30)
    {
        //获取全部已归档标签
        $relationInfos = $this->labelCagegoryRelation->getRelationList(0, 100);
        $countArr = array();
        $result = array();
        foreach ($relationInfos as $value) {
            if (!isset($countArr[$value['category_id']])) {
                $countArr[$value['category_id']] = 0;
            }
            if (count($result[$value['category_id']]) >= $limit) {
                continue;
            }
            if (isset($result[$value['category_id']]) && in_array($value['label_id'], $result[$value['category_id']])) {
                continue;
            }
            $result[$value['category_id']][] = $value['label_id'];
            $countArr[$value['category_id']] += 1;
        }
        return $result;
    }

    /**
     * 更新标签详情页图像宽高
     */
    public function updateLabelImgInfo($labelId,$setData)
    {
        $data = $this->labelData->updateLabelImgInfo($labelId,$setData);
        return $data;
    }
    
    /**
     * 获取标签下是否有精选帖子
     */
    public function getLabelIsRecommendInfo($labelId){
        return $this->labelRelation->getLabelIsRecommendInfo($labelId);
    }
    
    /**
     * 查询标签分类
     */
    public function getCategroyByIds($categoryIds, $status = 1) {
        $labelCategoryInfo = new \mia\miagroup\Data\Label\LabelCategory();
        $data = $labelCategoryInfo->getCategroyByIds($categoryIds, $status);
        return $data;
    }
    
    /**
     * @todo 删除标签
     * @param label_id, subject_id
     * @return 返回影响的行数
     **/
    public function removeLabelByLabelId($label_id)
    {
        $affect = $this->labelData->removeLabelByLabelId($label_id);
        return $affect;
    }
    
    /**
     * 批量获取帖子下面的标签ID
     */
    public function getBatchSubjectLabelIds($subjectIds){
        $data = $this->labelRelation->getBatchSubjectLabelIds($subjectIds);
        return $data;
    }
    
    /**
     * 获取关联信息
     * @param unknown $label_id
     * @param unknown $subject_id
     * @return unknown
     */
    public function getLabelRelation($subject_id,$label_id){
        $data = $this->labelRelation->getLabelRelation($subject_id, $label_id);
        return $data;
    }
    
    /**
     * 添加标签帖子关系表
     * @param unknown $label_id
     * @param unknown $subject_id
     * @param unknown $is_recommend
     * @param unknown $user_id
     */
    public function addLabelRelation($subject_id,$label_id,$is_recommend,$user_id){
        $data = $this->labelRelation->addLabelRelation($subject_id, $label_id, $is_recommend, $user_id);
        return $data;
    }
    
    /**
     * 给标签下的帖子  加精
     * @param int $user_id 操作人id
     */
    public function setLabelRelationRecommend($subject_id, $label_id, $recommend, $user_id){
        $affect = $this->labelRelation->setLabelRelationRecommend($subject_id, $label_id, $recommend, $user_id);
        return $affect;
    }
    
    /**
     * 给标签下的帖子  置顶
     * @param int $user_id 操作人id
     */
    public function setLabelRelationTop($subject_id, $label_id, $top, $user_id){
        $affect = $this->labelRelation->setLabelRelationTop($subject_id, $label_id, $top, $user_id);
        return $affect;
    }
    
    /**
     * @todo 删除图片和标签的对应关系
     * @param label_id, subject_id
     * @return 返回影响的行数
     **/
    public function removeRelation($subject_id,$label_id){
        $affect = $this->labelRelation->removeRelation($subject_id, $label_id);
        return $affect;
    }
    

}
