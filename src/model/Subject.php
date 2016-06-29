<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Subject\Subject as SubjectData;
use mia\miagroup\Data\Subject\Video as VideoData;

class Subject {

    protected $subjectData = null;

    protected $videoData = null;

    public function __construct() {
        $this->subjectData = new SubjectData();
        $this->videoData = new VideoData();
    }

    /**
     * //获取帖子基本信息
     *
     * @param type $subjectIds            
     * @return type
     */
    public function getSubjectByIds($subjectIds) {
        // 获取帖子的基本信息
        $subjects = $this->subjectData->getBatchSubjects($subjectIds);
        if (empty($subjects)) {
            return array();
        }
        // 获取帖子附属的视频信息
        $subjectVideoIds = $this->videoData->getVideoBySubjectIds($subjectIds);
        $videos = $this->getBatchVideoInfos($subjectVideoIds);
        foreach ($subjects as $subjectId => $subject) {
            if (!empty($videos[$subjectVideoIds[$subjectId]])) {
                $subjects[$subjectId]['video_info'] = $videos[$subjectVideoIds[$subjectId]];
            }
        }
        return $subjects;
    }

    /**
     * 批量获取用户发布的帖子数
     */
    public function getBatchUserSubjectCounts($userIds) {
        $data = $this->subjectData->getBatchUserSubjectCounts($userIds);
        return $data;
    }

    /**
     * 增加帖子
     */
    public function addSubject($insertData) {
        $data = $this->subjectData->addSubject($insertData);
        return $data;
    }

    /**
     * 添加视频
     */
    public function addVideoBySubject($insertData) {
        $data = $this->videoData->addVideoBySubject($insertData);
        return $data;
    }

    /**
     * 更新视频
     */
    public function updateVideoBySubject($setData, $where = []) {
        $data = $this->videoData->updateVideoBySubject($setData, $where);
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
        $data = $this->subjectData->updateSubject($setData, $subjectId);
        return $data;
    }

    /**
     * 批量查询视频信息
     */
    public function getBatchVideoInfos($videoIds, $videoType = 'm3u8') {
        if (empty($videoIds)) {
            return array();
        }
        $videoArr = $this->videoData->getBatchVideoInfos($videoIds, $videoType);
        $result = array();
        if (!empty($videoArr)) {
            foreach ($videoArr as $v) {
                $video = null;
                $video['id'] = $v['id'];
                $video['subject_id'] = $v['subject_id'];
                $video['is_outer'] = $v['source'] == 'qiniu' ? 0 : 1;
                $video['video_origin_url'] = $v['video_origin_url'];
                switch ($v['source']) {
                    case 'letv':
                        $video['video_url'] = $v['video_origin_url'];
                        $video['video_type'] = 'swf';
                        break;
                    case 'qiniu':
                        $video['video_url'] = $this->videoData->getVideoUrl($v['video_origin_url'], $videoType);
                        $video['video_type'] = $videoType;
                        break;
                }
                $video['status'] = $v['status'];
                if (!empty($v['ext_info']) && is_array($v['ext_info'])) {
                    $video['cover_image'] = !empty($v['ext_info']['cover_image']) ? $v['ext_info']['cover_image'] : $v['ext_info']['thumb_image'];
                    $video['video_time'] = !empty($v['ext_info']['video_time']) ? date('i:s', floor($v['ext_info']['video_time'])) : '00:00';
                }
                $result[$v['id']] = $video;
            }
        }
        return $result;
    }
}
