<?php
namespace mia\miagroup\Data\Robot;

class SubjectMaterial extends \DB_Query
{
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_robot_subject_materials';
    protected $mapping = [];
    
    /**
     * 根据ID批量获取帖子素材
     */
    public function getBatchSubjectMaterial($ids) {
        if (empty($ids)) {
            return array();
        }
        $where[] = array('id', $ids);
        $data = $this->getRows($where, 'id, title, short_text, category, keyword, brand, source, op_admin, status');
    
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $v = $this->_format_row_data($v);
                $result[$v['id']] = $v;
            }
        }
        return $result;
    }
    
    /**
     * 获取单条帖子素材详情
     */
    public function getSubjectMaterialById($id) {
        if (empty($id)) {
            return array();
        }
        $where[] = array('id', $id);
        $data = $this->getRow($where);
        if (!empty($data)) {
            $data = $this->_format_row_data($data);
        }
        return $data;
    }
    
    /**
     * 新增帖子素材
     */
    public function addSubjectMaterail($insert_data) {
        if (empty($insert_data) || empty($insert_data['id'])) {
            return false;
        }
        $emojiUtil = new \mia\miagroup\Util\EmojiUtil();
        foreach ($insert_data as $k => $v) {
            if (in_array($k, ['pics', 'draft'])) {
                $insert_data[$k] = json_encode($v);
            }
        }
        $insert_id = $this->insert($insert_data);
        return $insert_id;
    }
    
    /**
     * 更新帖子素材
     */
    public function updateSubjectMaterialById($id, $update_data) {
        if (empty($id) || empty($update_data) || !is_array($update_data)) {
            return false;
        }
        $set_data = array();
        $emojiUtil = new \mia\miagroup\Util\EmojiUtil();
        foreach ($update_data as $k => $v) {
            if (in_array($k, ['pics', 'draft'])) {
                $v = json_encode($v);
            }
            $set_data[] = array($k, $v);
        }
        $where[] = array('id', $id);
        $data = $this->update($set_data, $where);
        return $data;
    }
    
    /**
     * 根据运营操作人更新帖子素材状态
     */
    public function updateSubjectMaterialByOpadmin($update_status, $op_admin, $current_status) {
        $materialStatusConfig = \F_Ice::$ins->workApp->config->get('busconf.robot.subject_material_status');
        if (empty($op_admin) || !in_array($update_status, $materialStatusConfig) || !in_array($current_status, $materialStatusConfig)) {
            return false;
        }
        $set_data = [];
        $set_data[] = ['status', $update_status];
        if ($update_status == $materialStatusConfig['unused']) {
            $set_data[] = ['op_admin', null];
        }
        $where[] = ['status', $current_status];
        $where[] = ['op_admin', $op_admin];
        $data = $this->update($set_data, $where);
        return $data;
    }
    
    private function _format_row_data($row_data) {
        $emojiUtil = new \mia\miagroup\Util\EmojiUtil();
        $row_data['title'] = $emojiUtil->emoji_html_to_unified($row_data['title']);
        $row_data['text'] = $emojiUtil->emoji_html_to_unified($row_data['text']);
        if (isset($row_data['pics'])) {
            $row_data['pics'] = json_decode($row_data['pics'], true);
        }
        if (isset($row_data['draft'])) {
            $row_data['draft'] = json_decode($row_data['draft'], true);
        }
        if ($row_data['status'] == 1) {
            $row_data['op_admin'] = '';
        }
        return $row_data;
    }
}