<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\PointTags\SubjectPointTags as TagsData;
class PointTags {
    
    public $tagsData;
    
    public function __construct() {
        $this->tagsData = new TagsData();
    }
    
    /**
     * 保存蜜芽圈帖子标记信息
     * @param $tagsInfo array() 帖子标记信息
     */
    public function saveSubjectTags($tagsInfo){
        $data = $this->tagsData->saveSubjectTags($tagsInfo);
        return $data;
    }
    
    /**
     * 保存蜜芽圈帖子标记信息
     * @param $subjectId 帖子id
     * @param itemIds array() 帖子标记信息
     */
    public function saveBatchSubjectTags($setData){
        return $this->tagsData->saveBatchSubjectTags($setData);
    }
    
    /**
     * 批量查帖子相关商品id
     * @param $subjectIds array() 图片ids
     * @return array() 图片相关商品id列表
     */
    public function getBatchSubjectItmeIds($subjectIds){
        return $this->tagsData->getBatchSubjectItmeIds($subjectIds);
    }
    
    /**
     * 获取帖子标记的信息
     * @param unknown $subjectId
     * @param unknown $itemId
     * @return unknown
     */
    public function getInfoByIds($subjectId,$itemIds){
        return $this->tagsData->getInfoByIds($subjectId, $itemIds);
    }
    
    /**
     * 删除帖子关联商品
     */
    public function delSubjectTagById($subjectId,$itemIds){
        return $this->tagsData->delSubjectTagById($subjectId, $itemIds);
    }
    
}