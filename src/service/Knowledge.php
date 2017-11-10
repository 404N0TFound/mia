<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Knowledge as KnowledgeModel;
use mia\miagroup\Service\Label as LabelService;
use mia\miagroup\Service\Subject as SubjectService;
class Knowledge extends \mia\miagroup\Lib\Service {

    public $knowledgeModel = null;

    public function __construct() {
        parent::__construct();
        $this->knowledgeModel = new KnowledgeModel();
    }
    
    /**
     * 发布知识素材
     */
    public function issueKnowledge($param) {
        //参数校验
        if (empty($param['user_status']) || empty($param['category_id']) || empty($param['blog_meta']) || !is_array($param['blog_meta'])) {
            return $this->error(500);
        }
        if ($param['min_period'] >= $param['max_period']) {
            return $this->error(500);
        }
        $param['user_id'] = \F_Ice::$ins->workApp->config->get('busconf.user.miaKnowledgeUid');
        $param['author_hidden'] = 1;
        
        $subject_service = new \mia\miagroup\Service\Subject();
        //解析参数
        $parsed_param = $subject_service->parseBlogParam($param, 'knowledge');
        if (empty($parsed_param['subject_info']['title']) || empty($parsed_param['subject_info']['text'])) {
            return $this->error(500);
        }
        //发布知识贴子
        $param['labels'] = !empty($param['labels']) && is_array($param['labels']) ? $param['labels'] : [];
        $result = $subject_service->issue($parsed_param['subject_info'], $parsed_param['items'], $param['labels']);
        if ($result['code'] > 0) {
            return $this->error($result['code'], $result['msg']);
        }
        $knowledge_info = [];
        $knowledge_info['user_id'] = $param['user_id'];
        $knowledge_info['subject_id'] = $result['data']['id'];
        $knowledge_info['title'] = $parsed_param['subject_info']['title'];
        $knowledge_info['text'] = $parsed_param['subject_info']['text'];
        $knowledge_info['user_status'] = $param['user_status'];
        $knowledge_info['min_period'] = $param['min_period'];
        $knowledge_info['max_period'] = $param['max_period'];
        $knowledge_info['accurate_period'] = $param['min_period'] + $param['accurate_period'];
        $knowledge_info['blog_meta'] = $parsed_param['blog_meta'];
        $knowledge_info['status'] = $param['status'];
        $knowledge_info['create_time'] = $result['data']['created'];
        $this->knowledgeModel->addKnowledge($knowledge_info);
        
        //插入分类与帖子关系
        $this->addKnowledgeCateSubjectRelation($param['category_id'], $result['data']['id']);
        
        return $this->succ($result['data']);
    }
    
    /**
     * 获取知识分类列表（一级->二级->标签）
     */
    public function getKnowledgeCateLalbels() {
        //获取分类标签关联关系信息
        $knowledgeCateLabels = $this->knowledgeModel->getKnowledgeCateLabelRelation(array());
        if (empty($knowledgeCateLabels)) {
            return $this->succ(array());
        }
        
        $pCateIds = array();
        $categoryIds = array();
        $labelIds = array();
        //获取关系表中的分类id和标签id，因为多对多关系，使用需要去重
        foreach ($knowledgeCateLabels as $knowledgeCateLabel) {
            if($knowledgeCateLabel['parent_cate_id'] > 0){
                $pCateIds[] = $knowledgeCateLabel['parent_cate_id'];//一级分类
            }
            $categoryIds[] = $knowledgeCateLabel['category_id'];//二级分类
            $labelIds[] = $knowledgeCateLabel['label_id'];
        }
        
        $pCateIds = array_unique($pCateIds);//一级分类id去重
        $categoryIds = array_unique($categoryIds);//分类id去重
        //获取一级、二级分类信息，为了获取分类名称
        $bothCateIds = array_merge($pCateIds,$categoryIds);
        $categoryInfos = $this->knowledgeModel->getCategoryInfosByCids($bothCateIds,array(0,1));
        
        $labelService = new LabelService();
        //获取标签信息
        $labelIds = array_unique($labelIds);//标签id去重
        //为了获取标签名称
        $labelInfos = $labelService->getBatchLabelInfos($labelIds)['data'];
    
        //拼装结果集
        $result = array();
        foreach ($knowledgeCateLabels as $key => $knowledgeCateLabel) {
            $temp['parent_id'] = $knowledgeCateLabel['parent_cate_id'];
            $temp['category_id'] = $knowledgeCateLabel['category_id'];
            $temp['label_id'] = $knowledgeCateLabel['label_id'];
            $temp['status'] = $knowledgeCateLabel['status'];
            //如果存在父分类，获取父分类信息
            if($knowledgeCateLabel['parent_cate_id'] > 0){
                $temp['parent_name'] = $categoryInfos[$knowledgeCateLabel['parent_cate_id']]['name'];
            }
            //如果存在分类，获取分类信息
            if($knowledgeCateLabel['category_id'] > 0){
                $temp['category_name'] = $categoryInfos[$knowledgeCateLabel['category_id']]['name'];
            }
            //获取标签信息
            if ($knowledgeCateLabel['label_id'] > 0) {
                $temp['label_name'] = $labelInfos[$knowledgeCateLabel['label_id']]['title'];
            }

            $result[$knowledgeCateLabel['parent_cate_id']][$knowledgeCateLabel['category_id']][$knowledgeCateLabel['label_id']] = $temp;
        }
        return $this->succ($result);
    }
    
