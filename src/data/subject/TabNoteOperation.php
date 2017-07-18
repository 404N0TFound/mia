<?php
namespace mia\miagroup\Data\Subject;

class TabNoteOperation extends \DB_Query
{
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_tab_note_operation';
    protected $mapping = [];

    /**
     * 获取运营信息
     * @param $conditions
     * @return array
     */
    public function getBatchOperationInfos($conditions)
    {
        if (isset($conditions['tab_id'])) {
            $where[] = ['tab_id', $conditions['tab_id']];
        }
        if (isset($conditions['page'])) {
            $where[] = ['page', $conditions['page']];
        }
        if(!isset($conditions['time_tag'])){
            $date = date("Y-m-d H:i:s");
            
            $where[] = [':lt', 'start_time', $date];
            $where[] = [':gt', 'end_time', $date];
        }
        $data = $this->getRows($where);
        $result = [];
        foreach ($data as $v) {
            if ($v['relation_type'] == 'link') {
                $tmpId = $v['tab_id'] . "_" . $v['id'];
            } else {
                $tmpId = $v['tab_id'] . "_" . $v['relation_id'];
            }
            //http转https
            $result[$tmpId . '_' . $v['relation_type']] = array_merge($v, ['ext_info' => json_decode(str_replace('http:\/\/', 'https:\/\/', strval($v['ext_info'])), true)]);
        }
        return $result;
    }
    
    /**
     * 添加运营笔记
     */
    public function addOperateNote($setData)
    {
        $data = $this->insert($setData);
        return $data;
    }
    
    /**
     * 更新运营笔记
     */
    public function updateNoteById($id, $setData)
    {
        $where[] = ['id', $id];
        $data = $this->update($setData, $where);
        return $data;
    }
    
    /**
     * 删除运营笔记
     */
    public function delNoteById($id)
    {
        $where[] = ['id',$id];
        $data = $this->delete($where);
        return $data;
    }
    
    /**
     * 根据ID查询运营笔记
     */
    public function getNoteInfoById($id) {
        if (intval($id) <= 0) {
            return false;
        }
        $where[] = [':eq','id', $id];
        $data = $this->getRow($where);
        if (!empty($data)) {
            //http转https
            $data['ext_info'] = str_replace('http:\/\/', 'https:\/\/', strval($data['ext_info']));
            $data['ext_info'] = json_decode($data['ext_info'], true);
        }
        return $data;
    }
    
    /**
     * 根据relation_id/type查询运营笔记
     */
    public function getNoteByRelationId($relation_id, $relation_type) {
        if (intval($relation_id) <= 0) {
            return false;
        }
        $where[] = [':eq','relation_id', $relation_id];
        $where[] = [':eq','relation_type', $relation_type];
        $data = $this->getRow($where);
        if (!empty($data)) {
            //http转https
            $data['ext_info'] = str_replace('http:\/\/', 'https:\/\/', strval($data['ext_info']));
            $data['ext_info'] = json_decode($data['ext_info'], true);
        }
        return $data;
    }
    
}