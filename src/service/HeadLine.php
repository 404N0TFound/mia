<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\HeadLine as HeadLineModel;
use Qiniu\Auth;
use mia\miagroup\Remote\RecommendedHeadline as HeadlineRemote;
class HeadLine extends \mia\miagroup\Lib\Service {
    
    public $headLineModel;
    public $headlineRemote;
    public function __construct() {
        parent::__construct();
        $this->headLineModel = new HeadLineModel();
        $this->headlineRemote = new HeadlineRemote();
    }
    
    /**
     * 根据头条栏目获取头条
     */
    public function getHeadLinesByChannel($channelId, $page=1, $action = 'init',$currentUid = 0,$subjectIds=[]) {
        //@chaojiang
        //先调用服务
        //调用model
        //merge数据
    }
    
    /**
     * 获取首页轮播头条
     */
    public function getHomePageHeadLines()
    {
        $channelId = 1;
        $data = $this->headLineModel->getHeadLinesByChannel($channelId);
        return $this->succ($data);
    }
    
    /**
     * 获取头条栏目
     */
    public function getHeadLineChannels($channelIds, $status) {
        if (empty($channelIds) || empty($status) || !is_array($channelIds) || !is_array($status)) {
            return $this->error(500);
        }
        
        $channelRes = $this->headLineModel->getHeadLineChannels($channelIds, $status);
        return $this->succ($channelRes);
    }
    
    /**
     * 头条内容查看告知
     */
    public function headLineReadNotify($currentUid, $subjectId, $channelId = 0)
    {
        if(empty($currentUid) || empty($subjectId)){
            return $this->error(500);
        }
        $data = $this->headlineRemote->headlineRead($currentUid, $subjectId, $channelId);
        return $this->succ($data);
    }
    
    /**
     * 获取头条的相关头条
     */
    public function getRelatedHeadLines($subjectId, $currentUid, $channelId = 0)
    {
        if(empty($currentUid) || empty($subjectId)){
            return $this->error(500);
        }
        $data = $this->headlineRemote->headlineRelate($subjectId, $currentUid,$channelId);
        return $this->succ($data);
    }
    
    /**
     * 新增头条栏目
     */
    public function addHeadLineChannel($channelInfo) {
        if (empty($channelInfo) || !is_array($channelInfo)) {
            return $this->error(500);
        }
        $channelInfo['create_time'] = date('Y-m-d H:i:s');
        $channelInfo['status'] = 1;
        $insertRes = $this->headLineModel->addHeadLineChannel($channelInfo);
        if(!$insertRes){
            return $this->error(20000);
        }
        return $this->succ($insertRes);
    }
    
    /**
     * 上线/下线头条栏目
     */
    public function changeHeadLineChannelStatus($channelIds, $status) {
        if (empty($channelIds) || empty($status) || !is_array($channelIds) || !is_array($status)) {
            return $this->error(500);
        }
        
        $changeRes = $this->headLineModel->setChannelStatusByIds($channelIds, $status);
        if(!$changeRes){
            return $this->error(20003);
        }
        return $this->succ($changeRes);
    }
    
    /**
     * 编辑头条栏目
     */
    public function editHeadLineChannel($channelId, $channelInfo) {
        if (empty($channelId) || empty($channelInfo) || !is_array($channelInfo)) {
            return $this->error(500);
        }
        $setData = array();
        if(isset($channelInfo['channel_name'])){
            $setData[] = ['channel_name',$channelInfo['channel_name']];
        }
        if(isset($channelInfo['sort'])){
            $setData[] = ['sort',$channelInfo['sort']];
        }
        
        $editRes = $this->headLineModel->updateHeadLineChannel($channelId, $setData);
        if(!$editRes){
            return $this->error(20001);
        }
        return $this->succ($editRes);
    }
    
    /**
     * 删除头条栏目
     */
    public function delHeadLineChannel($channelId) {
        if(empty($channelId)){
            return $this->error(500);
        }
        $delRes = $this->headLineModel->deleteHeadLineChannel($channelId);
        if(!$delRes){
            $this->error(20002);
        }
        return $this->succ($delRes);
    }
    
