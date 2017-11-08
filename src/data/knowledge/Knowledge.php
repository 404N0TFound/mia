<?php
namespace mia\miagroup\Data\Knowledge;

class Knowledge extends \DB_Query{
    protected $tableName = 'group_knowledge_category';
    protected $dbResource = 'miagroup';
    protected $mapping = [];
    
    /**
     * 查询知识分类
     */
    public function getKnowledgeCateByIds($cateIds, $status = array(1)) {
        $result = array();
        $where[] = ['id',$cateIds];
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