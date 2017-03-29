<?php
namespace mia\miagroup\Service\Ums;

class Koubei extends \mia\miagroup\Lib\Service {
    
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
        //获取编辑中的素材
        $cond = ['status' => 2, 'op_admin' => $current_op_admin];
        $editing_materials = $this->robotModel->getSubjectMaterialData($cond, 0, 5)['list'];
        //查询锁定解除
        $robot_service->unLockSelectSubjectMaterial($current_op_admin);
        //获取待处理的素材
        $select_codition['status'] = 0;
        $offset = ($page - 1) * $limit;
        $to_do_list = $this->robotModel->getSubjectMaterialData($select_codition, $offset, $limit)['list'];
        //查询锁定
        $robot_service->updateSubjectMaterialStatusByIds($this->robotConfig['subject_material_status']['locked'], $current_op_admin, $to_do_list);
        //获取结果集数据
        $subject_material_ids = array_merge($editing_materials, $to_do_list);
        $subject_materials = $robot_service->getBatchSubjectMaterial($subject_material_ids);
        return $this->succ(['count' => $to_do_list['count'], 'list' => array_values($subject_materials)]);
    }
    
    /**
     * 获取素材列表
     */
    public function getSubjectMaterialList($param, $page = 1, $limit = 20) {
        ;
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
     * 获取马甲列表
     */
    public function getAvatarMaterialList($param, $page = 1, $limit = 20) {
        ;
    }
}