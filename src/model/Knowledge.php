<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Knowledge\Knowledge;
use mia\miagroup\Data\Knowledge\KnowledgeSubjectRelation;
use mia\miagroup\Data\Knowledge\KnowledgeLabelRelation;
class Knowledge {

    public $knowledgeData = null;

    public $knowledgeSubjectRelation = null;

    public $knowledgeLabelRelation = null;

    public function __construct() {
        $this->knowledgeData         = new KnowledgeData();
        $this->knowledgeSubjectRelation     = new KnowledgeSubjectRelation();
        $this->knowledgeLabelRelation     = new KnowledgeLabelRelation();
    }

    /**
     * 获取全部知识分类标签关联信息
     */
    public function getKnowledgeCateLabelRelation($condition=array()){
        
    }
    
    /**
     * 获取知识分类信息
     */
    public function getCategoryInfos($bothCateIds){
    
    }
    
    /**
     * 获取知识分类帖子关联信息
     */
    public function getKnowledgeCateSubjectRelation($condition){
    
    }
    
    /**
     * 新增知识分类帖子关联信息
     */
    public function addKnowledgeCateLabelRelation($cate_id, $label_id, $user_id,$create_time){
    
    }
    
    /**
     * 新增知识分类标签关联信息
     */
    public function addKnowledgeCateSubjectRelation($subject_id, $cate_id, $user_id,$create_time){
    
    }
    
    /**
     * 新增知识
     */
    public function addKnowledge($param) {
        ;
    }
}
