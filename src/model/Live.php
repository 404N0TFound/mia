<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Live\Live as LiveData;
use mia\miagroup\Data\Live\LiveRoom as LiveRoomData;
use mia\miagroup\Data\Live\ChatHistory as LiveChatHistoryData;
use mia\miagroup\Lib\Redis;
use mia\miagroup\Util\NormalUtil;
class Live {
    
    public $liveData;
    public $liveRoomData;
    
    public function __construct() {
        $this->liveData = new LiveData();
        $this->liveRoomData = new LiveRoomData();
        $this->liveChatHistoryData = new LiveChatHistoryData();
    }
    
    /**
     * 获取房间的信息
     */
    public function getRoomInfoByRoomId($roomId) {
        //获取房间数据
        $roomData = $this->liveRoomData->getBatchLiveRoomByIds($roomId)[$roomId];
        return $roomData;
    }
    
    /**
     * 新增直播
     */
    public function addLive($liveInfo) {
        $data = $this->liveData->addLive($liveInfo);
        return $data;
    }
    
    /**
     * 更新直播
     */
    public function updateLiveById($liveId, $liveInfo) {
        $data = $this->liveData->updateLiveById($liveId, $liveInfo);
        return $data;
    }
    
    /**
     * 获取单个直播信息
     */
    public function getLiveInfoById($liveId,$status=array()) {
        $liveInfo = $this->getBatchLiveInfoByIds(array($liveId), $status);
        $liveInfo = !empty($liveInfo[$liveId]) ? $liveInfo[$liveId] : [];
        return $liveInfo;
    }
    
    /**
     * 根据直播ID批量获取直播信息
     */
    public function getBatchLiveInfoByIds($liveIds,$status=array(3)) {
        if (empty($liveIds)) {
            return array();
        }
        $data = $this->liveData->getBatchLiveInfoByIds($liveIds,$status);
        return $data;
    }
    
    /**
     * 根据usreId获取用户的直播信息
     * @param unknown $userId
     * @param unknown $status
     */ 
    public function getLiveInfoByUserId($userId,$status=[3]){
        $data = $this->liveData->getLiveInfoByUserId($userId,$status);
        return $data;
    }
    
    /**
     * 根据userId更新直播状态
     */
    public function updateLiveByUserId($userId,$status){
        $data = $this->liveData->updateLiveByUserId($userId, $status);
        return $data;
    }
    
    
  
    /**
     * 检测用户是否有直播权限
     * @param $userId
     */
    public function checkLiveRoomByUserId($userId){
        $data = $this->liveRoomData->checkLiveRoomByUserIds($userId)[$userId];
        return $data;
    }
    
    /**
     * 批量检测用户是否有直播权限
     * @param $userIds
     */
    public function checkLiveRoomByUserIds($userIds){
        $data = $this->liveRoomData->checkLiveRoomByUserIds($userIds);
        return $data;
    }
    
    /**
     * 根据ID修改直播房间信息
     */
    public function updateLiveRoomById($roomId, $setData) {
        $data = $this->liveRoomData->updateLiveRoomById($roomId, $setData);
        return $data;
    }
    
    /**
     * 根据ID修改直播房间信息
     * @
     */
    public function updateRoomSettingsById($roomId, $setData) {
    	$data = $this->liveRoomData->updateRoomSettingsById($roomId, $setData);
    	return $data;
    }
    
    /**
     * 根据获取房间ID批量获取房间信息
     * @author jiadonghui@mia.com
     */
    public function getBatchLiveRoomByIds($roomIds) {
        if (empty($roomIds)) {
            return array();
        }
        $rooms = $this->liveRoomData->getBatchLiveRoomByIds($roomIds);
        return $rooms;
    }
    
    /**
     * 获取直播列表
     */
    public function getLiveList($cond, $offset = 0, $limit = 20, $orderBy='') {
        $liveList = $this->liveData->getLiveList($cond, $offset, $limit,$orderBy);
        return $liveList;
    }
    
    /**
     * 新增直播房间
     */
    public function addLiveRoom($liveRoomInfo) {
        $data = $this->liveRoomData->addLiveRoom($liveRoomInfo);
        return $data;
    }

    /**
     * 删除直播房间
     * @author jiadonghui@mia.com
     */
    public function deleteLiveRoom($roomId){
        $data = $this->liveRoomData->deleteLiveRoom($roomId);
        return $data;
    }

