<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\HeadLine as HeadLineModel;
use mia\miagroup\Remote\RecommendedHeadline as HeadlineRemote;
use mia\miagroup\Service\Subject as SubjectServer;
use mia\miagroup\Service\Album as AlbumServer;
use mia\miagroup\Service\Live as LiveServer;
use mia\miagroup\Service\Feed as FeedServer;
class HeadLine extends \mia\miagroup\Lib\Service {
    
    private $headLineModel;
    private $headlineRemote;
    private $liveServer;
    private $albumServer;
    private $subjectServer;
    private $feedServer;
    private $headlineConfig;

    
    public function __construct() {
        parent::__construct();
        $this->headLineModel  = new HeadLineModel();
        $this->headlineRemote = new HeadlineRemote();
        $this->subjectServer  = new SubjectServer();
        $this->liveServer     = new LiveServer();
        $this->albumServer    = new AlbumServer();
        $this->feedServer     = new FeedServer();
        $this->headlineConfig = \F_Ice::$ins->workApp->config->get('busconf.headline');
    }
    
    /**
     * 根据头条栏目获取头条
     */
    public function getHeadLinesByChannel($channelId, $page = 1, $count = 10, $action = '', $currentUid = 0, $headlineIds = array(), $isRecommend = 1) {
        if(empty($channelId)){
            return $this->succ(array());
        }
        
        //获取订阅数据
        if($channelId == $this->headlineConfig['lockedChannel']['attention']['id']) {
            $feedData = $this->feedServer->getExpertFeedSubject($currentUid, $page, $count, true)['data'];
            $headLineList = array();
            foreach ($feedData as $subject) {
                if (!empty($subject['album_article'])) {
                    $tmpData['id'] = $subject['id'] . '_album';
                    $tmpData['type'] = 'album';
                    $tmpData['album'] = $subject;
                } else if (!empty($subject['video_info'])) {
                    $tmpData['id'] = $subject['id'] . '_album';
                    $tmpData['type'] = 'video';
                    $tmpData['video'] = $subject;
                }
                $headLineList[] = $tmpData;
            }
            return $this->succ($headLineList);
        }
        
        //获取推荐服务数据
        if ($isRecommend == 1) {
            $headLineData = $this->headlineRemote->headlineList($currentUid, $channelId, $action);
            //格式化客户端上传的headlineIds
            $headlineIds = $this->_formatClientIds($headlineIds);
            $headLineData = array_merge($headlineIds, $headLineData);
        }
        //获取运营数据
        $operationData = $this->headLineModel->getHeadLinesByChannel($channelId, $page);
        //获取格式化的头条输出数据
        $headLineList = $this->_getFormatHeadlineData(is_array($headLineData) ? $headLineData : array(), $operationData);
        return $this->succ($headLineList);
    }

    /**
     * 获取头条栏目
     */
    public function getHeadLineChannels($channelIds, $status = array(1)) {
        //获取所有栏目
        $channelRes = $this->headLineModel->getHeadLineChannels($channelIds, $status);
        //获取对外屏蔽的栏目
        $shieldIds = array();
        foreach ($this->headlineConfig['lockedChannel'] as $config) {
            if (isset($config['shield']) && $config['shield'] == 1) {
                $shieldIds[] = $config['id'];
            }
        }
        foreach ($channelRes as $key => $channel) {
            if (in_array($channel['id'], $shieldIds)) {
                unset($channelRes[$key]);
            }
        }
        return $this->succ(array('channel_list' => array_values($channelRes)));
    }
    
    /**
     * 头条内容查看告知
     */
    public function headLineReadNotify($channelId, $subjectId, $currentUid = 0)
    {
        if(empty($channelId) || empty($subjectId)){
            return $this->error(500);
        }
        $data = $this->headlineRemote->headlineRead($currentUid, $subjectId, $channelId);
        return $this->succ($data);
    }
    
