<?php
namespace mia\miagroup\Data\PointTags;

use Ice;

class SubjectPointTags extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_point_tags';

    protected $mapping = array();
    
    /**
     * 保存蜜芽圈帖子标记
     *
     * @param array $tagsInfo 帖子标记
     */
    public function saveSubjectTags($tagsInfo) {
        $insertTags = $this->insert($tagsInfo);
        return $insertTags;
    }
    
    /**
     * 保存蜜芽圈帖子标记信息
     * @param $subjectId 帖子id
     * @param itemIds array() 帖子标记信息
     */
    public function saveBatchSubjectTags($setData){
        if (!is_array($setData) || empty($setData)) {
            return false;
        }
        return  $this->multiInsert($setData);
    }
    
    /**
     * 批量查帖子相关商品id
     * @param $subjectIds array() 图片ids
     * @return array() 图片相关商品id列表
     */
    public function getBatchSubjectItmeIds($subjectIds)
    {
        $itemPointInfo = array();
        $where[] = ['subject_id',$subjectIds];
        $arrRes = $this->getRows($where);
        if (!empty($arrRes)) {
            foreach ($arrRes as $key => $value) {
                $itemPointInfo[$value['subject_id']][] = $value['item_id'];
            }
        }
        return $itemPointInfo;
    }
    
    /**
     * 获取帖子标记的信息
     * @param unknown $subjectId
     * @param unknown $itemId
     * @return unknown
     */
    public function getInfoByIds($subjectId,$itemIds){
        $where[] = ['subject_id',$subjectId];
        $where[] = ['item_id',$itemIds];
        $data = $this->getRows($where);
        return $data;
    }
    
    /**
     * 删除帖子关联商品
     */
    public function delSubjectTagById($subjectId,$itemIds){
        $where[] = ['subject_id',$subjectId];
        $where[] = ['item_id',$itemIds];
        $affect = $this->delete($where);
        return $affect;
    }

}