<?php
namespace mia\miagroup\Data\Knowledge;

class KnowledgeCategory extends \DB_Query{
    protected $tableName = 'group_knowledge_category';
    protected $dbResource = 'miagroup';
    protected $mapping = [];
    
    /**
     * 查询知识分类
     */
    public function getKnowledgeCates($condition=array(), $status = array(1)) {
        $result = [];
        $where = [];
        if(isset($condition['cate_ids'])){
            $where[] = ['id',$condition['cate_ids']];
        }
        if(isset($condition['cate_name'])){
            $where[] = ['name',$condition['cate_name']];
        }
        
        if(isset($condition['parent_id'])){
            $where[] = ['parent_id',$condition['parent_id']];
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
    
    /**
     * 添加知识分类
     */
    public function insertKnowledgeCategory($insertData) {
        if (empty($insertData)) {
            return false;
        }
        $insertData = $this->insert($insertData);
        return $insertData;
    }
    
    /**
     * 修改知识分类
     */
    public function updateCategory($where, $updateData) {
        if (empty($where) || empty($updateData)) {
            return false;
        }
        
        $data = $this->update($updateData, $where);
        return $data;
    }
}