<?php
namespace mia\miagroup\Data\Subject;
use Ice;
class Subject extends \DB_Query {
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_subjects';
    protected $mapping   = array(
        'id'       => 'i',
        'title'     => 's',
        'text'   => 's',
        'image_url' => 's',
        'ext_info' => 's',
        'show_age' => 'i',
        'user_id' => 'i',
        'status' => 'i',
        'is_fine' => 'i',
        'fancied_count' => 'i',
        'created' => 's',
        'active_id' => 'i',
        'comment_count' => 'i',
        'share_count' => 'i',
        'shield_text' => 's',
        'update_time' => 's',
        'regulate' => 'f',
        'is_top' => 'i',
        'top_time' => 's',
    );
    
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
