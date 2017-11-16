<?php
namespace mia\miagroup\Service\Ums;

class Robot extends \mia\miagroup\Lib\Service {
    
    private $robotModel;
    
    public function __construct() {
        parent::__construct();
        $this->robotModel = new \mia\miagroup\Model\Ums\Robot();
        $this->robotConfig = \F_Ice::$ins->workApp->config->get('busconf.robot');
    }
    
    /**
     * 获取待编辑列表
     */
    public function getTodoList($select_codition, $current_op_admin, $page = 1, $limit = 10) {
        if (empty($current_op_admin) || !in_array(array_keys($select_codition), ['category', 'source']))
        $robot_service = new \mia\miagroup\Service\Robot();
        $order_by = 'id asc';
        //获取编辑中的素材
        //$cond = ['status' => 2, 'op_admin' => $current_op_admin];
        //$editing_materials = $this->robotModel->getSubjectMaterialData($cond, 0, 5, $order_by)['list'];
        $editing_materials = array();
        //查询锁定解除
        $robot_service->unLockSelectSubjectMaterial($current_op_admin);
        //获取待处理的素材
        $select_codition['status'] = 0;
        $offset = ($page - 1) * $limit;
        $to_do_list = $this->robotModel->getSubjectMaterialData($select_codition, $offset, $limit, $order_by);
        //查询锁定
        $robot_service->updateSubjectMaterialStatusByIds($this->robotConfig['subject_material_status']['locked'], $current_op_admin, $to_do_list['list']);
        //获取结果集数据
        $subject_material_ids = array_merge($editing_materials, $to_do_list['list']);
        $subject_materials = $robot_service->getBatchSubjectMaterial($subject_material_ids)['data'];
        return $this->succ(['count' => $to_do_list['count'], 'list' => array_values($subject_materials)]);
    }
    
