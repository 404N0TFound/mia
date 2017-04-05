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
        $material_info = $this->robotModel->getAvatarMaterialById($avatar_material_id);
        //检查素材状态是否正确
        if (empty($material_info) || $material_info['status'] == $this->robotConfig['avatar_material_status']['create_user']) {
            $this->error(500);
        }
        //生成马甲用户
        $user_info['username'] = 'miagroup_robot_' . $avatar_material_id;
        $user_info['nickname'] = $nickname;
        $user_info['password'] = 'a255220a91378ba2f4aad17300ed8ab7';
        $user_info['icon'] = $material_info['avatar'];
        $userService = new \mia\miagroup\Service\User();
        $result = $userService->addMiaUser($user_info);
        if ($result['code'] > 0) {
            $this->error($result['code']);
        }
        //素材表数据更新
        $update_info['nickname'] = $nickname;
        $update_info['category'] = $category;
        $update_info['user_id'] = $result['data'];
        $update_info['generate_time'] = date('Y-m-d H:i:s');
        $update_info['status'] = 2;
        $this->robotModel->updateAvatarMaterialById($avatar_material_id, $update_info);
        return $this->succ($user_info);
    }
    
    /**
     * 生成运营编辑帖子
     */
    public function generateEditorSubject($editor_subject_info) {
        if (empty($editor_subject_info['material_id']) || empty($editor_subject_info['content']) || empty($editor_subject_info['pub_user']) || empty($editor_subject_info['op_admin'])) {
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
            foreach ($editor_subject_info['image'] as $pic) {
                @$img = getimagesize(\F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $pic);
                if ($img) {
                    $imgWidth = $img[0];
                    $imgHeight = $img[1];
                    $insert_data['image'][] = ['url' => $pic, 'width' => $imgWidth, 'height' => $imgHeight];
                }
            }
        }
        //关联商品
        if (!empty($editor_subject_info['relate_item']) || is_array($editor_subject_info['relate_item'])) {
            foreach ($editor_subject_info['relate_item'] as $item) {
                $tmp_item = array();
                if ($item['is_outer'] == 1) {
                    if (empty($item['item_name'])) {
                        continue;
                    } 
                    $tmp_item['name'] = $item['item_name'];
                    $tmp_item['is_outer'] = $item['is_outer'];
                    if (!empty($item['brand_name'])) {
                        $tmp_item['brand_name'] = $item['brand_name'];
                    }
                    if (!empty($item['category_name'])) {
                        $tmp_item['category_name'] = $item['category_name'];
                    }
                    if (!empty($item['item_pic'])) {
                        $tmp_item['item_pic'] = $item['item_pic'];
                    }
                } else {
                    if ($item['item_id'] > 0) {
                        $tmp_item['item_id'] = $item['item_id'];
                    }
                }
                if (!empty($tmp_item)) {
                    $insert_data['relate_item'][] = $tmp_item;
                }
            }
        }
        //关联标签
        if (!empty($editor_subject_info['relate_tag']) || is_array($editor_subject_info['relate_tag'])) {
            foreach ($editor_subject_info['relate_tag'] as $label) {
                if (!empty($label)) {
                    $insert_data['relate_tag'][] = $label;
                }
            }
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
        $insert_data['ext_info'] = $ext_info;
        $insert_data['create_time'] = date('Y-m-d H:i:s');
        $insert_id = $this->robotModel->addEditorSubject($insert_data);
        if (!$insert_id) {
            return $this->error(500);
        }
        //更新素材状态
        $set_data = ['status' => $this->robotConfig['subject_material_status']['used'], 'op_admin' => $editor_subject_info['op_admin']];
        $this->robotModel->updateSubjectMaterialByIds($set_data, $editor_subject_info['material_id']);
        //发布帖子
        $result = $this->publishEditorSubject($insert_id);
        if ($result['code'] > 0) {
            $this->error($result['code']);
        }
        $subject = $result['data'];
        //更新状态
        $set_data = [];
        $set_data['status'] = $this->robotConfig['subject_material_status']['used'];
        $set_data['subject_id'] = $subject['id'];
        $set_data['publish_time'] = $insert_data['create_time'];
        $this->robotModel->updateEditorSubjectById($insert_id, $set_data);
        
        return $this->succ($subject);
    }
    
    /**
     * 发布运营编辑帖子
     */
    public function publishEditorSubject($editor_subject_id) {
        $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);
        $editor_subject_info = $this->robotModel->getBatchEditorSubjectByIds([$editor_subject_id])[$editor_subject_id];
        \DB_Query::switchCluster($preNode);
        if (empty($editor_subject_info)) {
            return $this->error(500);
        }
        $subject_service = new \mia\miagroup\Service\Subject();
        $subject_info = array();
        $subject_info['user_info']['user_id'] = $editor_subject_info['pub_user'];
        $subject_info['title'] = $editor_subject_info['title'];
        $subject_info['text'] = $editor_subject_info['content'];
        $subject_info['created'] = date('Y-m-d H:i:s');
        $subject_info['source'] = \F_Ice::$ins->workApp->config->get('busconf.subject.source.editor'); //帖子数据来自口碑标识
        if(!empty($editor_subject_info['image'])) {
            $subject_info['image_infos'] = $editor_subject_info['image'];
        }
        $label_infos = array();
        if(!empty($editor_subject_info['relate_tag'])) {
            foreach($editor_subject_info['relate_tag'] as $label) {
                $label_infos[] = array('title' => $label);
            }
        }
        $point_info = array();
        $outer_items = array();
        if (!empty($editor_subject_info['relate_item'])) {
            foreach($editor_subject_info['relate_item'] as $item) {
                if (isset($item['is_outer']) && $item['is_outer'] == 1) {
                    $outer_items[] = $item;
                } elseif ($item['item_id'] > 0) {
                    $point_info[] = array('item_id' => $item['item_id']);
                }
            }
        }
        if (!empty($outer_items)) {
            $subject_info['ext_info']['outer_items'] = $outer_items;
        }
        $result = $subject_service->issue($subject_info, $point_info, $label_infos, 0, 1);
        if ($result['code'] > 0) {
            $this->error($result['code']);
        }
        $subject = $result['data'];
        if ($editor_subject_info['ext_info']['is_recommend'] == 1) {
            $subject_service->subjectAddFine($subject['id']);
        }
        return $this->succ($subject);
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
        $result = $this->robotModel->updateSubjectMaterialByOpadmin($this->robotConfig['subject_material_status']['unused'], $op_admin, $this->robotConfig['subject_material_status']['locked']);
        return $this->succ($result);
    }
    
    /**
     * 编辑帖子素材(编辑锁定，并保存草稿)
     */
    public function editMaterialBegin($subject_material_id, $op_admin, $draft = array(), $editor_subject_id = 0) {
        if (empty($op_admin) || empty($subject_material_id)) {
            return $this->error(500);
        }
        //锁定素材状态
        $set_data = array();
        $set_data['op_admin'] = $op_admin;
        $set_data['status'] = $this->robotConfig['subject_material_status']['editing'];
        $set_data['draft'] = $draft;
        $result = $this->robotModel->updateSubjectMaterialByIds($set_data, [$subject_material_id]);
        
        //如果运营帖子被编辑，锁定运营帖子状态
        if ($editor_subject_id > 0) {
            $set_data = array();
            $set_data['status'] = $this->robotConfig['subject_material_status']['editing'];
            $this->robotModel->updateEditorSubjectById($editor_subject_id, $set_data);
        }
        return $this->succ($result);
    }
    
    /**
     * 帖子素材编辑完成(编辑锁定解除，清空草稿箱)
     */
    public function editMaterialEnd($subject_material_id, $op_admin, $editor_subject_id = 0) {
        if (empty($op_admin) || empty($subject_material_id)) {
            return $this->error(500);
        }
        $subject_material = $this->robotModel->getSingleSubjectMaterial($subject_material_id);
        if ($subject_material['op_admin'] != $op_admin) {
            return $this->error(500);
        }
        //重置素材状态
        $set_data = array();
        $set_data['op_admin'] = '';
        $set_data['status'] = $this->robotConfig['subject_material_status']['used'];
        $set_data['draft'] = '';
        $result = $this->robotModel->updateSubjectMaterialByIds($set_data, [$subject_material_id]);
        
        //如果运营帖子完成编辑，重置运营帖子状态
        if ($editor_subject_id > 0) {
            $set_data = array();
            $set_data['status'] = 1;
            $this->robotModel->updateEditorSubjectById($editor_subject_id, $set_data);
        }
        return $this->succ($result);
    }
}