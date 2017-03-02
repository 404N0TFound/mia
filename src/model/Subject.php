<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Subject\Subject as SubjectData;
use mia\miagroup\Data\Subject\TabNoteOperation;
use mia\miagroup\Data\Subject\Video as VideoData;
use mia\miagroup\Data\Subject\Tab as TabData;
use mia\miagroup\Data\Subject\GroupSubjectRecommendPool;
use mia\miagroup\Lib\Redis;

class Subject {

    protected $subjectData = null;
    protected $videoData = null;
    protected $tabData = null;
    protected $tabOpeationData = null;
    public function __construct() {
        $this->subjectData = new SubjectData();
        $this->videoData = new VideoData();
        $this->tabData = new TabData();
        $this->tabOpeationData = new TabNoteOperation();
    }

    /**
     * 获取首页，推荐栏目，运营数据
     * @param $tabId
     * @param $page
     * @return array
     */
    public function getOperationNoteData($tabId, $page, $timeTag=null)
    {
        if (empty($tabId)) {
            return [];
        }
        $conditions['tab_id'] = $tabId;
        $conditions['page'] = $page;
        if(isset($timeTag)){
            $conditions['time_tag'] = $timeTag;
        }
        
        $operationInfos = $this->tabOpeationData->getBatchOperationInfos($conditions);
        //按位置分组
        foreach ($operationInfos as $k => $v) {
            $result[$v['row']][$k] = $v;
        }
        //同位置有重复的，随机取一个
        foreach ($result as $val) {
            shuffle($val);
            $return[] = array_pop($val);
        }
        //返回的键名保留格式
        $data = [];
        foreach ($return as $detail) {
            $key = $detail['relation_id'] . '_' . $detail['relation_type'];
            if($detail['relation_type'] == 'link'){
                $key = $detail['id'] . '_' . 'link';
            }
            $data[$key] = $detail;
        }
        return $data;
    }

    public function getYuerList($page, $count)
    {
        $conditions['is_fine'] = 1;
        $conditions['iPageSize'] = $count;
        $conditions['page'] = $page;
        $conditions['without_item'] = 1;
        $subjectIds = $this->subjectData->getSubjectList($conditions);
        $subjectIds = array_map(function ($v) {
            return $v . "_subject";
        }, $subjectIds);
        return $subjectIds;
    }

    /**
     * @param $tabNames
     * @return array
     * 批量获取导航分类标签信息
     */
    public function getBatchTabInfos($tabNames)
    {
        if (!is_array($tabNames) || empty($tabNames)) {
            return [];
        }
        $tabNames = array_map(function ($v) {
            return md5($v);
        }, $tabNames);
        $conditions['name_md5'] = $tabNames;
        $tabInfos = $this->tabData->getBatchSubjects($conditions);
        return $tabInfos;
    }

    /**
     * @param $tabNames
     * @return array
     * 获取导航分类标签信息
     */
    public function getTabInfos($tabIds)
    {
        if (!is_array($tabIds) || empty($tabIds)) {
            return [];
        }
        $conditions['id'] = $tabIds;
        $tabInfos = $this->tabData->getBatchSubjects($conditions);
        return $tabInfos;
    }

