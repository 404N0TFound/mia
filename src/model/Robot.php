<?php
namespace mia\miagroup\Model;

class Robot {
    
    private $avatarMaterialData;
    private $editorSubjectData;
    private $subjectMaterialData;
    
    public function __construct() {
        $this->avatarMaterialData = new \mia\miagroup\Data\Robot\AvatarMaterial();
        $this->editorSubjectData = new \mia\miagroup\Data\Robot\EditorSubject();
        $this->subjectMaterialData = new \mia\miagroup\Data\Robot\SubjectMaterial();
    }
    
    /**
     * 批量获取帖子素材
     */
    public function getBatchSubjectMaterials($subject_material_ids) {
        $result = $this->subjectMaterialData->getBatchSubjectMaterial($subject_material_ids);
        return $result;
    }
    
    /**
     * 获取单条素材详情
     */
    public function getSingleSubjectMaterial($subject_material_id) {
        $result = $this->subjectMaterialData->getSubjectMaterialById($subject_material_id);
        return $result;
    }
    
    /**
     * 批量获取运营帖子
     */
    public function getBatchEditorSubjectByIds($editor_subject_ids) {
        $result = $this->editorSubjectData->getBatchEditorSubjectByIds($editor_subject_ids);
        return $result;
    }
    
    /**
     * 根据素材ID批量获取运营帖子
     */
    public function getBatchEditorSubjectByMaterialIds($subject_material_ids) {
        $result = $this->editorSubjectData->getBatchEditorSubjectByMaterialIds($subject_material_ids);
        return $result;
    }
    
    /**
     * 新增头像素材
     */
    public function addAvatarMaterial($avatar_material_info) {
        $is_exist = $this->avatarMaterialData->getAvatarMaterialById($avatar_material_info['id']);
        if (!$is_exist) {
            $result = $this->avatarMaterialData->addAvatarMaterail($avatar_material_info);
        } else {
            $this->avatarMaterialData->updateAvatarMaterialById($avatar_material_info['id'], $avatar_material_info);
        }
        return $avatar_material_info['id'];
    }
    
    /**
     * 新增帖子素材
     */
    public function addSubjectMaterial($subject_material_info) {
        $is_exist = $this->subjectMaterialData->getSubjectMaterialById($subject_material_info['id']);
        if (!$is_exist) {
            $result = $this->subjectMaterialData->addSubjectMaterail($subject_material_info);
        } else {
            $this->subjectMaterialData->updateSubjectMaterialById($subject_material_info['id'], $subject_material_info);
        }
        return $subject_material_info['id'];
    }
    
    /**
     * 新增运营编辑帖子
     */
    public function addEditorSubject($editor_subject_info) {
        $result = $this->editorSubjectData->addEditorSubject($editor_subject_info);
        return $result;
    }
    
    /**
     * 批量修改帖子素材
     */
    public function updateSubjectMaterialByIds($set_data, $subject_material_ids) {
        $result = $this->subjectMaterialData->updateSubjectMaterialById($subject_material_ids, $set_data);
        return $result;
    }
    
    /**
     * 批量修改帖子素材
     */
    public function updateSubjectMaterialByOpadmin($update_status, $op_admin, $current_status) {
        $result = $this->subjectMaterialData->updateSubjectMaterialByOpadmin($update_status, $op_admin, $current_status);
        return $result;
    }
}