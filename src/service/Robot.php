<?php
namespace mia\miagroup\Service;

class Robot extends \mia\miagroup\Lib\Service {

    private $robotModel;
    private $robotConfig;
    

    public function __construct() {
        parent::__construct();
        $this->robotModel = new \mia\miagroup\Model\Robot();
        $this->robotConfig = \F_Ice::$ins->workApp->config->get('busconf.robot');
    }
    
    /**
     * 批量获取帖子素材
     */
    public function getBatchSubjectMaterial($subject_material_ids) {
        $subject_materials = $this->robotModel->getBatchSubjectMaterials($subject_material_ids);
        if (empty($subject_materials)) {
            return $this->succ([]);
        }
        $editor_subjects = $this->robotModel->getBatchEditorSubjectByMaterialIds($subject_material_ids);
        foreach ($subject_materials as $key => $subject_material) {
            if (isset($editor_subjects[$subject_material['id']]) && !empty($editor_subjects[$subject_material['id']])) {
                $subject_materials[$key]['editor_subjects'] = $editor_subjects[$subject_material['id']];
            }
        }
        $result = array();
        foreach ($subject_material_ids as $subject_material_id) {
            if (isset($subject_materials[$subject_material_id]) && !empty($subject_materials[$subject_material_id])) {
                $result[$subject_material_id] = $subject_materials[$subject_material_id];
            }
        }
        return $this->succ($result);
    }
    
    /**
     * 获取单条帖子素材
     */
    public function getSingleSubjectMaterial($subject_material_id, $editor_subject_id = 0) {
        $subject_material = $this->robotModel->getSingleSubjectMaterial($subject_material_id);
        if ($editor_subject_id > 0) {
            $editor_subject = $this->robotModel->getBatchEditorSubjectByIds([$editor_subject_id])[$editor_subject_id];
        }
        return $this->succ(['subject_material' => $subject_material, 'editor_subject' => $editor_subject]);
    }
    
    /**
     * 生成机器人账户
     */
    public function generateRobotAccout($avatar_material_id, $category, $nickname) {
        //检查素材状态是否正确
        //检查昵称是否已存在
        //素材表数据更新
        //生成马甲用户
        //回写user_id
    }
    
    /**
     * 生成运营编辑帖子
     */
    public function generateEditorSubject($editor_subject_info) {
        if (empty($editor_subject_info['material_id']) || empty($editor_subject_info['content'])) {
            $this->error(500);
        }
        $insert_data = array();
        $ext_info = array();
        //素材ID
        $insert_data['material_id'] = $editor_subject_info['material_id'];
        //标题
        if (!empty(trim($editor_subject_info['title']))) {
            $insert_data['title'] = $editor_subject_info['title'];
        }
        //内容
        $insert_data['content'] = $editor_subject_info['content'];
        //图片
        if (!empty($editor_subject_info['image']) || is_array($editor_subject_info['image'])) {
            $insert_data['image'] = $editor_subject_info['image'];
        }
        //关联商品
        if (!empty($editor_subject_info['relate_item']) || is_array($editor_subject_info['relate_item'])) {
            $insert_data['relate_item'] = $editor_subject_info['relate_item'];
        }
        //关联标签
        if (!empty($editor_subject_info['relate_tag']) || is_array($editor_subject_info['relate_tag'])) {
            $insert_data['relate_tag'] = $editor_subject_info['relate_tag'];
        }
        //是否推荐
        if ($editor_subject_info['is_recommend'] == 1) {
            $ext_info['is_recommend'] = 1;
        }
        //发布用户
        $insert_data['pub_user'] = $editor_subject_info['pub_user'];
        //编辑人
        $insert_data['op_admin'] = $editor_subject_info['op_admin'];
        //todo标记
        if ($editor_subject_info['todo_mark'] == 1) {
            $insert_data['todo_mark'] = 1;
        }
        $insert_id = $this->robotModel->addEditorSubject($insert_data);
        return $this->succ($insert_id);
    }
    
    /**
     * 发布运营编辑帖子
     */
    public function publishEditorSubject($editor_subject_id) {
        $editor_subject_info = $this->robotModel->getBatchEditorSubjectByIds([$editor_subject_id])[$editor_subject_id];
        if (empty($editor_subject_info)) {
            return $this->error(500);
        }
        
    }
    
    /**
     * 批量更新帖子素材状态
     */
    public function updateSubjectMaterialStatusByIds($status, $op_admin, $subject_material_ids) {
        if (empty($subject_material_ids) || !in_array($status, $this->robotConfig['subject_material_status']) || empty($op_admin)) {
            return $this->error(500);
        }
        $set_data = ['status' => $status, 'op_admin' => $op_admin];
        $result = $this->robotModel->updateSubjectMaterialByIds($set_data, $subject_material_ids);
        return $this->succ($result);
    }
    
    /**
     * 导入头像素材
     */
    public function importAvatarMaterial($import_data) {
        if (empty($import_data['id'])) {
            return $this->error(500);
        }
        $data = array();
        $data['id'] = $import_data['id'];
        $data['avatar'] = $import_data['link'];
        $data['create_time'] = date('Y-m-d H:i:s');
        $result = $this->robotModel->addAvatarMaterial($data);
        return $this->succ($result);
    }
    
    /**
     * 导入帖子素材
     */
    public function importSubjectMaterial($import_data) {
        if (empty($import_data['id'])) {
            return $this->error(500);
        }
        $data = array();
        $data['id'] = $import_data['id'];
        $data['title'] = $import_data['title'];
        $data['text'] = $import_data['text'];
        if (mb_strlen($import_data['text'], 'utf8') > 30) {
            $data['short_text'] = mb_substr($import_data['text'], 0, 30, 'utf8');
        } else {
            $data['short_text'] = $import_data['text'];
        }
        $import_data['srvPics'] = json_decode($import_data['srvPics'], true);
        $import_pics = array();
        if (!empty($import_data['srvPics'])) {
            foreach ($import_data['srvPics'] as $pic) {
                @$img = getimagesize(\F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $pic);
                if ($img) {
                    $imgWidth = $img[0];
                    $imgHeight = $img[1];
                    $import_pics[] = [
                        'url'    => $pic,
                        'width'  => $imgWidth,
                        'height' => $imgHeight
                    ];
                } else {
                    $import_pics[] = [
                        'url'    => $pic,
                        'width'  => 640,
                        'height' => 640
                    ];
                }
            }
        }
        $data['pics'] = $import_pics;
        $data['category'] = $import_data['catgy'];
        $data['keyword'] = $import_data['keyword'];
        $data['brand'] = $import_data['brand'];
        $data['source'] = $import_data['source'];
        //$data['create_time'] = date('Y-m-d H:i:s');
        
        $result = $this->robotModel->addSubjectMaterial($data);
        return $this->succ($result);
    }
    
    /**
     * 查询锁定解除
     */
    public function unLockSelectSubjectMaterial($op_admin) {
        if (empty($op_admin)) {
            return $this->error(500);
        }
        $result = $this->robotModel->updateSubjectMaterialByOpadmin($this->robotConfig['subject_material_status']['locked'], $op_admin, $this->robotConfig['subject_material_status']['unused']);
        return $this->succ($result);
    }
    
    /**
     * 编辑帖子素材(编辑锁定，并保存草稿)
     */
    public function editMaterialBegin($subject_material_id, $op_admin, $draft = array(), $editor_subject_id = 0) {
    
    }
    
    /**
     * 帖子素材编辑完成(编辑锁定解除，清空草稿箱)
     */
    public function editMaterialEnd($subject_material_id, $op_admin, $editor_subject_id = 0) {
    
    }
}