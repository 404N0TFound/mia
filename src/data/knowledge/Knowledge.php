<?php
namespace mia\miagroup\Data\Knowledge;

class KnowledgeCategory extends \DB_Query{
    protected $tableName = 'group_knowledge_category';
    protected $dbResource = 'miagroup';
    protected $mapping = [];
    
    /**
     * 查询知识分类
     */
    public function getKnowledgeCates($cateIds=array(), $status = array(1)) {
        $result = [];
        $where = [];
        if(!empty($cateIds)){
            $where[] = ['id',$cateIds];
        }
        $where[] = ['status',$status];
        $data = $this->getRows($where);
        if (!empty($data) && is_array($data)) {
            foreach ($data as $v) {
                $result[$v['id']] = $v;
            }
        }
        return $result;
    }
}