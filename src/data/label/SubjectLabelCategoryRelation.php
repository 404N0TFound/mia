<?php
namespace mia\miagroup\Data\Label;

class SubjectLabelCategoryRelation extends \DB_Query
{
    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_label_category_relation';

    /**
     * 查询某个标签的所属的分类
     */
    public function getLabelCategory($labelId) {
        if(empty($labelId)){
            return [];
        }
        $where[] = ['label_id',$labelId];
        $data = $this->getRows($where);
        $result = [];
        foreach ($data as $v) {
            $result[] = $v['category_id'];
        }
        return $result;
    }

    /**
     * 通过分类ID获取标签id
     */
    public function getLabelsByCategoryIds($categroyIds, $offset = 0, $limit = 10){
        if (empty($categroyIds)) {
            return array();
        }
        $where[] = array('status', 1);
        $where[] = array('category_id', $categroyIds);
        $data =  $this->getRows($where,'*',$limit,$offset,'id desc');
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = $value;
        }
        
        return $result;
    }

    /**
     * 获取分类标签关系列表
     */
    public function getRelationList($offset = 0, $limit = 10, $status = array(1)) {
        if (!empty($status)) {
            $where[] = array('status', $status);
        }
        $result =  $this->getRows($where,'*',$limit,$offset,'id desc');
        return $result;
    }
}