<?php
namespace mia\miagroup\Data\Subject;

use Ice;

class Subject extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subjects';

    protected $mapping = array('id' => 'i', 'title' => 's', 'text' => 's', 'image_url' => 's', 'ext_info' => 's', 'show_age' => 'i', 'user_id' => 'i', 'status' => 'i', 'is_fine' => 'i', 'fancied_count' => 'i', 'created' => 's', 'active_id' => 'i', 'comment_count' => 'i', 'share_count' => 'i', 'shield_text' => 's', 'update_time' => 's', 'regulate' => 'f', 'is_top' => 'i', 'top_time' => 's');

    /**
     * 批量查图片信息
     */
    public function getBatchSubjects($subjectIds, $status = array(1, 2)) {
        if (empty($subjectIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'id', $subjectIds);
        if (!empty($status) && !is_array($status)) {
            $where[] = array('i:eq', 'status', $status);
        } else 
            if (!empty($status) && is_array($status)) {
                $where[] = array('i:in', 'status', $status);
            }
        $subjectsArrs = $this->getRows($where);
        if (empty($subjectsArrs)) {
            return array();
        }
        $result = array();
        foreach ($subjectsArrs as $v) {
            if ($v['status'] == 2) { // 视频转码中按正常处理
                $v['status'] = 1;
            }
            $v['ext_info'] = json_decode($v['ext_info'], true);
            $result[$v['id']] = $v;
        }
        return $result;
    }


    /**
     * 批量获取用户发布的帖子数
     *
     * @param type $userIds            
     * @return type
     */
    public function getBatchUserSubjectCounts($userIds) {
        $result = array();
        
        $where[] = ['user_id', $userIds];
        $where[] = ['status', 1];
        $groupBy = 'id';
        $field = 'id, count(1) as num';
        
        $arrRes = $this->getRows($where, $field, $limit = FALSE, $offset = 0, $orderBy = FALSE, $join = FALSE, $groupBy);
        
        if (!empty($arrRes)) {
            foreach ($arrRes as $res) {
                $result[$res['id']] = intval($res['num']);
            }
        }
        return $result;
    }

    /*
     * 增加帖子
     */
    public function addSubject($insertData) {
        $data = $this->insert($insertData);
        return $data;
    }

    /**
     * 更新帖子
     *
     * @param type $setData            
     * @param type $where            
     * @param type $orderBy            
     * @param type $limit            
     * @return int
     */
    public function updateSubject($setData, $subjectId) {
        if (intval($subjectId) <= 0) {
            return false;
        }
        $where[] = ['id', $subjectId];
        $data = $this->update($setData, $where);
        return $data;
    }
}