    /**
     * //获取帖子基本信息
     *
     * @param type $subjectIds            
     * @return type
     */
    public function getSubjectByIds($subjectIds, $status = array(1, 2)) {
        // 获取帖子的基本信息
        $subjects = $this->subjectData->getBatchSubjects($subjectIds, $status);
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
    public function getBatchVideoInfos($videoIds) {
        if (empty($videoIds)) {
            return array();
        }
        $videoArr = $this->videoData->getBatchVideoInfos($videoIds);
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
                        $video['video_url'] = $this->videoData->getVideoUrl($v['video_origin_url'], 'm3u8');
                        $video['video_mp4_url'] = $this->videoData->getVideoUrl($v['video_origin_url'], 'mp4');
                        $video['video_type'] = 'm3u8';
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

    /**
     * 批量查询视频ext_info信息
     */
    public function getBatchVideoExtInfos($videoIds)
    {
        if (empty($videoIds)) {
            return array();
        }
        $videoArr = $this->videoData->getBatchVideoInfos($videoIds);
        if (!empty($videoArr)) {
            return $videoArr;
        } else {
            return array();
        }
    }

    /**
     * 获取推荐池列表
     * @return multitype:multitype:unknown
     */
    public function getRecommendSubjectIdList(){
        $recommendData = new GroupSubjectRecommendPool();
        return $recommendData->getRecommendSubjectIdList();
    }
    
    /**
     * 设置推荐池中的状态为已经推荐过
     * @param array $ids
     * @param int $setStatus
     * @return boolean
     */
    public function setRecommendorStatus($ids, $status = 1){
        $recommendData = new GroupSubjectRecommendPool();
        return $recommendData->setRecommendorStatus($ids, $status);
    }
    
    /**
     * 设置图片为推荐图片
     * @param array ids
     * @param int $setStatus
     * @return boolean
     */
    public function setSubjectRecommendStatus($ids, $setStatus = 1)
    {
        return $this->subjectData->setSubjectRecommendStatus($ids,$setStatus);
    }
    
    /**
     * 帖子阅读写入队列
     */
    public function viewNumRecord($subjectId,$num=1) {
        $read_num_key = \F_Ice::$ins->workApp->config->get('busconf.rediskey.subjectKey.subject_read_num.key');
        $redis = new Redis();
        $data = json_encode(['subject_id'=>$subjectId,'num'=>intval($num)]);
        $redis->lpush($read_num_key, $data);
        return true;
    }
    
    /**
     * 读取帖子阅读记录
     * @param int $num 获取队列中的条数
     */
    public function getViewNumRecord($num) {
        $read_num_key = \F_Ice::$ins->workApp->config->get('busconf.rediskey.subjectKey.subject_read_num.key');
        $redis = new Redis();
        $len = intval($redis->llen($read_num_key));
        if ($len < $num) {
            $num = $len;
        }
        $result = [];
        for ($i = 0; $i < $num; $i ++) {
            $data = json_decode($redis->rpop($read_num_key),true);
            $result[$data['subject_id']] += intval($data['num']);
        }
        return $result;
     }
     
     /**
      * 获取用户的相关帖子ID
      * @param unknown $userId
      * @param number $currentId
      * @param number $iPage
      * @param number $iPageSize
      */
     public function getSubjectInfoByUserId($userId, $currentId = 0, $iPage = 1, $iPageSize = 20){
         $subject_id = $this->subjectData->getSubjectInfoByUserId($userId, $currentId, $iPage, $iPageSize);
         return $subject_id;
     }
     
     /**
      * 删除帖子
      * @param unknown $subjectId
      * @param unknown $userId
      */
     public function delete($subjectId, $userId){
         $affect = $this->subjectData->delete($subjectId, $userId);
         return $affect;
     }
     
     /**
      * 精选帖子的ids
      * @param int $iPage 页码
      * @param int $iPageSize 一页多少个
      * @return array 帖子ids
      */
     public function getRrecommendSubjectIds($iPage=1, $iPageSize=21){
         $subject_ids = $this->subjectData->getRrecommendSubjectIds($iPage,$iPageSize);
         return $subject_ids;
     }
     
     /**
      * 根据用户ID获取帖子信息
      */
     public function getSubjectDataByUserId($subjectId, $userId, $status = array(1,2)){
         $data = $this->subjectData->getSubjectDataByUserId($subjectId, $userId, $status);
         return $data;
     }
     
     /**
      * 分享
      */
     public function addShare($sourceId, $userId, $type, $platform,$status){
         $shareData = new \mia\miagroup\Data\Subject\Share();
         $shareId = $shareData->addShare($sourceId, $userId, $type, $platform,$status);
         return $shareId;
     }
     
     /**
      * 根据用户id查询帖子
      * @param int $userId
      */
     public function getSubjectsByUid($userId){
         $result = $this->subjectData->getSubjectsByUid($userId);
         return $result;
     }
     
     /**
      * 删除或者屏蔽帖子
      * @param array $subjectIds
      * @param  $status
      * @param string $shieldText
      * @return true/false
      */
     public function deleteSubjects($subjectIds,$status,$shieldText){
         $data = $this->subjectData->deleteSubjects($subjectIds,$status,$shieldText);
         return $data;
     }
     
     /**
      * 批量更新帖子的数量
      */
     public function updateSubjectComment($commentNumArr){
         $data = $this->subjectData->updateSubjectComment($commentNumArr);
         return $data;
     }
     
     /**
      * 新增口碑贴信息
      * @param array $koubeiData
      */
     public function addKoubeiSubject($koubeiData){
         $koubeiSubjectData = new KoubeiSubjectData();
         $result = $koubeiSubjectData->saveKoubeiSubject($koubeiData);
         return $result;
     }
     
    /**
     * 帖子置顶
     */
    public function setSubjectTopStatus($subjectIds,$status=1){
        $affect = $this->subjectData->setSubjectTopStatus($subjectIds,$status);
        return $affect;
    }
    
    /**
     * 加入推荐池
     */
    public function addRecommentPool($subjectIds,$dateTime){
        $recommendData = new GroupSubjectRecommendPool();
        $insert_id = $recommendData->addRecommentPool($subjectIds, $dateTime);
        return $insert_id;
    }
    
    /**
     * 获取帖子置顶数量
     */
    public function getSubjectTopNum(){
        return $this->subjectData->getSubjectTopNum();
    }
    
    /**
     * UMS
     * 取消推荐
     */
    public function cacelSubjectIsFine($subjectId){
        $affect = $this->subjectData->cacelSubjectIsFine($subjectId);
        return $affect;
    }
    
    /**
     * 获取活动的帖子（全部/精华）
     */
    public function getSubjectIdsByActiveId($activeId, $type, $page = 1, $limit = 20){
        $subjectIds = $this->subjectData->getSubjectIdsByActiveid($activeId, $type, $page, $limit);
        return $subjectIds;
    }
    
    /**
     * 新增运营笔记
     */
    public function addOperateNote($noteInfo)
    {
        if (is_array($noteInfo['ext_info']) && !empty($noteInfo['ext_info'])) {
            $noteInfo['ext_info'] = json_encode($noteInfo['ext_info']);
        }
        $data = $this->tabOpeationData->addOperateNote($noteInfo);
        return $data;
    }
    
    /**
     * 编辑运营笔记
     */
    public function editOperateNote($noteId, $noteInfo)
    {
        $data = $this->tabOpeationData->updateNoteById($noteId,$noteInfo);
        return $data;
    }
    
    /**
     * 根据ID查询运营笔记
     */
    public function getNoteInfoById($noteId) {
        $data = $this->tabOpeationData->getNoteInfoById($noteId);
        return $data;
    }
    
    /**
     * 删除运营笔记
     */
    public function delOperateNote($noteId)
    {
        $data = $this->tabOpeationData->delNoteById($noteId);
        return $data;
    }
    
    /**
     * 通过relation_id/type获取运营笔记
     */
    public function getOperateNoteByRelationId($relation_id, $relation_type)
    {
        $data = $this->tabOpeationData->getNoteByRelationId($relation_id, $relation_type);
        return $data;
    }
}
