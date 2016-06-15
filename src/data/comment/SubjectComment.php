<?php
namespace mia\miagroup\Data\Comment;
use Ice;
class SubjectComment extends \DB_Query {
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_subjects';
    protected $mapping   = array(
    );
    
    public function selectCommentByIds($commentIds, $status = 1) {
        if (empty($commentIds)) {
            return array();
        }
        $this->db_read = $this->load->database('read', true);
        $commentIds = implode(',', $commentIds);
        $sql = "SELECT * "
            . "FROM {$this->table_comment} "
            . "WHERE id in({$commentIds}) ";
        if ($status < 3) {
            $sql.="AND status={$status} ";
        }
        //批量查询这些ID对于的评论信息
        $data = $this->db_read->query($sql)->result_array();
    
        //2015-7-13 转义表情
        $result = array();
        foreach ($data as $key => $r) {
            $r['comment'] = emoji_html_to_unified($r['comment']);
            $result[$r['id']] = $r;
        }
        return $result;
    }
    
    /**
     * 批量查图片信息
     */
    public function getBatchSubjects($subjectIds, $status = array())
    {
        if (empty($subjectIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'id', $subjectIds);
        if (!empty($status) && !is_array($status)) {
            $where[] = array('i:eq', 'status', $status);
        } else if (!empty($status) && is_array($status)){
            $where[] = array('i:in', 'status', $status);
        }
        $subjectsArrs = $this->getRows($where);
        if (empty($subjectsArrs)) {
            return array();
        }
        $result = array();
        foreach ($subjectsArrs as $v) {
            if ($v['status'] == 2) { //视频转码中按正常处理
                $v['status'] = 1;
            }
            $result[$v['id']] = $v;
        }
        return $result;
    }
}