<?php
namespace mia\miagroup\Data\Subject;

use Ice;

class Video extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_video';

    protected $mapping = array(
        'id' => 'i', 
        'subject_id' => 'i', 
        'user_id' => 'i', 
        'video_origin_url' => 's', 
        'source' => 's', 
        'ext_info' => 's', 
        'status' => 'i', 
        'create_time' => 's'
    );

    /**
     * 批量查询视频信息
     */
    public function getBatchVideoInfos($videoIds) {
        if (empty($videoIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'id', $videoIds);
        $videoArr = $this->getRows($where);
        $result = array();
        if (!empty($videoArr)) {
            foreach ($videoArr as $k => $v) {
                $extInfo = $v['ext_info'] ? json_decode($v['ext_info'], true) : array();
                $v['ext_info'] = $extInfo;
                $result[$v['id']] = $v;
            }
        }
        return $result;
    }

    /**
     * 根据选题id批量查询视频信息
     */
    public function getVideoBySubjectIds($subjectIds) {
        if (empty($subjectIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'subject_id', $subjectIds);
        $subjectsArrs = $this->getRows($where, '`id`, `subject_id`');
        if (empty($subjectsArrs)) {
            return array();
        }
        $subjectVideoIds = array();
        foreach ($subjectsArrs as $v) {
            $subjectVideoIds[$v['subject_id']] = $v['id'];
        }
        return $subjectVideoIds;
    }

    /**
     * 获取视频列表
     */
    public function getVideoList($cond) {
        if (empty($cond)) {
            return array();
        }
        $where = array();
        $limit = false;
        $offset = 0;
        if (intval($cond['user_id']) > 0) {
            $where[] = array(':eq', 'user_id', $cond['user_id']);
        }
        if (in_array($cond['status'], array(1, 2))) {
            $where[] = array(':eq', 'status', $cond['status']);
        }
        if (!empty($cond['start_time'])) {
            $where[] = array(':gt', 'create_time', $cond['start_time']);
        }
        if (!empty($cond['end_time'])) {
            $where[] = array(':lt', 'create_time', $cond['end_time']);
        }
        if (intval($cond['limit']) > 0) {
            $offset = ($cond['page'] - 1) > 0 ? (($cond['page'] - 1) * $cond['limit']) : 0;
            $limit = $cond['limit'];
        }
        $data = $this->getRows($where, '`id`', $limit, $offset, '`id` DESC');
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[] = $v['id'];
            }
        }
        $result = $this->getBatchVideoInfos($result);
        return $result;
    }

    /**
     * 获取视频URL
     */
    public function getVideoUrl($originUrl, $type) {
        $lenth = strrpos($originUrl, '.');
        if (!in_array($type, array('mp4', 'm3u8'))) {
            return false;
        }
        if ($lenth === false) { // 天生不带后缀
            $url = $originUrl;
        } else {
            $url = substr($originUrl, 0, $lenth + 1);
        }
        $qiniuConfig = \F_Ice::$ins->workApp->config->get('busconf.qiniu');
        $url = $qiniuConfig['video_host'] . $url . $type;
        return $url;
    }

    /**
     * 添加视频
     */
    public function addVideoBySubject($insertData) {
        $data = $this->insert($insertData);
        return $data;
    }

    /**
     * 更新视频
     */
    public function updateVideoBySubject($setData, $where = []) {
        $data = $this->update($setData, $where);
        return $data;
    }
}
