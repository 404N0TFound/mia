<?php
namespace mia\miagroup\Data\Label;

class LabelCategory extends \DB_Query{
    protected $tableName = 'group_subject_label_category';
    protected $dbResource = 'miagroup';
    protected $mapping = [];
    
    /**
     * 查询标签分类
     */
    public function getCategroyByIds($categoryIds, $status = 1) {
        $result = array();
        $where[] = ['id',$categoryIds];
        $where[] = ['status',$status];
        $data = $this->getRows($where,'id as category_id, name as category_name');
        if (!empty($data) && is_array($data)) {
            foreach ($data as $v) {
                $result[$v['category_id']] = $v;
            }
        }
        return $result;
    }
}