    /**
     * 获取素材列表
     */
    public function getSubjectMaterialList($params, $page = 1, $limit = 20) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        //初始化入参
        $orderBy = 'id asc'; //默认排序
        if (!empty($params['category'])) {
            $condition['category'] = $params['category'];
        }
        if (!empty($params['keyword'])) {
            $condition['keyword'] = $params['keyword'];
        }
        if (!empty($params['source'])) {
            $condition['source'] = $params['source'];
        }
        if (!empty($params['after_id'])) {
            $condition['after_id'] = $params['after_id'];
        }
        if (!empty($params['op_admin'])) {
            $condition['op_admin'] = $params['op_admin'];
        }
        if (isset($params['status']) && in_array($params['status'], $this->robotConfig['subject_material_status'])) {
            $condition['status'] = $params['status'];
        }
        $offset = ($page - 1) * $limit;
        $subject_material_list = $this->robotModel->getSubjectMaterialData($condition, $offset, $limit, $orderBy);
        //获取结果集数据
        $robot_service = new \mia\miagroup\Service\Robot();
        $result['list'] = $robot_service->getBatchSubjectMaterial($subject_material_list['list'])['data'];
        $result['count'] = $subject_material_list['count'];
        return $this->succ($result);
    }
    
    /**
     * 获取素材详情
     */
    public function getSingleSubjectMaterial($subject_material_id, $editor_subject_id = 0) {
        $robot_service = new \mia\miagroup\Service\Robot();
        $result = $robot_service->getSingleSubjectMaterial($subject_material_id, $editor_subject_id);
        return $this->succ($result);
    }
    
    /**
     * 获取运营帖子列表
     */
    public function getEditorSubjectList($params, $page = 1, $limit = 20) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        //初始化入参
        $orderBy = 'id asc'; //默认排序
        if (!empty($params['category'])) {
            $condition['category'] = $params['category'];
        }
        if (!empty($params['source'])) {
            $condition['source'] = $params['source'];
        }
        if (!empty($params['op_admin'])) {
            $condition['op_admin'] = $params['op_admin'];
        }
        if (!empty($params['start_time'])) {
            $condition['start_time'] = $params['start_time'];
        }
        if (!empty($params['end_time'])) {
            $condition['end_time'] = $params['end_time'];
        }
        $condition['status'] = $this->robotConfig['editor_subject_status']['create_subject'];
        $offset = ($page - 1) * $limit;
        $editor_subject_list = $this->robotModel->getEditorSubjectData($condition, $offset, $limit, $orderBy);
        $subject_ids = array();
        if (!empty($editor_subject_list['list'])) {
            foreach ($editor_subject_list['list'] as $v) {
                $subject_ids[] = $v['subject_id'];
            }
        }
        //获取结果集数据
        $subject_service = new \mia\miagroup\Service\Subject();
        $subjects = $subject_service->getBatchSubjectInfos($subject_ids, 0, array('user_info', 'count','content_format', 'share_info'), array())['data'];
        if (!empty($editor_subject_list['list'])) {
            foreach ($editor_subject_list['list'] as $k => $v) {
                $editor_subject_list['list'][$k]['subject'] = $subjects[$v['subject_id']];
            }
        }
        $result['list'] = $editor_subject_list['list'];
        $result['count'] = $editor_subject_list['count'];
        return $this->succ($result);
    }
    
    /**
     * 获取马甲列表
     */
    public function getAvatarMaterialList($params, $page = 1, $limit = false) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        //初始化入参
        $orderBy = 'id asc'; //默认排序
        
        if (!empty($params['category'])) {
            $condition['category'] = $params['category'];
        }
        if (!empty($params['nickname'])) {
            $condition['nickname'] = $params['nickname'];
        }
        if (!empty($params['user_status'])) {
            $condition['user_status'] = $params['user_status'];
        }
        if (!empty($params['majia_child_sex'])) {
            $condition['child_sex'] = $params['majia_child_sex'];
        }
        if (!empty($params['period_start'])) {
            $condition['period_start'] = $params['period_start'];
        }
        if (!empty($params['period_end'])) {
            $condition['period_end'] = $params['period_end'];
        }
        if (in_array($params['status'], $this->robotConfig['avatar_material_status'])) {
            $condition['status'] = $params['status'];
        }
        $offset = ($page - 1) * $limit;

        $avatar_material_list = $this->robotModel->getAvatarMaterialData($condition, $offset, $limit, $orderBy);
        $result['list'] = $avatar_material_list['list'];
        $result['count'] = $avatar_material_list['count'];
        return $this->succ($result);
    }
    
    /**
     * 获取文本素材列表
     */
    public function getTextMaterialList($params, $page = 1, $limit = 20) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        //初始化入参
        $orderBy = 'id asc'; //默认排序
        
        if (!empty($params['category'])) {
            $condition['category'] = $params['category'];
        }
        if (!empty($params['type'])) {
            $condition['type'] = $params['type'];
        }
        if (!empty($params['text'])) {
            $condition['text'] = $params['text'];
        }
        $offset = ($page - 1) * $limit;
        $avatar_material_list = $this->robotModel->getTextMaterialData($condition, $offset, $limit, $orderBy);
        $result['list'] = $avatar_material_list['list'];
        $result['count'] = $avatar_material_list['count'];
        return $this->succ($result);
    }
    
    /**
     * 获取素材抓取来源
     */
    public function getSubjectMaterialSources() {
        $result = $this->robotModel->getSubjectMaterialSource();
        return $this->succ($result);
    }
    
    /**
     * 获取素材内容分类
     */
    public function getSubjectMaterialCategorys() {
        $result = $this->robotModel->getSubjectMaterialCategory();
        return $this->succ($result);
    }
    
    /**
     * 获取全部编辑人
     */
    public function getEditorSubjectAdmin() {
        $result = $this->robotModel->getEditorSubjectAdmin();
        return $this->succ($result);
    }
    

    /**
     * 删除用户素材，物理删除
     * @param $id
     * @return array
     */
    public function delAvatarData($id)
    {
        if (empty($id)) {
            return $this->error(500);
        }
        $result = $this->robotModel->delAvatarData($id);
        if($result === false) {
            return $this->error(500);
        }
        return $this->succ($result);
    }

    /**
     * 删除帖子素材，物理删除
     */
     public function delMaterial($id)
     {
         if (empty($id)) {
             return $this->error(500);
         }
         $result = $this->robotModel->delMaterial($id);
         if($result === false) {
             return $this->error(500);
         }
         return $this->succ($result);
     }
     
     /**
      * 知识编辑页面初始化
      */
     public function knowledgeIssueInit() {
         $result = ['user_period' => [], 'knowledge_category' => []];
         $period_list = [];
         //获取年龄段
         $user_period = \F_Ice::$ins->workApp->config->get('busconf.user.pregnancy_period');
         foreach ($user_period as $k => $pregnancy) {
             foreach ($pregnancy as $period => $v) {
                 $begin = (time() - strtotime($v['end'])) / 86400;
                 $end = (time() - strtotime($v['start'])) / 86400;
                 $period_list[$k][$period] = ['start' => $begin, 'end' => $end];
             }
         }
         $user_period = \F_Ice::$ins->workApp->config->get('busconf.user.child_period');
         foreach ($user_period as $k => $child) {
             foreach ($child as $period => $v) {
                 $begin = (time() - strtotime($v['end'])) / 86400;
                 $end = (time() - strtotime($v['start'])) / 86400;
                 $period_list[$k][$period] = ['start' => $begin, 'end' => $end];
             }
         }
         $period_list['备孕']['备孕'] = ['start' => -1001, 'end' => -1000];
         $knowledge_service = new \mia\miagroup\Service\Knowledge();
         $categorys = $knowledge_service->getKnowledgeCateLalbels()['data'];
         $category_labels = [];
         
         foreach($categorys as $category){
             foreach($category as $clabels){
                 $labels = array_values($clabels['labels']);
                 $labels = array_column($clabels['labels'], 'label_name');
                 $category_labels[$clabels['parent_name']][$clabels['category_id']]['id'] = $clabels['category_id'];
                 $category_labels[$clabels['parent_name']][$clabels['category_id']]['name'] = $clabels['category_name'];
                 $category_labels[$clabels['parent_name']][$clabels['category_id']]['labels'] = $labels;
             }
         }
         
         $result['knowledge_category'] = $category_labels;
         $result['user_period'] = $period_list;
         return $this->succ($result);
     }
     
     /**
      * 获取知识待处理列表
      */
     public function getKnowledgeToDoList($params, $current_op_admin, $page = 1, $limit = 10) {
         if (empty($current_op_admin) || !in_array(array_keys($params), ['category', 'source', 'period', 'days_from', 'days_to']))
             $robot_service = new \mia\miagroup\Service\Robot();
         $order_by = 'id asc';
         $editing_materials = array();
         //查询锁定解除
         $robot_service->unLockKnowledgeMaterial($current_op_admin);
         //获取待处理的素材
         $params['status'] = 0;
         $offset = ($page - 1) * $limit;
         $to_do_list = $this->robotModel->getKnowledgeMaterialData($params, $offset, $limit, $order_by);
         //查询锁定
         $robot_service->updateKnowledgeMaterialStatusByIds($this->robotConfig['knowledge_material_status']['locked'], $current_op_admin, $to_do_list['list']);
         //获取结果集数据
         $knowledge_material_ids = array_merge($editing_materials, $to_do_list['list']);
         $knowledge_materials = $robot_service->getBatchKnowledgeMaterial($knowledge_material_ids)['data'];
         return $this->succ(['count' => $to_do_list['count'], 'list' => array_values($knowledge_materials)]);
     }
     
     /**
      * 获取知识列表
      */
     public function getKnowledgeMaterialList($params, $page = 1, $limit = 10) {
         $result = array('list' => array(), 'count' => 0);
         $condition = array();
         //初始化入参
         $orderBy = 'id asc'; //默认排序
         if (!empty($params['category'])) {
             $condition['category'] = $params['category'];
         }
         if (!empty($params['period'])) {
             $condition['keyword'] = $params['period'];
         }
         if (!empty($params['source'])) {
             $condition['source'] = $params['source'];
         }
         if (!empty($params['after_id'])) {
             $condition['after_id'] = $params['after_id'];
         }
         if (!empty($params['op_admin'])) {
             $condition['op_admin'] = $params['op_admin'];
         }
         if (isset($params['status']) && in_array($params['status'], $this->robotConfig['knowledge_material_status'])) {
             $condition['status'] = $params['status'];
         }
         $offset = ($page - 1) * $limit;
         $knowledge_material_list = $this->robotModel->getKnowledgeMaterialData($condition, $offset, $limit, $orderBy);
         //获取结果集数据
         $robot_service = new \mia\miagroup\Service\Robot();
         $result['list'] = $robot_service->getBatchKnowledgeMaterial($knowledge_material_list['list'])['data'];
         $result['count'] = $knowledge_material_list['count'];
         return $this->succ($result);
     }
     
     /**
      * 获取知识分类列表
      */
     public function getKnowledgeCategory() {
         $result = $this->robotModel->getKnowledgeMaterialCategory();
         return $this->succ($result);
     }
}