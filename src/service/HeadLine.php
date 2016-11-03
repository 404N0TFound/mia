<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\HeadLine as HeadLineModel;
use mia\miagroup\Remote\RecommendedHeadline as HeadlineRemote;
use mia\miagroup\Service\Subject as SubjectServer;
use mia\miagroup\Service\Album as AlbumServer;
use mia\miagroup\Service\Live as LiveServer;
use mia\miagroup\Service\Feed as FeedServer;
use mia\miagroup\Service\User as UserServer;
class HeadLine extends \mia\miagroup\Lib\Service {
    
    private $headLineModel;
    private $headlineRemote;
    private $liveServer;
    private $albumServer;
    private $subjectServer;
    private $feedServer;
    private $userServer;
    private $headlineConfig;
    
    public function __construct() {
        parent::__construct();
        $this->headLineModel  = new HeadLineModel();
        $this->headlineRemote = new HeadlineRemote();
        $this->subjectServer  = new SubjectServer();
        $this->liveServer     = new LiveServer();
        $this->albumServer    = new AlbumServer();
        $this->feedServer     = new FeedServer();
        $this->userServer     = new UserServer();
        $this->headlineConfig = \F_Ice::$ins->workApp->config->get('busconf.headline');
    }
    
    /**
     * 根据头条栏目获取头条
     */
    public function getHeadLinesByChannel($channelId, $page = 1, $count = 10, $action = '', $currentUid = 0, $headlineIds = array()) {
        if(empty($channelId)){
            return $this->succ(array());
        }
        //获取订阅数据
        if($channelId == $this->headlineConfig['lockedChannel']['attention']['id']) {
            $feedData = $this->feedServer->getExpertFeedSubject($currentUid, $currentUid, $page, $count)['data'];
            $headLineList = array();
            foreach ($feedData as $subject) {
                $tmpData = [];
                if (!empty($subject['album_article'])) {
                    $tmpData['id'] = $subject['id'] . '_album';
                    $tmpData['type'] = 'album';
                    $tmpData['album'] = $subject;
                    $headLineList[] = $tmpData;
                } else if (!empty($subject['video_info'])) {
                    $tmpData['id'] = $subject['id'] . '_video';
                    $tmpData['type'] = 'video';
                    $tmpData['video'] = $subject;
                    $headLineList[] = $tmpData;
                }
                
            }
            return $this->succ($headLineList);
        }
        if (intval($currentUid) > 0) {
            $uniqueFlag = $currentUid;
        } else { //不登录情况下用户的唯一标识
            $uniqueFlag = $this->ext_params['dvc_id'] ? $this->ext_params['dvc_id'] : $this->ext_params['cookie'];
        }
        $headLineData = $this->headlineRemote->headlineList($channelId, $action, $uniqueFlag, $count);
        if ($action == 'init' && $channelId == $this->headlineConfig['lockedChannel']['recommend']['id']) {
            //格式化客户端上传的headlineIds
            $headlineIds = $this->_formatClientIds($headlineIds);
            $headLineData = array_unique(array_merge($headlineIds, $headLineData));
        }
        if ($action != 'refresh') {
            //获取运营数据
            $operationData = $this->headLineModel->getHeadLinesByChannel($channelId, $page);
            //推荐数据、运营数据去重
            $headLineData = array_diff($headLineData, array_intersect($headLineData, array_keys($operationData)));
        }
        //获取格式化的头条输出数据
        if ($channelId == $this->headlineConfig['lockedChannel']['homepage']['id']) {
            //首页轮播只显示基本信息
            $baseInfo = true;
        } else {
            $baseInfo = false;
        }
        $headLineList = $this->_getFormatHeadlineData(is_array($headLineData) ? $headLineData : array(), $operationData, $baseInfo);
        if ($channelId == 4 && $action == 'init') {
            $tmp['id'] = '123_banner';
            $tmp['type'] = 'banner';
            $tmp['banner'] = array(
                'pic' => array(
                    'url' => 'http://img.miyabaobei.com/d1/p4/2016/08/31/b5/28/b5284f9290ed714459e2e3aecbc7d8db338789237.jpg',
                    'width' => 358,
                    'height' => 220
                ),
                'url' => 'miyabaobei://productDetail?id=1000324',
                'content' => 'banner类型测试数据',
                'source' => '蒲老湿',
                'view_num' => 1293840,
            );
            array_unshift($headLineList, $tmp);
        }
        return $this->succ($headLineList);
    }
    
