<?php
namespace mia\miagroup\Data\Knowledge;

class KnowledgeTips extends \DB_Query{
    protected $tableName = 'group_knowledge_tips_info';
    protected $dbResource = 'miagroup';
    protected $mapping = [];
    
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
    
}