    /**
     * UMS
     * 添加知识分类和帖子的关联关系
     */
    public function addKnowledgeCateSubjectRelation($cate_id,$subject_id){
        if(intval($cate_id) <= 0 || intval($subject_id) <= 0 ){
            return $this->error(500);
        }
        //检查知识分类是否存在
        $cateInfo = $this->knowledgeModel->getCategoryInfosByCids(array($cate_id),array(0,1))[$cate_id];
        if(empty($cateInfo)){
            return $this->error(90004,'该分类不存在');
        }
        
        //检查帖子是否存在
        $subjectService = new SubjectService();
        $subjectInfo = $subjectService->getSingleSubjectById($subject_id)['data'];
        if(empty($subjectInfo)){
            return $this->error(1107);
        }
        //判断是否已经存在关联关系
        $condition = array('subject_id'=>$subject_id,'cate_id'=>$cate_id);
        $relation_res = $this->knowledgeModel->getKnowledgeCateSubjectRelation($condition);
        if(!empty($relation_res)){
            return $this->error(90001,'对应关系已经存在');
        }
        
        $insertData = array();
        $insertData['category_id'] = $cate_id;
        $insertData['subject_id'] = $subject_id;
        //如果分类存在父级分类，将父级分类id也存入关联表
        $insertData['parent_cate_id'] = 0;
        if(intval($cateInfo['parent_id']) > 0){
            $insertData['parent_cate_id'] = $cateInfo['parent_id'];
        }
        $res = $this->knowledgeModel->addKnowledgeCateSubjectRelation($insertData);
        return $this->succ($res);
    }
    
    /**
     * UMS
     * 知识分类下添加标签
     */
    public function addKnowledgeCateLabelRelation($cate_id,$label_title){
        if(intval($cate_id) <= 0 || empty(trim($label_title))){
            return $this->error(500);
        }
        if (mb_strlen($label_title,'utf-8') > 20 || strlen($label_title) <= 0) {
            return $this->error(90002,'标签名字长度不符合要求');
        }
        
        //检查知识分类是否存在
        $cateInfo = $this->knowledgeModel->getCategoryInfosByCids(array($cate_id),array(0,1))[$cate_id];
        if(empty($cateInfo)){
            return $this->error(90004,'该分类不存在');
        }
        //获取标签id
        $labelService = new LabelService();
        //如果不存在，先插入标签，然后存关联关系
        $label_id = $labelService->addLabel($label_title)['data'];
        
        //判断是否已经存在关联关系
        $condition = array('cate_id'=>$cate_id,'label_id'=>$label_id);
        $relation_res = $this->knowledgeModel->getKnowledgeCateLabelRelation($condition);
        if(!empty($relation_res)){
            return $this->error(90001,'对应关系已经存在');
        }
        
        $insertData = array();
        $insertData['category_id'] = $cate_id;
        $insertData['label_id'] = $label_id;
        //如果分类存在父级分类，将父级分类id也存入关联表
        $insertData['parent_cate_id'] = 0;
        if(intval($cateInfo['parent_id']) > 0){
            $insertData['parent_cate_id'] = $cateInfo['parent_id'];
        }
        $res = $this->knowledgeModel->addKnowledgeCateLabelRelation($insertData);
        return $this->succ($res);
    }

}