    /**
     * ums获取头条栏目运营数据
     */
    public function getOperateHeadLineChannelContent($channelId) {
        //获取运营数据
        $operationData = $this->headLineModel->getHeadLinesByChannel($channelId, 0, 0, true);
        if (empty($operationData)) {
            return $this->succ(array());
        }
        //获取格式化的头条输出数据
        $headLineList = $this->_getFormatHeadlineData(array_keys($operationData), array());
        foreach ($headLineList as $k => $v) {
            list($id, $type) = explode('_', $v[id], 2);
            if (array_key_exists($type, $this->headlineConfig['clientServerMapping'])) {
                $type = $this->headlineConfig['clientServerMapping'][$type];
                $headLineId = "{$id}_{$type}";
                $headLineList[$k]['config_data'] = $operationData[$headLineId];
            }
            
        }
        return $this->succ($headLineList);
    }

    /**
     * 获取推荐的订阅用户
     */
    public function getHeadLineRecommenduser($currentUid=0)
    {
        $userids = [];
        if($currentUid>0){
            $userRelationService = new \mia\miagroup\Service\UserRelation();
            $expertUserInfo = $userRelationService->getAllAttentionExpert($currentUid)['data'];
            $userids = array_keys($expertUserInfo);
        }
        $expertIds = $this->headlineConfig['expert'];
        $user_ids = array_unique(array_merge($userids,$expertIds));
        $user_ids = array_intersect($user_ids, $expertIds);
        $userInfos = $this->userServer->getUserInfoByUids($user_ids,$currentUid)['data'];
        foreach ($user_ids as $key => $userId) {
            if(isset($userInfos[$userId])){
                $data[] = $userInfos[$userId];
            }
        }
        return $this->succ($data);
    }

    /**
     * 获取头条栏目
     */
    public function getHeadLineChannels($channelIds = array(), $status = array(1), $isAll = 0) {
        //获取所有栏目
        $channelRes = $this->headLineModel->getHeadLineChannels($channelIds, $status);
        //获取对外屏蔽的栏目
        if ($isAll == 0) {
            $shieldIds = array();
            foreach ($this->headlineConfig['lockedChannel'] as $config) {
                if (isset($config['shield']) && $config['shield'] == 1) {
                    $shieldIds[] = $config['id'];
                }
            }
            //配置里的id对应的是数据库id
            foreach ($channelRes as $key => $channel) {
                if (in_array($channel['id'], $shieldIds)) {
                    unset($channelRes[$key]);
                    continue;
                }
                if (isset($this->headlineConfig['channelStyle'][$channel['id']])) {
                    $channelRes[$key]['channel_style'] = $this->headlineConfig['channelStyle'][$channel['id']];
                } else {
                    $channelRes[$key]['channel_style'] = $this->headlineConfig['channelStyle']['default'];
                }
            }
        }
        return $this->succ(array('channel_list' => array_values($channelRes)));
    }
    
    /**
     * 头条内容查看告知
     */
    public function headLineReadNotify($subjectId, $channelId ,$currentUid = 0, $type ='video')
    {
        if(empty($channelId) || empty($subjectId)){
            return $this->succ(array());
        }
        if (intval($currentUid) > 0) {
            $uniqueFlag = $currentUid;
        } else { //不登录情况下用户的唯一标识
            $uniqueFlag = $this->ext_params['dvc_id'] ? $this->ext_params['dvc_id'] : $this->ext_params['cookie'];
        }
        $data = $this->headlineRemote->headlineRead($channelId, $subjectId, $uniqueFlag);
        return $this->succ($data);
    }
    