    /**
     * 新增多条消息历史记录
     */
    public function addChatHistories($chatHistories)
    {
        $data = $this->liveChatHistoryData->addChatHistories($chatHistories);
        return $data;
    }
    
    /**
     * 根据msgUID获取历史消息记录
     */
    public function getChatHistoryByMsgUID($msgUID)
    {
        $data = $this->liveChatHistoryData->getChatHistoryByMsgUID($msgUID);
        return $data;
    }

    /**
     * 记录待转成视频帖子的直播回放
     */
    public function addLiveToVideo($liveId) {
        $key = \F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_to_video_list.key');
        $redis = new Redis();
        $redis->lpush($key, $liveId);
        return true;
    }

    /**
     * 历史消息列表
     *
     */
    public function getChathistoryList($cond, $offset = 0, $limit = 20 ,$orderBy='')
    {
        $data = $this->liveChatHistoryData->getChathistoryList($cond,$offset,$limit,$orderBy);
        return $data;
    }
    
    /**
     * 记录直播房间最近的一次直播ID
     * @param unknown $roomId
     * @param unknown $latestLiveId
     * @return int
     */
    public function recordRoomLatestLive_Id($roomId, $latestLiveId){
        $data = $this->liveRoomData->recordRoomLatestLive_Id($roomId, $latestLiveId);
        return $data;
    }

    /**
     * 把融云的UserId放入缓存中
     *
     **/
    public function addRongUserId($userId,$deviceToken)
    {
        $rongCloudUserId = $userId.','.$deviceToken;

        $redis = new Redis();
        $rongHashKey = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_rong_cloud_user_hash.key'), $userId);
        if($redis->exists($rongHashKey)){
            $redis->expire($rongHashKey,NormalUtil::getConfig('busconf.rediskey.liveKey.live_rong_cloud_user_hash.expire_time'));
        }
        $redis->hsetnx($rongHashKey,$deviceToken,$rongCloudUserId);
        return true;
    }

    /**
     * 添加主播Id到缓存
     *
     * @return void
     * @author 
     **/
    public function addHostLiveUserId($userId,$deviceToken)
    {
        $rongCloudUserId = $userId.','.$deviceToken;

        $redis = new Redis();
        $liveRongCloudUserKey = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_rong_cloud_user_id.key'), $userId);
        $expire_time          = NormalUtil::getConfig('busconf.rediskey.liveKey.live_rong_cloud_user_id.expire_time');
        $redis                = new Redis();
        $redis->setex($liveRongCloudUserKey,$rongCloudUserId,$expire_time);
        return true;
    }

