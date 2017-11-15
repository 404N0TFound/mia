<?php
namespace mia\miagroup\Data\Knowledge;

use Ice;

class KnowledgeLabelRelation extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_knowledge_category_label_relation';

    protected $mapping = array();
    // TODO
    
    /**
     * 获取知识分类和标签的关联信息
     * @param array $condition
     * @return array
     */
    public function getKnowledgeCateLabelRelation($condition = array()){
        $where = [];
        if(isset($condition['cate_id'])){
            $where[] = ['category_id',$condition['cate_id']];
        }
        if(isset($condition['label_id'])){
            $where[] = ['label_id',$condition['label_id']];
        }
        if(isset($condition['parent_cate_id'])){
            $where[] = ['parent_cate_id',$condition['parent_cate_id']];
        }
        if(isset($condition['status'])){
            $where[] = ['status',$condition['status']];
        }
        
        $data = $this->getRows($where);
        return $data;
    }
    
    /**
     * 保存蜜芽圈知识分类和标签关系记录
     *
     * @param array $cateLabelRelationInfo
     * @return bool
     */
    public function insertKnowledgeCateLabelRelation($cateLabelRelationInfo) {
        $insertData = $this->insert($cateLabelRelationInfo);
        return $insertData;
    }
}
