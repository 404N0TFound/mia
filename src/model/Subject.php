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
     * @param type $subjectIds
     * @return type
     */
    public function getSubjectByIds($subjectIds) {
        //获取帖子的基本信息
        $subjects = $this->subjectData->getBatchSubjects($subjectIds);
        if (empty($subjects)) {
            return array();
        }
        //获取帖子附属的视频信息
        $videos = $this->videoData->getVideoBySubjectIds($subjectIds);
        foreach ($subjects as $subjectId => $subject) {
            if (!empty($videos[$subjectId])) {
                $subjects[$subjectId]['video_info'] = $videos[$subjectId];
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
    public function addSubject($insertData){
	
	$data = $this->subjectData->addSubject($insertData);
	return $data;
    }
    
    
    /**
     * 添加视频
     */
    public function addVideoBySubject($insertData){
	
	$data = $this->videoData->addVideoBySubject($insertData);
	return $data;
    }
    
    /**
     * 更新视频
     */
    public function updateVideoBySubject($setData,$where=[],$orderBy = FALSE, $limit = FALSE){
	
	$data = $this->videoData->updateVideoBySubject($setData,$where,$orderBy,$limit);
	return $data;
    }
    
    /**
     * 更新帖子
     * @param type $setData
     * @param type $where
     * @param type $orderBy
     * @param type $limit
     * @return int
     */
    public function updateSubject($setData,$where=[],$orderBy = FALSE, $limit = FALSE){
	
	$data = $this->subjectData->updateSubject($setData,$where,$orderBy,$limit);
	return $data;
    }
    
    /**
     * 批量查询视频信息
     */
    public function getBatchVideoInfos($videoIds, $videoType = 'm3u8') {

	$data = $this->videoData->getBatchVideoInfos($videoIds,$videoType);
	return $data;
    }
    
    
}
