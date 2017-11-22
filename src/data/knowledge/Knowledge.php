<?php
namespace mia\miagroup\Data\Knowledge;
use Ice;

class Knowledge extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_knowledge_info';
    
    /**
     * 新增知识
     */
    public function addKnowledge($insert_data) {
        if (empty($insert_data)) {
            return false;
        }
        foreach ($insert_data as $k => $v) {
            if (in_array($k, ['blog_meta', 'ext_info'])) {
                $insert_data[$k] = json_encode($v);
            }
            if ($k == "status") {
                $insert_data[$k] = $v;
            }
        }
        $insert_id = $this->insert($insert_data);
        return $insert_id;
    }
    
    /**
     * 更新知识
     */
    public function updateKnowledgeBySubjectId($subject_id, $update_data) {
        if (empty($subject_id) || empty($update_data) || !is_array($update_data)) {
            return false;
        }
        $set_data = array();
        foreach ($update_data as $k => $v) {
            if (in_array($k, ['blog_meta', 'ext_info'])) {
                $v = json_encode($v);
            }
            $set_data[] = array($k, $v);
        }
        $where[] = array('subject_id', $subject_id);
        $data = $this->update($set_data, $where);
        return $data;
    }
    
    /**
     * 批量查知识
     */
    public function getKnowledgeBySubjectIds($subject_ids, $status = array(1)) {
        if (empty($subject_ids)) {
            return array();
        }
        $where = array();
        $where[] = array('subject_id', $subject_ids);
        if (!empty($status)) {
            $where[] = array('status', $status);
        }
        $subjects = $this->getRows($where);
        if (empty($subjects)) {
            return array();
        }
        $result = array();
        foreach ($subjects as $v) {
            if (!empty($v['ext_info'])) {
                $v['ext_info'] = json_decode($v['ext_info'], true);
            }
            $v['blog_meta'] = json_decode($v['blog_meta'], true);
            $result[$v['subject_id']] = $v;
        }
        return $result;
    }

}