    /**
     * 新增运营头条
     */
    public function addOperateHeadLine($headLineInfo)
    {
        if(empty($headLineInfo) || !is_array($headLineInfo)){
            return $this->error(500);
        }
        if(!in_array($headLineInfo['relation_type'], [1,2,3,4,5,6])){
            return $this->error(500);
        }
        $data = $this->headLineModel->addOperateHeadLine($headLineInfo);
        return $this->succ($data);
    }
    
    /**
     * 编辑运营头条
     */
    public function editOperateHeadLine($id, $headLineInfo) {
        //@donghui
        //只能编辑ext_info、page、row、begin_time、end_time
        if(empty($id) || empty($headLineInfo) || !is_array($headLineInfo)){
            return $this->error(500);
        }
        $data = $this->headLineModel->editOperateHeadLine($id,$headLineInfo);
        return $this->succ($data);
    }
    
    /**
     * 删除运营头条
     */
    public function delOperateHeadLine($id) {
        if(empty($id)){
            return $this->error(500);
        }
        $data = $this->headLineModel->delOperateHeadLine($id);
        return $this->succ($data);
    }
    
    /**
     * 获取头条专题
     */
    public function getHeadLineTopics($topicIds, $status) {
        if (empty($topicIds) || empty($status) || !is_array($topicIds) || !is_array($status)) {
            return $this->error(500);
        }
    
        $topicRes = $this->headLineModel->getHeadLineTopics($topicIds, $status);
        if(!empty($topicRes)){
            foreach ($topicRes as $key=> $topic){
                $topicInfo = json_decode($topic['topic_info']);
                $topicRes[$key]['id'] = $topic['id'];
                $topicRes[$key]['title'] = $topicInfo['title'];
            }
        }
        return $this->succ($topicRes);
    }
    
    /**
     * 新增头条专题
     */
    public function addHeadLineTopic($topicInfo) {
        if (empty($topicInfo) || !is_array($topicInfo)) {
            return $this->error(500);
        }
        $setTopicData = array();
        $setTopicInfo = array('title'=>$topicInfo['title'],'cover_image'=>$topicInfo['cover_image']);
        $setTopicData[] = ['topic_info',json_encode($setTopicInfo)];
        $setTopicData[] = ['subject_ids',$setTopicInfo['subject_ids']];
        $setTopicData['status'] = ['subject_ids',$setTopicInfo['subject_ids']];
        $setTopicData['create_time'] = date('Y-m-d H:i:s',time());
        
        $insertRes = $this->headLineModel->addHeadLineTopic($setTopicData);
        return $this->succ($insertRes);
    }
    
    /**
     * 编辑头条专题
     */
    public function editHeadLineTopic($topicId, $topicInfo) {
        if (empty($topicId) || empty($topicInfo) || !is_array($topicInfo)) {
            return $this->error(500);
        }
        $setTopicData = array();
        if(isset($topicInfo['subject_ids'])){
            $setTopicData['subject_ids'] = $topicInfo['subject_ids'];
        }
        if(isset($topicInfo['title']) && isset($topicInfo['cover_image'])){
            $setTopicInfo = array('title'=>$topicInfo['title'],'cover_image'=>$topicInfo['cover_image']);
            $setTopicData['topic_info'] = json_encode($setTopicInfo);
        }
        $editRes = $this->headLineModel->updateHeadLineTopic($topicId, $setTopicData);
        return $this->succ($editRes);
    }
    
    /**
     * 删除头条专题
     */
    public function delHeadLineTopic($topicId) {
        if(empty($topicId)){
            return $this->error(500);
        }
        $delRes = $this->headLineModel->deleteHeadLineTopic($topicId);
        return $this->succ($delRes);
    }
    
    /**
     * 上线/下线头条专题
     */
    public function changeHeadLineTopicStatus($topicIds, $status) {
        if (empty($topicIds) || empty($status) || !is_array($topicIds) || !is_array($status)) {
            return $this->error(500);
        }
    
        $changeRes = $this->headLineModel->setTopicStatusByIds($topicIds, $status);
        return $this->succ($changeRes);
    }
}