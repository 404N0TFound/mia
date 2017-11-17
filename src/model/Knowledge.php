<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Knowledge\Knowledge as KnowledgeData;
use \mia\miagroup\Data\Knowledge\KnowledgeCategory;
use mia\miagroup\Data\Knowledge\KnowledgeSubjectRelation;
use mia\miagroup\Data\Knowledge\KnowledgeLabelRelation;
class Knowledge {

    private $knowledgeData = null;
    private $knowledgeCategoryData = null;
    private $knowledgeSubjectRelation = null;
    private $knowledgeLabelRelation = null;

    public function __construct() {
        $this->knowledgeData = new KnowledgeData();
        $this->knowledgeCategoryData = new KnowledgeCategory();
        $this->knowledgeSubjectRelation = new KnowledgeSubjectRelation();
        $this->knowledgeLabelRelation = new KnowledgeLabelRelation();
    }

    /**
     * 获取（全部/带查询条件）的知识分类标签关联信息——默认全部
     */
    public function getKnowledgeCateLabelRelation($condition){
        $result = $this->knowledgeLabelRelation->getKnowledgeCateLabelRelation($condition);
        return $result;
    }
    
    /**
     * 获取知识分类信息
     */
    public function getCategoryInfosByCids($condition,$status){
        $result = $this->knowledgeCategoryData->getKnowledgeCates($condition, $status);
        return $result;
    }
    
    /**
     * 获取知识分类帖子关联信息
     */
    public function getKnowledgeCateSubjectRelation($condition){
        $result = $this->knowledgeSubjectRelation->getKnowledgeCateSubjectRelation($condition);
        return $result;
    }
    
    /**
     * 新增知识分类帖子关联信息
     */
    public function addKnowledgeCateLabelRelation($insertData){
        $result = $this->knowledgeLabelRelation->insertKnowledgeCateLabelRelation($insertData);
        return $result;
    }
    
    /**
     * 新增知识分类标签关联信息
     */
    public function addKnowledgeCateSubjectRelation($insertData){
        $result = $this->knowledgeSubjectRelation->insertKnowledgeCateSubjectRelation($insertData);
        return $result;
    }
    
    /**
     * 新增知识
     */
    public function addKnowledge($insert_data) {
        $result = $this->knowledgeData->addKnowledge($insert_data);
        return $result;
    }
    
    /**
     * 新增知识分类
     */
    public function addKnowledgeCategory($insert_data) {
        $result = $this->knowledgeCategoryData->insertKnowledgeCategory($insert_data);
        return $result;
    }
    
    /**
     * 删除知识分类
     */
    public function updateKnowledgeCategory($condition,$update_data) {
        $result = $this->knowledgeCategoryData->updateCategory($condition,$update_data);
        return $result;
    }
    
    /**
     * 删除知识分类与标签的关联
     */
    public function updateKnowledgeCateLabelRelation($condition,$update_data) {
        $result = $this->knowledgeLabelRelation->updateKnowledgeCateLabelRelation($condition,$update_data);
        return $result;
    }
    
}
