<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Knowledge as KnowledgeModel;
use mia\miagroup\Service\Label as LabelService;
use mia\miagroup\Util\NormalUtil;
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
        if (!empty($param['labels']) && is_array($param['labels'])) {
            $parsed_param['labels'] = array_merge($parsed_param['labels'], $param['labels']);
        }
        
        //发布长文贴子
        $result = $subject_service->issue($parsed_param['subject_info'], $parsed_param['items'], $parsed_param['labels']);
        if ($result['code'] > 0) {
            return $this->error($result['code'], $result['msg']);
        }
        $knowledge_info = [];
        $knowledge_info['user_id'] = $param['user_id'];
        $knowledge_info['subject_id'] = $result['data']['id'];
        $knowledge_info['title'] = $parsed_param['subject_info']['title'];
        $knowledge_info['text'] = $parsed_param['subject_info']['text'];
        $knowledge_info['user_status'] = $param['user_status'];
        $knowledge_info['category_id'] = $param['category_id'];
        $knowledge_info['min_period'] = $param['min_period'];
        $knowledge_info['max_period'] = $param['max_period'];
        $knowledge_info['accurate_period'] = $param['min_period'] + $param['accurate_period'];
        $knowledge_info['blog_meta'] = $parsed_param['blog_meta'];
        $knowledge_info['status'] = $parsed_param['subject_info']['status'];
        $knowledge_info['create_time'] = $result['data']['created'];
        $this->knowledgeModel->addKnowledge($knowledge_info);
        
        return $this->succ($result['data']);
    }
    
    /**
     * 获取知识分类列表（一级->二级->标签）
     */
    public function getKnowledgeCateLalbels() {
        //获取分类标签关联关系信息
        $knowledgeCateLabels = $this->knowledgeModel->getKnowledgeCateLabelRelation();
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
        $categoryInfos = $this->knowledgeModel->getCategoryInfos($bothCateIds);
        
        $labelService = new LabelService();
        //获取标签信息
        $labelIds = array_unique($labelIds);//标签id去重
        //为了获取标签名称
        $labelInfos = $labelService->getBatchLabelInfos($labelIds)['data'];
    
        //拼装结果集
        $result = array();
        foreach ($knowledgeCateLabels as $key => $knowledgeCateLabel) {
            //如果存在父分类，获取父分类信息
            if($knowledgeCateLabel['parent_cate_id'] > 0){
                $result['parent_cate_id'] = $categoryInfos[$knowledgeCateLabel['parent_cate_id']];
            }
            //如果存在分类，获取分类信息
            if($knowledgeCateLabel['category_id'] > 0){
                $result[$knowledgeCateLabel['parent_cate_id']][$knowledgeCateLabel['category_id']] = $categoryInfos[$knowledgeCateLabel['category_id']];
            }
            //获取标签信息
            if ($knowledgeCateLabel['label_id'] > 0) {
                $result[$knowledgeCateLabel['parent_cate_id']][$knowledgeCateLabel['category_id']][$knowledgeCateLabel['lable_id']] = $labelInfos[$knowledgeCateLabel['label_id']];
            }
        }
        return $this->succ($result);
    }
    
    /**
     * UMS
     * 添加知识分类和帖子的关联关系
     */
    public function addKnowledgeCateSubjectRelation($subject_id,$cate_id,$user_id,$create_time){
        //判断是否已经存在关联关系
        $condition = array('subject_id'=>$subject_id,'cate_id'=>$cate_id);
        $relation_res = $this->knowledgeModel->getKnowledgeCateSubjectRelation($condition);
        if(!empty($relation_res)){
            return $this->error(90001,'对应关系已经存在');
        }
        $res = $this->knowledgeModel->addKnowledgeCateSubjectRelation($subject_id, $cate_id, $user_id,$create_time);
        return $this->succ($res);
    }
    
    /**
     * UMS
     * 知识分类下添加标签
     */
    public function addKnowledgeCateLabelRelation($cate_id,$label_title,$user_id,$create_time){
        if (mb_strlen($label_title,'utf-8') > 20 || strlen($label_title) <= 0) {
            return $this->error(90002,'标签名字长度不符合要求');
        }
        $labelService = new LabelService();
        $label_info = $labelService->checkIsExistByLabelTitle($label_title)['data'];
        
        if(empty($label_info)){
            $label_id = $labelService->addLabel($label_title)['data'];
        }else{
            $label_id = $label_info['id'];
        }
        
//         //判断数量是否已经达到 6 个
//         $relation_info = $this->knowledgeModel->getBatchKnowledgeCateLabelIds([$cate_id])[$cate_id];
//         if(count($relation_info) >= 6){
//             return $this->error(90000,'关联标签数已达上限');
//         }
        //判断是否已经存在关联关系
        $condition = array('cate_id'=>$cate_id,'label_id'=>$label_id);
        $relation_res = $this->knowledgeModel->getKnowledgeCateLabelRelation($condition);
        if(!empty($relation_res)){
            return $this->error(90001,'对应关系已经存在');
        }
        $res = $this->knowledgeModel->addKnowledgeCateLabelRelation($cate_id, $label_id, $user_id,$create_time);
        return $this->succ($res);
    }

}
