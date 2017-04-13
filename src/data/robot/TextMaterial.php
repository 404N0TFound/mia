<?php
namespace mia\miagroup\Data\Robot;

class TextMaterial extends \DB_Query
{
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_robot_text_material';
    protected $mapping = [];
    
    /**
     * 新增文本素材
     */
    public function addTextMaterail($material_data) {
        if (empty($material_data)) {
            return false;
        }
        $insert_id = $this->insert($material_data);
        return $insert_id;
    }
    
    /**
     * 根据id获取素材
     */
    public function getTextMaterailById($id) {
        if (empty($id)) {
            return false;
        }
        $where[] = array('id', $id);
        $data = $this->getRow($where);
        return $data;
    }
    
    /**
     * 更新马甲头像素材
     */
    public function updateTextMaterailById($id, $update_data) {
        if (empty($id) || empty($update_data) || !is_array($update_data)) {
            return false;
        }
        $set_data = array();
        foreach ($update_data as $k => $v) {
            $set_data[] = array($k, $v);
        }
        $where[] = array('id', $id);
        $data = $this->update($set_data, $where);
        return $data;
    }
    
}