<?php
namespace mia\miagroup\Data\Robot;

class EditorSubject extends \DB_Query
{
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_robot_editor_subject';
    protected $mapping = [];
    
    /**
     * 新增运营帖子
     */
    public function addEditorSubject($insert_data) {
        if (empty($insert_data) || empty($insert_data['material_id'])) {
            return false;
        }
        $emojiUtil = new \mia\miagroup\Util\EmojiUtil();
        foreach ($insert_data as $k => $v) {
            if (in_array($k, ['image', 'relate_item', 'relate_tag', 'ext_info'])) {
                $insert_data[$k] = json_encode($v);
            }
        }
        $insert_id = $this->insert($insert_data);
        return $insert_id;
    }
    
    /**
     * 更新运营帖子
     */
    public function updateEditorSubjectById($id, $update_data) {
        if (empty($id) || empty($update_data) || !is_array($update_data)) {
            return false;
        }
        $set_data = array();
        $emojiUtil = new \mia\miagroup\Util\EmojiUtil();
        foreach ($update_data as $k => $v) {
            if (in_array($k, ['image', 'relate_item', 'relate_tag', 'ext_info'])) {
                $v = json_encode($v);
            }
            $set_data[] = array($k, $v);
        }
        $where[] = array('id', $id);
        $data = $this->update($set_data, $where);
        return $data;
    }
    
    /**
     * 根据ID批量获取运营帖子
     */
    public function getBatchEditorSubjectByIds($ids) {
        if (empty($ids)) {
            return array();
        }
        $where[] = array('id', $ids);
        $data = $this->getRows($where);
        
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
     * 根据素材ID批量获取运营帖子
     */
    public function getBatchEditorSubjectByMaterialIds($material_ids) {
        if (empty($material_ids)) {
            return array();
        }
        $where[] = array('material_id', $material_ids);
        $data = $this->getRows($where);
    
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $v = $this->_format_row_data($v);
                $result[$v['material_id']][] = $v;
            }
        }
        return $result;
    }
    
    private function _format_row_data($row_data) {
        $emojiUtil = new \mia\miagroup\Util\EmojiUtil();
        $row_data['title'] = $emojiUtil->emoji_html_to_unified($row_data['title']);
        $row_data['content'] = $emojiUtil->emoji_html_to_unified($row_data['content']);
        $row_data['image'] = json_decode($row_data['image'], true);
        $row_data['relate_item'] = json_decode($row_data['relate_item'], true);
        $row_data['relate_tag'] = json_decode($row_data['relate_tag'], true);
        $row_data['ext_info'] = json_decode($row_data['ext_info'], true);
        if (isset($row_data['ext_info']['is_recommend'])) {
            $row_data['is_recommend'] = $row_data['ext_info']['is_recommend'];
        }
        return $row_data;
    }
}