    /**
     * 获取专题下的头条
     */
    public function getTopicHeadLines($topicId) {
        $topicInfo = $this->getHeadLineTopics(array($topicId));
        $topicInfo = $topicInfo[$topicId];
        if (empty($topicInfo)) {
            return $this->succ(array('headline_list' => array(), 'headline_topic' => (object)array()));
        }
        $subjectIds = $topicInfo['subject_ids'];
        $subjects = $this->subjectServer->getBatchSubjectInfos($subjectIds);
        $headLineList = array();
        foreach ($subjects as $subject) {
            $tmpData = null;
            if (!empty($subject['album_article'])) {
                $tmpData['id'] = $subject['id'] . '_album';
                $tmpData['type'] = 'album';
                $tmpData['album'] = $subject;
            } else if (!empty($subject['video_info'])) {
                $tmpData['id'] = $subject['id'] . '_album';
                $tmpData['type'] = 'video';
                $tmpData['video'] = $subject;
            }
            $headLineList[] = $tmpData;
        }
        return $this->succ(array('headline_list' => $headLineList, 'headline_topic' => $topicInfo));
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
        //获取被锁定的栏目
        $lockedIds = array_column($this->headlineConfig['lockedChannel'], id);
        if (in_array($channelId, $lockedIds)) {
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
        //relation_type校验
        if(!in_array($headLineInfo['relation_type'], $this->headlineConfig['clientServerMapping'])){
            return $this->error(500);
        }
        $setData = array();
        $setData['channel_id'] = $headLineInfo['channel_id'];
        $setData['relation_id'] = $headLineInfo['relation_id'];
        $setData['relation_type'] = $headLineInfo['relation_type'];
        $setData['page'] = $headLineInfo['page'];
        $setData['row'] = $headLineInfo['row'];
        $setData['begin_time'] = $headLineInfo['begin_time'];
        $setData['end_time'] = $headLineInfo['end_time'];
        if (!empty($headLineInfo['title'])) {
            $setData['ext_info']['title'] = $headLineInfo['title'];
        }
        if (!empty($headLineInfo['text'])) {
            $setData['ext_info']['text'] = $headLineInfo['text'];
        }
        if (!empty($headLineInfo['cover_image'])) {
            $setData['ext_info']['cover_image'] = $headLineInfo['cover_image'];
        }
        $data = $this->headLineModel->addOperateHeadLine($headLineInfo);
        return $this->succ($data);
    }
    
    /**
     * 编辑运营头条
     */
    public function editOperateHeadLine($id, $headLineInfo) {
        $headline = $this->headLineModel->getHeadLineById($id);
        if (empty($headline)) {
            return $this->error(500);
        }
        $setData['ext_info'] = $headline['ext_info'];
        if (!empty($headLineInfo['page'])) {
            $setData['page'] = $headLineInfo['page'];
        }
        if (!empty($headLineInfo['row'])) {
            $setData['row'] = $headLineInfo['row'];
        }
        if (!empty($headLineInfo['begin_time'])) {
            $setData['begin_time'] = $headLineInfo['begin_time'];
        }
        if (!empty($headLineInfo['end_time'])) {
            $setData['end_time'] = $headLineInfo['end_time'];
        }
        if (isset($headLineInfo['title'])) {
            $setData['ext_info']['title'] = $headLineInfo['title'];
        }
        if (isset($headLineInfo['text'])) {
            $setData['ext_info']['text'] = $headLineInfo['text'];
        }
        if (isset($headLineInfo['cover_image'])) {
            $setData['ext_info']['cover_image'] = $headLineInfo['cover_image'];
        }
        $data = $this->headLineModel->editOperateHeadLine($id, $headLineInfo);
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
    public function getHeadLineTopics($topicIds, $status = array(1)) {
        $topicRes = $this->headLineModel->getHeadLineTopics($topicIds, $status);
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
    
    /**
     * //格式化客户端传上来的headlineIds
     */
    private function _formatClientIds($headlineIds) {
        $referHeadLineIds = [];
        foreach ($headlineIds as $value) {
            list($id, $type) = explode('_', $value, 2);
            if (array_key_exists($type, $this->headlineConfig['clientServerMapping'])) {
                $type = $this->headlineConfig['clientServerMapping'][$type];
                $referHeadLineIds[] = "{$id}_{$type}";
            }
        
        }
        return $referHeadLineIds;
    }
    
    /**
     * 根据IDs查找并格式化头条数据
     * @param $sortIds 有序的头条ID数组
     * @param $opertionData headLineModel->getHeadLinesByChannel方法输出的数据
     */
    private function _getFormatHeadlineData(array $sortIds, array $opertionData) {
        //收集ID
        $opertionIds = !empty($opertionData) ? array_keys($opertionData) : array();
        $datas = array_merge($sortIds, $opertionIds);
        $subjectIds = [];
        $liveIds    = [];
        $topicIds   = [];
        foreach ($datas as $key => $value) {
            list($relation_id, $relation_type) = explode('_', $value, 2);
            //帖子
            if ($relation_type == 'subject') {
                $subjectIds[] = $relation_id;
            //直播
            } elseif ($relation_type == 'live') {
                $liveIds[] = $relation_id;
            //专题
            } elseif ($relation_type == 'topic') {
                $topicIds[] = $relation_id;
            }
        }

        $subjects = $this->subjectServer->getBatchSubjectInfos($subjectIds)['data'];
        $lives = $this->liveServer->getLiveRoomByIds($liveIds)['data'];
        $topics = $this->getHeadLineTopics($topicIds)['data'];
        //以row为key重新拼装opertionData
        $sortedOpertionData = array();
        foreach ($opertionData as $v) {
            $sortedOpertionData[$v['row']] = $v;
        }
        
        //按序输出头条结果集
        $headLineList = array();
        $num = count($sortIds) + count($opertionData);
        for ($row = 1; $row <= $num; $row ++) {
            $tmpData = null;
            //如果当前位置有运营数据，优先选择运营数据
            if (isset($sortedOpertionData[$row]) && !empty($sortedOpertionData[$row])) {
                $relation_id = $sortedOpertionData[$row]['relation_id'];
                $relation_type = $sortedOpertionData[$row]['relation_type'];
            } else {
                $id = array_shift($sortIds);
                list($relation_id, $relation_type) = explode('_', $id);
            }
            switch ($relation_type) {
                case 'subject':
                    if (isset($subjects[$relation_id]) && !empty($subjects[$relation_id])) {
                        $subject = $subjects[$relation_id];
                        if (!empty($subject['album_article'])) {
                            $tmpData['id'] = $subject['id'] . '_album';
                            $tmpData['type'] = 'album';
                            $tmpData['album'] = $subject;
                        } else if (!empty($subject['video_info'])) {
                            $tmpData['id'] = $subject['id'] . '_album';
                            $tmpData['type'] = 'video';
                            $tmpData['video'] = $subject;
                        }
                    }
                    break;
                case 'live':
                    if (isset($lives[$relation_id]) && !empty($lives[$relation_id])) {
                        $live = $lives[$relation_id];
                        $tmpData['id'] = $live['id'] . '_live';
                        $tmpData['type'] = 'live';
                        $tmpData['live'] = $live;
                    }
                    break;
                case 'topic':
                    if (isset($topics[$relation_id]) && !empty($topics[$relation_id])) {
                        $topic = $topics[$relation_id];
                        $tmpData['id'] = $topics['id'] . '_headline_topic';
                        $tmpData['type'] = 'headline_topic';
                        $tmpData['headline_topic'] = $topic;
                    }
                    break;
            }
            $headLineList[] = $tmpData;
        }
        return $headLineList;
    }
}