    /**
     * 删除与userId有关的缓存
     *
     * @return void
     * @author 
     **/
    public function delByUserId($userId)
    {
        $redis = new Redis();
        $redis->del(sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_rong_cloud_user_id.key'), $userId));
        $redis->del(sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_rong_cloud_user_hash.key'), $userId));
        return true;
    }

    /**
     * 通过userId获取主播融云userId
     *
     * @return void
     * @author 
     **/
    public function getRongHostUserId($userId)
    {
        $redis = new Redis();
        // 主播key
        $hostKey = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_rong_cloud_user_id.key'), $userId);
        $hostStatus = $redis->exists($hostKey);
        if(!$hostStatus){
            return false;
        }
        $rongCloudUid = $redis->get($hostKey);
        return $rongCloudUid ? $rongCloudUid : false;
    }

    /**
     * 根据userId获取融云用户ID
     *
     * @return void
     * @author 
     **/
    public function getRongCloudUidsByUserId($userId)
    {
        $redis = new Redis();
        $rongHashKey = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_rong_cloud_user_hash.key'), $userId);
        $keyStatus = $redis->exists($rongHashKey);
        if(!$keyStatus){
            return [];
        }
        $deviceToken = $redis->hkeys($rongHashKey);
        $rongCloudUids = [];
        foreach ($deviceToken as $field) {
            if(!$redis->hexists($rongHashKey,$field)){
                continue;
            }
            $rongCloudUids[] = $redis->hget($rongHashKey,$field);
        }
        return $rongCloudUids;
    }

    /**
     * 获取直播流状态
     *
     * @return void
     * @author 
     **/
    public function getStreamStatusByStreamId($streamId)
    {
        $redis = new Redis();
        $liveStatusKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_status.key'), $streamId);
        $exists = $redis->exists($liveStatusKey);
        if(!$exists){
            return false;
        }

        $liveStreamStatus = $redis->get($liveStatusKey);
        return $liveStreamStatus;
    }

    /**
     * 添加直播流状态
     *
     * @return void
     * @author 
     **/
    public function addStreamStatus($streamId)
    {
        $liveStatusKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_status.key'), $streamId);
        $redis = new Redis();
        $redis->setex($liveStatusKey, time(), \F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_status.expire_time'));

        return true;
    }

    /**
     * 批量获取直播计数
     */
    public function getLiveCountByIds($liveIds) {
        if (empty($liveIds)) {
            return array();
        }
        $liveInfos = $this->liveData->getBatchLiveInfoByIds($liveIds, array(3, 4));
        $redis = new Redis();
        $liveCounts = array();
        foreach ($liveIds as $liveId) {
            //当前在线数
            $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_audience_online_num.key'), $liveId);
            $liveCounts[$liveId]['online_num'] = intval($redis->get($key));
            //累计观看人数
            if ($liveInfos[$liveId]['status'] == 3) {
                $liveCounts[$liveId]['audience_num'] = $liveCounts[$liveId]['online_num'];
            } else {
                $liveCounts[$liveId]['audience_num'] = $liveInfos[$liveId]['audience_num'] * 3 + $liveInfos[$liveId]['audience_top_num'];
            }
            
        }
        return $liveCounts;
    }
    
    /**
     * 设置直播计数
     * @param $countType online_num 当前在线数, audience_num 累计观看数，audience_top_num 最高在线数，like_num 赞数， comment_num 评论数
     */
    public function setLiveCount($liveId, $countType, $count) {
        $liveCountTypes = \F_Ice::$ins->workApp->config->get('busconf.live.liveCountType');
        if (!in_array($countType, $liveCountTypes)) {
            return false;
        }
        $redis = new Redis();
        switch ($countType) {
            case 'online_num':
                $audience_num_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_audience_online_num.key'), $liveId);
                $redis->set($audience_num_key, $count);
                break;
            default:
                $setData[] = [$countType, $count];
                $this->updateLiveById($liveId, $setData);
                break;
        }
        return true;
    }
    
    /**
     * 增加直播计数
     * @param $countType online_num 当前在线数, audience_num 累计观看数，audience_top_num 最高在线数，like_num 赞数， comment_num 评论数
     */
    public function increaseLiveCount($liveId, $countType, $increaseNum = 1) {
        $liveCountTypes = \F_Ice::$ins->workApp->config->get('busconf.live.liveCountType');
        if (!in_array($countType, $liveCountTypes)) {
            return false;
        }
        $redis = new Redis();
        $readNumKey = \F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_count_record.key');
        $data = json_encode(['live_id' => $liveId, 'type' => $countType, 'num' => intval($increaseNum)]);
        $redis->lpush($readNumKey, $data);
        return true;
    }

    /**
     * 读取直播队列计数
     * @param int $num 获取队列中的条数
     */
    public function getLiveCountRecord($num = 2000) {
        $readNumKey = \F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_count_record.key');
        $redis = new Redis();
        $len = intval($redis->llen($readNumKey));
        if ($len < $num) {
            $num = $len;
        }
        $result = [];
        for ($i = 0; $i < $num; $i ++) {
            $data = json_decode($redis->rpop($readNumKey),true);
            $result[$data['live_id']][$data['type']] += intval($data['num']);
        }
        return $result;
    }

    /**
     * 获取有过直播记录的房间列表
     */
    public function getLiveRoomList($page = 1, $limit = 100, $fields = "id,user_id,latest_live_id,settings")
    {
        $cond['where'][] = ['status', 1];
        $cond['where'][] = [':and', [':notnull', 'latest_live_id']];

        $cond['offset'] = ($page - 1) * $limit;
        $cond['limit'] = $limit;
        $cond['fields'] = $fields;
        $cond['orderBy'] = "latest_live_id DESC";

        $data = $this->liveRoomData->getLiveRoomList($cond);
        $resData = [];
        foreach ($data as $v) {
            $resData[$v['user_id']] = $v;
        }
        return $resData;
    }

    /**
     * 获取有过直播记录的房间数
     */
    public function getLiveRoomNum()
    {
        $cond['where'][] = ['status', 1];
        $cond['where'][] = [':and', [':notnull', 'latest_live_id']];

        $cond['fields'] = 'count(id) as total';

        $data = $this->liveRoomData->getLiveRoomList($cond);
        return intval($data[0]['total']);
    }

    /**
     * 更新live表
     */
    public function updateLiveInfo($where,$setData)
    {
        $data = $this->liveData->updateLive($where, $setData);
        return $data;
    }
}