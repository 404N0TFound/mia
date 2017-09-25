<?php
namespace mia\miagroup\Data\Subject;

use Ice;

class SubjectDraft extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_draft';
    
    /**
     * 新增帖子草稿
     */
    public function addDraft($insert_data) {
        if (empty($insert_data)) {
            return false;
        }
        foreach ($insert_data as $k => $v) {
            if (in_array($k, ['issue_info'])) {
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
     * 更新帖子草稿
     */
    public function updateDraftById($id, $update_data) {
        if (empty($id) || empty($update_data) || !is_array($update_data)) {
            return false;
        }
        $set_data = array();
        foreach ($update_data as $k => $v) {
            if (in_array($k, ['issue_info'])) {
                $v = json_encode($v);
            }
            $set_data[] = array($k, $v);
        }
        $where[] = array('id', $id);
        $data = $this->update($set_data, $where);
        return $data;
    }
}