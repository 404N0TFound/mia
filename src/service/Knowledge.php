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
        if (empty($param['category_id']) || empty($param['blog_meta']) || !is_array($param['blog_meta'])) {
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
        if (!empty($param['labels']) && is_array($param['labels'])) {
            foreach ($param['labels'] as $label) {
                $parsed_param['labels'][] = ['title' => $label];
            }
        }
        //发布知识贴子
        $result = $subject_service->issue($parsed_param['subject_info'], $parsed_param['items'], $parsed_param['labels']);
        if ($result['code'] > 0) {
            return $this->error($result['code'], $result['msg']);
        }
        $knowledge_info = [];
        $knowledge_info['user_id'] = $param['user_id'];
        $knowledge_info['subject_id'] = $result['data']['id'];
        $knowledge_info['title'] = $parsed_param['subject_info']['title'];
        $knowledge_info['text'] = $parsed_param['subject_info']['text'];
        if ($param['max_period'] <= -1000) {
            $knowledge_info['user_status'] = 3;
            $param['min_period'] = 0;
            $param['max_period'] = 0;
        } else if ($param['max_period'] < 0) {
            $knowledge_info['user_status'] = 2;
        } else if ($param['min_period'] >= 0) {
            $knowledge_info['user_status'] = 1;
        }
        $knowledge_info['min_period'] = $param['min_period'];
        $knowledge_info['max_period'] = $param['max_period'];
        $knowledge_info['accurate_period'] = $param['accurate_period'];
        $knowledge_info['blog_meta'] = $parsed_param['blog_meta'];
        $knowledge_info['op_admin'] = $param['op_admin'];
        $knowledge_info['status'] = $param['status'];
        $knowledge_info['create_time'] = $result['data']['created'];
        $this->knowledgeModel->addKnowledge($knowledge_info);
        
        //插入分类与帖子关系
        $this->addKnowledgeCateSubjectRelation($param['category_id'], $result['data']['id']);
        
        //更新素材状态
        if (intval($param['material_id']) > 0) {
            $robot_service = new \mia\miagroup\Service\Robot();
            $robot_service->updateKnowledgeMaterialStatusByIds(\F_Ice::$ins->workApp->config->get('busconf.robot.knowledge_material_status.used'), $param['op_admin'], [$param['material_id']]);
        }
        
        return $this->succ(true);
    }
    
    /**
     * 获取知识详情
     */
    public function getKnowledgeDetai($subject_id) {
        
    }
    
    /**
     * 获取知识分类列表（一级->二级->标签）
     */
    public function getKnowledgeCateLalbels() {
        //获取分类标签关联关系信息
        $knowledgeCateLabels = $this->knowledgeModel->getKnowledgeCateLabelRelation(array('status'=>1));
        if (empty($knowledgeCateLabels)) {
            return $this->succ(array());
        }
    
        $labelIds = array();
        //获取关系表中的标签id，因为多对多关系，使用需要去重
        $categoryLabels = array();
        foreach ($knowledgeCateLabels as $knowledgeCateLabel) {
            $categoryLabels[$knowledgeCateLabel['category_id']]['labels'][$knowledgeCateLabel['label_id']] = $knowledgeCateLabel;
            $labelIds[] = $knowledgeCateLabel['label_id'];
        }
        
        $labelService = new LabelService();
        //获取标签信息
        $labelIds = array_unique($labelIds);//标签id去重
        //为了获取标签名称
        $labelInfos = $labelService->getBatchLabelInfos($labelIds)['data'];

        $categoryInfos = $this->knowledgeModel->getCategoryInfosByCids(array(),array(0,1));
    
        //组装知识分类信息
        foreach($categoryInfos as $categoryInfo){
            if($categoryInfo['parent_id'] > 0){
                $temp['category_id'] = $categoryInfo['id'];
                $temp['category_name'] = $categoryInfo['name'];
                $temp['status'] = $categoryInfo['status'];
                $temp['parent_id'] = $categoryInfo['parent_id'];
                $temp['parent_name'] = $categoryInfos[$categoryInfo['parent_id']]['name'];
                $result[$categoryInfo['parent_id']][$categoryInfo['id']]= $temp;
            }
            if($categoryLabels[$categoryInfo['id']]['labels']){
                foreach($categoryLabels[$categoryInfo['id']]['labels'] as $label){
                    $labelTemp['label_id'] = $label['label_id'];
                    $labelTemp['label_name'] = $labelInfos[$label['label_id']]['title'];
                    $result[$categoryInfo['parent_id']][$categoryInfo['id']]['labels'][$label['label_id']] = $labelTemp;
                }
            }
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
        $cateInfo = $this->knowledgeModel->getCategoryInfosByCids(array('cate_ids'=>array($cate_id)),array(1))[$cate_id];
        if(empty($cateInfo)){
            return $this->error(90004,'该分类不存在');
        }
        
        //检查帖子是否存在
        $subjectService = new SubjectService();
        $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);//查主库
        $subjectInfo = $subjectService->getSingleSubjectById($subject_id)['data'];
        \DB_Query::switchCluster($preNode);//结束主库查询
        
        if(empty($subjectInfo)){
            return $this->error(1107);
        }
        //判断是否已经存在关联关系
        $condition = array('subject_id'=>$subject_id,'cate_id'=>$cate_id);
        $relation_res = $this->knowledgeModel->getKnowledgeCateSubjectRelation($condition);
        if(!empty($relation_res[0]) && $relation_res[0]['status'] == 1){
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
        $cateInfo = $this->knowledgeModel->getCategoryInfosByCids(array('cate_ids'=>array($cate_id)),array(1))[$cate_id];
        if(empty($cateInfo)){
            return $this->error(90004,'该分类不存在');
        }
        //获取标签id
        $labelService = new LabelService();
        //如果不存在，先插入标签，然后存关联关系
        $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);//查主库
        $label_id = $labelService->addLabel($label_title)['data'];
        \DB_Query::switchCluster($preNode);//结束主库查询
        //判断是否已经存在关联关系
        $condition = array('cate_id'=>$cate_id,'label_id'=>$label_id);
        $relation_res = $this->knowledgeModel->getKnowledgeCateLabelRelation($condition);

        if(!empty($relation_res[0]) && $relation_res[0]['status'] == 1){
            return $this->error(90001,'对应关系已经存在');
        }
        //如果关联关系被删除过，则更新
        if(!empty($relation_res[0]) && $relation_res[0]['status'] == 0){
            $condition = array();
            $update_data = array();
            $condition[] = ['category_id',$cate_id];
            $condition[] = ['label_id',$label_id];
            $update_data[] = ['status',1];
            $res = $this->knowledgeModel->updateKnowledgeCateLabelRelation($condition,$update_data);
            return $this->succ($res);
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
    
    /**
     * 删除知识分类下标签
     */
    public function delKnowledgeCateLabelRelation($cateId,$labelId) {
        if(empty($cateId) || empty($labelId)){
            return $this->error(500);
        }
        $condition = array();
        $setData = array();
        $condition[] = ['category_id',$cateId];
        $condition[] = ['label_id',$labelId];
        $setData[] = ['status',0];
        $data = $this->knowledgeModel->updateKnowledgeCateLabelRelation($condition,$setData);
        return $this->succ($data);
    }
    
    /**
     * 
     * 新增知识分类
     */
    public function addKnowledgeCategory($param) {
        //参数校验
        if (empty($param) || empty($param['name']) || empty($param['parent_id']) || empty($param['level'])) {
            return $this->error(500);
        }
        //判断知识是否存在
        $condition = array();
        $condition['cate_name'] = $param['name'];
        $condition['parent_id'] = $param['parent_id'];
        $cateInfo = $this->knowledgeModel->getCategoryInfosByCids($condition,array(0,1));
        $cateInfo = array_values($cateInfo);
        
        if(!empty($cateInfo[0]) && $cateInfo[0]['status'] == 1){
            return $this->error(90005,'该分类已存在');
        }
        //如果该分类被删除过，则更新
        if(!empty($cateInfo[0]) && $cateInfo[0]['status'] == 0){
            $condition = array();
            $setData = array();
            $condition[] = ['id',$cateInfo[0]['id']];
            $update_data[] = ['status',1];
            $res = $this->knowledgeModel->updateKnowledgeCategory($condition,$update_data);
            return $this->succ($res);
        }
        
        $knowledgeCategory = [];
        $knowledgeCategory['parent_id'] = intval($param['parent_id']);
        $knowledgeCategory['name'] = trim($param['name']);
        $knowledgeCategory['level'] = intval($param['level']);
        $knowledgeCategory['status'] = 1;
        $knowledgeCategory['modify_author'] = $param['operator'];
        $knowledgeCategory['last_modify'] = $param['oper_time'];
        
        $res = $this->knowledgeModel->addKnowledgeCategory($knowledgeCategory);
        return $this->succ($res);
    }
    
    /**
     * 删除知识分类
     */
    public function delKnowledgeCategory($cateId) {
        if(empty($cateId)){
            return $this->error(500);
        }
        $condition = array();
        $setData = array();
        $condition[] = ['id',$cateId];
        $setData[] = ['status',0];
        $data = $this->knowledgeModel->updateKnowledgeCategory($condition,$setData);
        return $this->succ($data);
    }
    
    /**
     * 同龄首页
     */
    public function sameAgeIndex($user_id, $dvc_id) {
        //获取group_user_info
        //获取近期小贴士
        //获取今日必读
        //获取同龄用户推荐
        //获取同龄内容推荐
    }
    
    /**
     * 获取用户小贴士列表
     */
    public function getUserTipsList($type, $user_id, $dvc_id, $accurate_day = 0, $before_id = 0, $after_id = 0, $limit = 10) {
        //获取贴士列表
        //获取身高体重标准
        //获取用户记录的身高体重
        //拼装结果集
    }
    
    /**
     * 获取今日必读知识
     */
    public function getDailyRecommendKnowledge($user_id, $dvc_id, $accurate_day = 0) {
        ;
    }
}
