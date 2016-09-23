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
            $result[$v['label_id']] = $v['category_id'];
        }
        return $result;
    }

    /**
     * 根据标签分类查找关联的标签id
     */
    public function getLabelByCategroyIds($categroyIds, $offset = 0, $limit = 10) {
        if (empty($categroyIds)) {
            return array();
        }
        $where[] = array(':eq', 'status', 1);
        $where[] = array(':in', 'category_id', $categroyIds);
        $data =  $this->getRows($where,'*',$limit,$offset,'id desc');
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['category_id']] = $value;
        }
        
        return $result;
    }

}