    /**
     * 获取专题下的头条
     */
    public function getTopicHeadLines($topicId) {
        $topicInfo = $this->getHeadLineTopics(array($topicId))['data'];
        $topicInfo = $topicInfo[$topicId];
        if (empty($topicInfo)) {
            return $this->succ(array('headline_list' => array(), 'headline_topic' => array()));
        }
        $subjectIds = $topicInfo['subject_ids'];
        $subjects = $this->subjectServer->getBatchSubjectInfos($subjectIds)['data'];
        $headLineList = array();
        foreach ($subjects as $subject) {
            $tmpData = null;
            if(empty($subject['album_article']) && empty($subject['video_info'])){
                continue;
            }
            if (!empty($subject['album_article'])) {
                $tmpData['id'] = $subject['id'] . '_album';
                $tmpData['type'] = 'album';
                $tmpData['album'] = $subject;
            } else if (!empty($subject['video_info'])) {
                $tmpData['id'] = $subject['id'] . '_video';
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
        
        if(isset($channelInfo['status'])){
            $setData[] = ['status',$channelInfo['status']];
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
        $setData['create_time'] = $headLineInfo['create_time'];
        if (!empty($headLineInfo['title'])) {
            $setData['ext_info']['title'] = $headLineInfo['title'];
        }
        if (!empty($headLineInfo['text'])) {
            $setData['ext_info']['text'] = $headLineInfo['text'];
        }
        if (!empty($headLineInfo['cover_image'])) {
            $setData['ext_info']['cover_image'] = $headLineInfo['cover_image'];
        }
        $data = $this->headLineModel->addOperateHeadLine($setData);
        return $this->succ($data);
    }
    
    /**
     * 编辑运营头条
     */
    public function editOperateHeadLine($id, $headLineInfo) {
        if (empty($id)) {
            return $this->error(500);
        }
        $headline = $this->headLineModel->getHeadLineById($id);
        if (empty($headline)) {
            return $this->error(500);
        }
        
        if (!empty($headLineInfo['relation_id'])) {
            $setData[] = ['relation_id',$headLineInfo['relation_id']];
        }
        if (!empty($headLineInfo['relation_type'])) {
            $setData[] = ['relation_type',$headLineInfo['relation_type']];
        }
        if (!empty($headLineInfo['page'])) {
            $setData[] = ['page',$headLineInfo['page']];
        }
        if (!empty($headLineInfo['row'])) {
            $setData[] = ['row',$headLineInfo['row']];
        }
        if (!empty($headLineInfo['begin_time'])) {
            $setData[] = ['begin_time',$headLineInfo['begin_time']];
        }
        if (!empty($headLineInfo['end_time'])) {
            $setData[] = ['end_time',$headLineInfo['end_time']];
        }
        if (isset($headLineInfo['title'])) {
            $headLineInfo['ext_info']['title'] = $headLineInfo['title'];
        }
//         if (isset($headLineInfo['text'])) {
//             $setData['ext_info']['text'] = $headLineInfo['text'];
//         }
        if (isset($headLineInfo['cover_image'])) {
            $headLineInfo['ext_info']['cover_image'] = $headLineInfo['cover_image'];
        }
        if (is_array($headLineInfo['ext_info']) && !empty($headLineInfo['ext_info'])) {
            $setData[] = ['ext_info',json_encode($headLineInfo['ext_info'])];
        }
        $data = $this->headLineModel->editOperateHeadLine($id, $setData);
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
     * @param $field 额外字段 count
     */
    public function getHeadLineTopics($topicIds, $field = array('count'), $status = array(1)) {
        $topicRes = $this->headLineModel->getHeadLineTopics($topicIds, $status);
        //收集帖子ID
        $subjectIds = array();
        foreach ($topicRes as $topic) {

            $subjectIds = is_array($topic['subject_ids']) ? array_merge($subjectIds, $topic['subject_ids']) : $subjectIds;
        }
        //获取专题相关计数
        if (in_array('count', $field)) {
            $praiseService = new \mia\miagroup\Service\Praise();
            $subjectPraiseCounts = $praiseService->getBatchSubjectPraises($subjectIds)['data'];
            $subjectViewCounts = $this->subjectServer->getBatchSubjectViewCount($subjectIds)['data'];
        }
        //拼装专题数据
        foreach ($topicRes as $topicId => $topic) {
            if (in_array('count', $field)) {
                foreach ($topic['subject_ids'] as $subjectId) {
                    if (intval($topicRes[$topicId]['fancied_count']) > 0) {
                        $topicRes[$topicId]['fancied_count'] += (isset($subjectPraiseCounts[$subjectId]) ? $subjectPraiseCounts[$subjectId] : 0);
                    } else {
                        $topicRes[$topicId]['fancied_count'] = isset($subjectPraiseCounts[$subjectId]) ? $subjectPraiseCounts[$subjectId] : 0;
                    }
                    if (intval($topicRes[$topicId]['view_num']) > 0) {
                        $topicRes[$topicId]['view_num'] += (isset($subjectViewCounts[$subjectId]) ? intval($subjectViewCounts[$subjectId]) : 0);
                    } else {
                        $topicRes[$topicId]['view_num'] = isset($subjectViewCounts[$subjectId]) ? intval($subjectViewCounts[$subjectId]) : 0;
                    }
                }
                
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
        $topicInfo['text'] = isset($topicInfo['text']) ? $topicInfo['text'] : '';
        $setTopicData = array();
        $setTopicInfo = array('title'=>$topicInfo['title'],'text'=>$topicInfo['text'],'cover_image'=>$topicInfo['cover_image']);
        $setTopicData['topic_info'] = json_encode($setTopicInfo);
        $setTopicData['subject_ids'] = $topicInfo['subject_ids'];
        $setTopicData['status'] = 1;
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
        
        if(isset($topicInfo['status'])){
            $setTopicData[] = ['status',$topicInfo['status']];
        }
        
        if(isset($topicInfo['subject_ids'])){
            $topicInfo['subject_ids'] = json_encode($topicInfo['subject_ids']);
            $setTopicData[] = ['subject_ids',$topicInfo['subject_ids']];
        }
        
        $topicInfo['text'] = isset($topicInfo['text']) ? $topicInfo['text'] : '';
        
        if(isset($topicInfo['title']) && isset($topicInfo['cover_image'])){
            $setTopicInfo = array('title'=>$topicInfo['title'],'text'=>$topicInfo['text'],'cover_image'=>$topicInfo['cover_image']);
            $setTopicData[] = ['topic_info',json_encode($setTopicInfo)];
        }
        $editRes = $this->headLineModel->editHeadLineTopic($topicId, $setTopicData);
        return $this->succ($editRes);
    }
    
    /**
     * 删除头条专题
     */
    public function delHeadLineTopic($topicId) {
        if(empty($topicId)){
            return $this->error(500);
        }
        $delRes = $this->headLineModel->delHeadLineTopic($topicId);
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
    private function _getFormatHeadlineData(array $sortIds, array $opertionData, $baseInfo = false) {
        //收集ID
        $opertionIds = !empty($opertionData) ? array_keys($opertionData) : array();
        $datas = array_merge($sortIds, $opertionIds);
        $subjectIds = [];
        $roomIds    = [];
        $topicIds   = [];
        foreach ($datas as $key => $value) {
            list($relation_id, $relation_type) = explode('_', $value, 2);
            //帖子
            if ($relation_type == 'video' || $relation_type == 'album' || $relation_type == 'subject') {
                $subjectIds[] = $relation_id;
            //直播
            } elseif ($relation_type == 'live') {
                $roomIds[] = $relation_id;
            //专题
            } elseif ($relation_type == 'topic') {
                $topicIds[] = $relation_id;
            }
        }

        if ($baseInfo === true) { //简要信息
            $subjects = $this->subjectServer->getBatchSubjectInfos($subjectIds, 0, array('album'))['data'];
            $lives = $this->liveServer->getLiveRoomByIds($roomIds, array())['data'];
            $topics = $this->getHeadLineTopics($topicIds, array())['data'];
        } else { //列表信息
            $subjects = $this->subjectServer->getBatchSubjectInfos($subjectIds)['data'];
            $lives = $this->liveServer->getLiveRoomByIds($roomIds, array('user_info', 'live_info'))['data'];
            $topics = $this->getHeadLineTopics($topicIds, array('count'))['data'];
        }
        
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
            $relation_title = null;
            $relation_cover_image = null;
            //如果当前位置有运营数据，优先选择运营数据
            if (isset($sortedOpertionData[$row]) && !empty($sortedOpertionData[$row])) {
                $relation_id = $sortedOpertionData[$row]['relation_id'];
                $relation_type = $sortedOpertionData[$row]['relation_type'];
                $relation_title = $sortedOpertionData[$row]['ext_info']['title'];
                $relation_cover_image = $sortedOpertionData[$row]['ext_info']['cover_image'];
            } else {
                $id = array_shift($sortIds);
                list($relation_id, $relation_type) = explode('_', $id);
            }
            //将运营配置的title、cover_image替换掉原有的
            switch ($relation_type) {
                case 'album':
                    if (isset($subjects[$relation_id]) && !empty($subjects[$relation_id])) {
                        $subject = $subjects[$relation_id];
                        if (!empty($subject['album_article'])) {
                            $tmpData['id'] = $subject['id'] . '_album';
                            $tmpData['type'] = 'album';
                            $subject['album_article']['title'] = $relation_title ? $relation_title : $subject['album_article']['title'];
                            if(!empty($relation_cover_image)){
                                $subject['album_article']['cover_image'] = $relation_cover_image;
                            }
                            $tmpData['album'] = $subject;
                        }
                    }
                    break;
                case 'video':
                    if (isset($subjects[$relation_id]) && !empty($subjects[$relation_id])) {
                        $subject = $subjects[$relation_id];
                        if (!empty($subject['video_info'])) {
                            $tmpData['id'] = $subject['id'] . '_video';
                            $tmpData['type'] = 'video';
                            $subject['title'] = $relation_title ? $relation_title : $subject['title'];
                            if(!empty($relation_cover_image)){
                                $subject['video_info']['cover_image'] = $relation_cover_image['url'];
                            }
                            $tmpData['video'] = $subject;
                        }
                    }
                    break;
                case 'subject': //relation_type=subject 兼容推荐服务没有返回数据类型的问题
                    if (isset($subjects[$relation_id]) && !empty($subjects[$relation_id])) {
                        $subject = $subjects[$relation_id];
                        if (!empty($subject['album_article'])) {
                            $tmpData['id'] = $subject['id'] . '_album';
                            $tmpData['type'] = 'album';
                            $subject['album_article']['title'] = $relation_title ? $relation_title : $subject['album_article']['title'];
                            if(!empty($relation_cover_image)){
                                $subject['album_article']['cover_image'] = $relation_cover_image;
                            }
                            $tmpData['album'] = $subject;
                        } else if (!empty($subject['video_info'])) {
                            $tmpData['id'] = $subject['id'] . '_video';
                            $tmpData['type'] = 'video';
                            if(!empty($relation_cover_image)){
                                $subject['image_url'][] = $relation_cover_image;
                                $subject['small_image_url'][] = $relation_cover_image;
                            }
                            $subject['title'] = $relation_title ? $relation_title : $subject['title'];
                            $tmpData['video'] = $subject;
                        }
                    }
                    break;
                case 'live':
                    if (isset($lives[$relation_id]) && !empty($lives[$relation_id])) {
                        $live = $lives[$relation_id];
                        $live['title'] = $relation_title ? $relation_title : $live['title'];
                        if(!empty($relation_cover_image)){
                            $live['cover_image'] = $relation_cover_image;
                        }
                        $tmpData['id'] = $live['id'] . '_live';
                        $tmpData['type'] = 'live';
                        $tmpData['live'] = $live;
                    }
                    break;
                case 'topic':
                    if (isset($topics[$relation_id]) && !empty($topics[$relation_id])) {
                        $topic = $topics[$relation_id];
                        $tmpData['id'] = $relation_id . '_headline_topic';
                        $tmpData['type'] = 'headline_topic';
                        $topic['title'] = $relation_title ? $relation_title : $topic['title'];
                        if(!empty($relation_cover_image)){
                            $topic['cover_image'] = $relation_cover_image;
                        }
                        $tmpData['headline_topic'] = $topic;
                    }
                    break;
            }
            if (!empty($tmpData)) {
                $headLineList[] = $tmpData;
            } else { //如果源关联项已不存在，则删除
                if (isset($sortedOpertionData[$row])) {
                    $this->delOperateHeadLine($sortedOpertionData[$row]['id']);
                }
            }
        }
        return $headLineList;
    }
}