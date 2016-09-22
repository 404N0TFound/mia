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

    /**
     * 查找被推荐的分类标签
     */
    public function getCategoryLables($offset=0,$limit=50)
    {
        $sql = "select a.category_id,l.id,l.title from $this->tableName a ,group_subject_label l where a.label_id = l.id order by a.id desc limit $offset, $limit";
        $result = $this->query($sql);
        return $result;
    }
}