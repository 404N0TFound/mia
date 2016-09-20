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
        $where[] = array('id', $subjectIds);
        if (!empty($status)) {
            $where[] = array('status', $status);
        }
        $subjectsArrs = $this->getRows($where);
        if (empty($subjectsArrs)) {
            return array();
        }
        $result = array();
        $emojiUtil = new \mia\miagroup\Util\EmojiUtil();
        foreach ($subjectsArrs as $v) {
            if ($v['status'] == 2) { // 视频转码中按正常处理
                $v['status'] = 1;
            }
            $v['title'] = $emojiUtil->emoji_html_to_unified($v['title']);
            $v['text'] = $emojiUtil->emoji_html_to_unified($v['text']);
            $v['text'] = str_replace('&nbsp;', ' ', $v['text']);
            $v['text'] = strip_tags($v['text']);
            $v['ext_info'] = json_decode($v['ext_info'], true);
            if (!is_array($v['ext_info'])) {
                $v['ext_info'] = json_decode($v['ext_info'], true);
            }
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
     * @param type $subjectId   int/aray      
     * @param type $setData            
     * @param type $where            
     * @param type $orderBy            
     * @param type $limit            
     * @return int
     */
    public function updateSubject($setData, $subjectId) {
        $where[] = ['id', $subjectId];
        $data = $this->update($setData, $where);
        return $data;
    }
    
    /**
     * 设置图片为推荐图片
     * @param array $subjects
     * @param int $setStatus
     * @return boolean
     */
    public function setSubjectRecommendStatus($ids, $setStatus = 1)
    {    
        $setData[] = ['update_time',date('Y-m-d H:i:s')];
        $setData[] = ['is_fine',$setStatus];   
        $where[] = ['id',$ids];
        
        $affectRow = $this->update($setData,$where);
        if ($affectRow) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 更新帖子计数
     */
    public function updateSubjectCount($subjectId, $num, $countType='view_num') {
        if (empty($subjectId)) {
            return false;
        }

        $sql = "update $this->tableName set $countType = $countType+$num where id = $subjectId";
        $result = $this->query($sql);
        return $result;
    }
    
    /**
     * 获取用户的帖子ID
     */
    public function getSubjectInfoByUserId($userId, $currentId = 0, $iPage = 1, $iPageSize = 20){
        
        $offsetLimit = $iPageSize * ($iPage - 1);
        $where[] = ['user_id',$userId];
        if ($userId == $currentId) { 
            //查看自己空间显示正在转码中的视频
            $where[] = ['status',array(1,2)];
            $orderBy = 'id DESC';
            $result = $this->getRows($where,'id',$iPageSize,$offsetLimit,$orderBy);
        } else {
            $where[] = ['status',1];
            $orderBy = 'id DESC';
            $result = $this->getRows($where,'id',$iPageSize,$offsetLimit,$orderBy);
        }
        
        $subject_id = array_column($result, 'id');
        return $subject_id;
    }
    
    /**
     * 删除帖子
     */
    public function delete($subjectId,$userId){
        //删除帖子
        $setData[] = ['status',0];
        $where[] = ['id',$subjectId];
        $where[] = ['user_id',$userId];
        $affect = $this->update($where);
        return $affect;
    }
    
    /**
     * 精选帖子的ids
     * @param int $iPage 页码
     * @param int $iPageSize 一页多少个
     * @return array 帖子ids
     */
    public function getRrecommendSubjectIds($iPage=1, $iPageSize=21){
        $offsetLimit = $iPageSize * ($iPage - 1);
        $where = "g.status = 1 and g.is_fine = 1 and (sh.status = 0 or sh.user_id is null) and g.update_time>'" . date("Y-m-d",strtotime("last month")) . "'";
        $sql = "select g.id
            from {$this->tableName} as g
            left join user_shield as sh
            on g.user_id = sh.user_id
            where {$where}
            order by g.top_time desc, g.update_time desc
            limit {$offsetLimit},{$iPageSize}";
        $subjectsArrs = $this->query($sql);
        return array_column($subjectsArrs, 'id');
    }
    
}
