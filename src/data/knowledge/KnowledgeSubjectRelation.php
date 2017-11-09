<?php
namespace mia\miagroup\Data\Knowledge;

use Ice;

class KnowledgeSubjectRelation extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_knowledge_category_subject_relation';

    protected $mapping = array();
    // TODO
    
    /**
     * 获取知识分类和标签的关联信息
     * @param array $condition
     * @return array
     */
    public function getKnowledgeCateSubjectRelation($condition = array()){
        $where = [];
        if(isset($condition['cate_id'])){
            $where[] = ['category_id',$condition['cate_id']];
        }
        if(isset($condition['subject_id'])){
            $where[] = ['subject_id',$condition['subject_id']];
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
     * @param array $cateSubjectRelationInfo
     * @return bool
     */
    public function insertKnowledgeCateSubjectRelation($cateSubjectRelationInfo) {
        $insertData = $this->insert($cateSubjectRelationInfo);
        return $insertData;
    }
}
