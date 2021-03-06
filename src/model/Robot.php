<?php
namespace mia\miagroup\Model;

class Robot {
    
    private $avatarMaterialData;
    private $editorSubjectData;
    private $subjectMaterialData;
    private $textMaterialData;
    private $knowledgeMaterialData;
    
    public function __construct() {
        $this->avatarMaterialData = new \mia\miagroup\Data\Robot\AvatarMaterial();
        $this->editorSubjectData = new \mia\miagroup\Data\Robot\EditorSubject();
        $this->subjectMaterialData = new \mia\miagroup\Data\Robot\SubjectMaterial();
        $this->textMaterialData = new \mia\miagroup\Data\Robot\TextMaterial();
        $this->knowledgeMaterialData = new \mia\miagroup\Data\Robot\KnowledgeMaterial();
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
     * 根据ID获取头像素材
     */
    public function getAvatarMaterialById($avatar_material_id) {
        $result = $this->avatarMaterialData->getAvatarMaterialById($avatar_material_id);
        return $result;
    }
    
    /**
     * 根据user_id获取素材ID
     */
    public function getAvatarMaterialByUserId($user_id) {
        $result = $this->avatarMaterialData->getAvatarMaterialByUserId($user_id);
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
     * 新增文本素材
     */
    public function addTextMaterial($text_material_info) {
        $result = $this->textMaterialData->addTextMaterail($text_material_info);
        return $result;
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
     * 根据操作人修改帖子素材状态
     */
    public function updateSubjectMaterialByOpadmin($update_status, $op_admin, $current_status) {
        $result = $this->subjectMaterialData->updateSubjectMaterialByOpadmin($update_status, $op_admin, $current_status);
        return $result;
    }
    
    /**
     * 更新运营帖子信息
     */
    public function updateEditorSubjectById($editor_subject_id, $update_data) {
        $result = $this->editorSubjectData->updateEditorSubjectById($editor_subject_id, $update_data);
        return $result;
    }
    
    /**
     * 更新头像素材信息
     */
    public function updateAvatarMaterialById($avatar_material_id, $update_data) {
        $result = $this->avatarMaterialData->updateAvatarMaterialById($avatar_material_id, $update_data);
        return $result;
    }

    /**
     * 获取文本素材
     */
    public function getTextMaterailById($id) {
        $result = $this->textMaterialData->getTextMaterailById($id);
        return $result;
    }

    /**
     * 更新文本素材信息
     */
    public function updateTextMaterailById($text_material_id, $update_data) {
        $result = $this->textMaterialData->updateTextMaterailById($text_material_id, $update_data);
        return $result;
    }
    
    /**
     * 批量修改知识素材
     */
    public function updateKnowledgeMaterialByIds($set_data, $material_ids) {
        $result = $this->knowledgeMaterialData->updateKnowledgeMaterialById($material_ids, $set_data);
        return $result;
    }
    
    /**
     * 根据操作人修改知识素材状态
     */
    public function updateKnowledgeMaterialByOpadmin($update_status, $op_admin, $current_status) {
        $result = $this->knowledgeMaterialData->updateKnowledgeMaterialByOpadmin($update_status, $op_admin, $current_status);
        return $result;
    }
    
    /**
     * 获取单条知识素材
     */
    public function getSingleKnowledgeMaterial($material_id) {
        $result = $this->knowledgeMaterialData->getKnowledgeMaterialById($material_id);
        return $result;
    }
    
    /**
     * 批量获取帖子素材
     */
    public function getBatchKnowledgeMaterials($material_ids) {
        $result = $this->knowledgeMaterialData->getBatchKnowledgeMaterial($material_ids);
        return $result;
    }
}