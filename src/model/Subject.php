<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Subject\Subject as SubjectData;
use mia\miagroup\Data\Subject\SubjectCollect;
use mia\miagroup\Data\Subject\SubjectDownload;
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
    protected $subjectCollectData = null;
    protected $subjectDownloadData = null;
    protected $subjectBlogData = null;
    protected $subjectDraftData = null;
    
    public function __construct() {
        $this->subjectData = new SubjectData();
        $this->videoData = new VideoData();
        $this->tabData = new TabData();
        $this->tabOpeationData = new TabNoteOperation();
        $this->subjectCollectData = new SubjectCollect();
        $this->subjectDownloadData = new SubjectDownload();
        $this->subjectBlogData = new \mia\miagroup\Data\Subject\SubjectBlog();
        $this->subjectDraftData = new \mia\miagroup\Data\Subject\SubjectDraft();
    }

    /**
     * 获取首页，推荐栏目，运营数据
     * @param $tabId
     * @param $page
     * @return array
     */
    public function getOperationNoteData($tabId, $page, $timeTag=null)
    {
        if (!empty($tabId)) {
            $conditions['tab_id'] = $tabId;
        } else {
            return [];
        }
        
        $conditions['page'] = $page;
        if(isset($timeTag)){
            $conditions['time_tag'] = $timeTag;
        }
        
        $operationInfos = $this->tabOpeationData->getBatchOperationInfos($conditions);
        //后台管理不需要过滤帖子，直接返回原结果
        if(isset($timeTag) && !empty($operationInfos)){
            return $operationInfos;
        }
        //按位置分组
        $result = [];
        foreach ($operationInfos as $k => $v) {
            $result[$v['row']][$k] = $v;
        }
        //同位置有重复的，随机取一个
        $return = [];
        foreach ($result as $val) {
            shuffle($val);
            $return[] = array_pop($val);
        }
        //返回的键名保留格式
        $data = [];
        foreach ($return as $detail) {
            if($detail['relation_type'] == 'link'){
                $key = $detail['id'] . '_' . $detail['relation_type'];
            }else{
                $key = $detail['relation_id'] . '_' . $detail['relation_type'];
            }
            $data[$key] = $detail;
        }
        return $data;
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
     public function getSubjectInfoByUserId($userId, $currentId = 0, $iPage = 1, $iPageSize = 20, $conditions = []){
         $subject_id = $this->subjectData->getSubjectInfoByUserId($userId, $currentId, $iPage, $iPageSize, $conditions);
         return $subject_id;
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
         $condition['user_id'] = $userId;
         $condition['status'] = 1;
         $result = $this->subjectData->getSubjectList($condition, 0, false);
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
    
    /**
     * 帖子关键数据修改入帖子更新队列
     */
    public function addSubjectUpdateQueue($subject_id) {
        // 获取rediskey
        $key = \F_Ice::$ins->workApp->config->get('busconf.rediskey.subjectKey.subject_update_record.key');
        $redis = new \mia\miagroup\Lib\Redis();
        $redis->lpush($key, $subject_id);
        return true;
    }
    
    /**
     * 判断帖子是否有重复提交
     */
    public function checkReSubmit($subject_info) {
        if (is_array($subject_info)) {
            if (isset($subject_info['user_info'])) {
                unset($subject_info['user_info']);
            }
            $md5_text = md5(json_encode($subject_info));
        } else {
            $md5_text = md5($subject_info);
        }
        // 获取rediskey
        $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.subjectKey.subject_check_resubmit.key'), $md5_text);
        $redis = new \mia\miagroup\Lib\Redis();
        $result = $redis->get($key);
        if ($result == 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 帖子发布成功记录
     */
    public function subjectPublishRecord($subject_info) {
        if (is_array($subject_info)) {
            if (isset($subject_info['user_info'])) {
                unset($subject_info['user_info']);
            }
            $md5_text = md5(json_encode($subject_info));
        } else {
            $md5_text = md5($subject_info);
        }
        // 获取rediskey
        $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.subjectKey.subject_check_resubmit.key'), $md5_text);
        $redis = new \mia\miagroup\Lib\Redis();
        $redis->set($key, 1);
        $redis->expire($key, \F_Ice::$ins->workApp->config->get('busconf.rediskey.subjectKey.subject_check_resubmit.expire_time'));
    }

    /**
     * 根据用户收藏信息，查询收藏信息
     */
    public function getCollectInfo($userId, $sourceId, $type = 1, $status = [0, 1])
    {
        if(empty($userId) || empty($sourceId) || empty($type)) {
            return [];
        }
        $conditions["user_id"] = $userId;
        $conditions["source_id"] = $sourceId;
        $conditions["source_type"] = $type;
        $conditions["status"] = $status;

        $res = $this->subjectCollectData->getCollectInfo($conditions);
        return $res;
    }

    /**
     * 添加收藏
     */
    public function addCollection($userId, $sourceId, $type)
    {
        if(empty($userId) || empty($sourceId) || empty($type)) {
            return 0;
        }
        $insertData["user_id"] = $userId;
        $insertData["source_id"] = $sourceId;
        $insertData["source_type"] = $type;

        $now = date("Y-m-d H:i:s");
        $insertData["update_time"] = $now;
        $insertData["create_time"] = $now;

        $res = $this->subjectCollectData->addCollection($insertData);
        return $res;
    }

    /**
     * 根据用户收藏信息，查询收藏信息
     * @param $setData
     * @param $where
     * @return array
     */
    public function updateCollect($setData, $where)
    {
        if(empty($setData) || empty($where)) {
            return [];
        }
        $res = $this->subjectCollectData->updateCollect($setData, $where);
        return $res;
    }

    /**
     * 用户收藏列表
     * @param $userId
     * @param $page
     * @param $type
     * @param $count
     * @return array
     */
    public function userCollectList($userId, $type = NULL, $page = 1, $count = 20)
    {
        if (empty($userId)) {
            return [];
        }
        $conditions["user_id"] = $userId;
        if(isset($type)) {
            $conditions["type"] = intval($type);
        }
        $conditions['status'] = 1;
        $conditions["offset"] = ($page - 1) * $count;
        $conditions["limit"] = $count;

        $conditions["order"] = "update_time DESC";

        $res = $this->subjectCollectData->getCollectInfo($conditions);
        return $res;
    }

    /**
     * 获取帖子收藏数
     */
    public function getCollectNum($subjectIds)
    {
        if (empty($subjectIds)) {
            return [];
        }
        $res = $this->subjectCollectData->getCollectNum($subjectIds);
        return $res;
    }
    
    /**
     * 新增长文数据
     */
    public function addBlog($blog_info) {
        $result = $this->subjectBlogData->addBlog($blog_info);
        return $result;
    }
    
    /**
     * 修改长文数据
     */
    public function editBlog($subject_id, $blog_info) {
        $result = $this->subjectBlogData->updateBlogBySubjectId($subject_id, $blog_info);
        return $result;
    }
    
    /**
     * 根据帖子id批量获取长文信息
     */
    public function getBlogBySubjectIds($subject_ids, $status = [1, 3])
    {
        $result = $this->subjectBlogData->getBlogBySubjectIds($subject_ids, $status);
        return $result;
    }

    /*
     * 获取plus用户素材列表
     * */
    public function getUserMaterialIds($item_ids, $user_id = 0, $count = 0, $offset = 0, $conditions = [])
    {
        $result = $this->subjectData->getUserMaterialIds($item_ids, $user_id, $count, $offset, $conditions);
        return $result;
    }

    /*
     * 添加素材下载
     * */
    public function insertSubjectDownload($userId, $sourceId, $source_type)
    {
        if(empty($userId) || empty($sourceId) || empty($source_type)) {
            return 0;
        }
        $insertData["user_id"] = $userId;
        $insertData["source_id"] = $sourceId;
        $insertData["source_type"] = $source_type;
        $now = date("Y-m-d H:i:s");
        $insertData["create_time"] = $now;
        $res = $this->subjectDownloadData->insertSubjectDownload($insertData);
        return $res;
    }

    /**
     * 获取帖子下载数
     */
    public function getDownloadNum($subjectIds)
    {
        if (empty($subjectIds)) {
            return [];
        }
        $res = $this->subjectDownloadData->getDownloadNum($subjectIds);
        return $res;
    }
    
    /**
     * 获取长文列表
     * */
    public function getSubjectBlogLists($condition, $page, $limit, $status)
    {
        $result = $this->subjectBlogData->getSubjectBlogLists($condition, $page, $limit, $status);
        return $result;
    }
    
    /**
     * 新增帖子草稿
     */
    public function addSubjectDraft($user_id, $issue_info, $publish_time = null) {
        $draft_data = [];
        $draft_data['user_id'] = $user_id;
        $draft_data['issue_info'] = $issue_info;
        $draft_data['create_time'] = date('Y-m-d H:i:s');
        if (!empty($publish_time)) {
            $draft_data['publish_time'] = $publish_time;
            $draft_data['status'] = 1;
        } else {
            $draft_data['status'] = 0;
        }
        $res = $this->subjectDraftData->addDraft($draft_data);
        return $res;
    }

    public function getFirstSubject($userIds, $source = 1)
    {
        if(empty($userIds)) {
            return [];
        }
        $result = $this->subjectData->getFirstSubject($userIds, $source);
        return $result;
    }
}
