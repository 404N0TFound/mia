<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Live as LiveModel;
use mia\miagroup\Service\User;
use mia\miagroup\Util\RongCloudUtil;
use mia\miagroup\Util\NormalUtil;
use mia\miagroup\Util\QiniuUtil;
use mia\miagroup\Util\JinShanCloudUtil;
use mia\miagroup\Lib\Redis;
use mia\miagroup\Service\Redbag;

class Live extends \mia\miagroup\Lib\Service {
    
    public $liveModel;
    public $rongCloud;//融云聊天室api接口
    private $deviceToken;
    
    public function __construct() {
        parent::__construct();
        $this->liveModel = new LiveModel();
        $this->rongCloud = new RongCloudUtil();
        $this->deviceToken = md5($this->ext_params['device_token']);
    }
    
    /**
     * 获取融云的token
     */
    public function getRongCloudToken($userId){
        //获取$name,$portratiuri
        $userService = new User();

        $userInfo = $userService->getUserInfoByUids([$userId])['data'][$userId];
        
        if(empty($userInfo)){
            //获取用户信息失败
            return $this->error(31000);
        }

        $rongCloudUserId = $userId.','.$this->deviceToken;
        $token = $this->rongCloud->getToken($rongCloudUserId, $userInfo['nickname'], $userInfo['icon']);
        if(!$token){
            //获取rongcloudToken失败
            return $this->error(31000);
        }
        $userInfo['user_id'] = $rongCloudUserId;
        $data['user_info'] = $userInfo;
        $data['token'] = $token;

        //把融云的用户ID存入缓存中
        $this->liveModel->addRongUserId($userId,$this->deviceToken);
        return $this->succ($data);
    }
    
    /**
     * 创建直播
     * @param $userId 用户id
     * @param $isLast 是否获取用户最近一次的推流信息
     * @return json
     */
    public function addLive($userId, $isLast=0,$source=1) {
        //校验是否有直播权限
        $roomInfo = $this->liveModel->checkLiveRoomByUserId($userId);
        if(empty($roomInfo)){
            //没有直播权限
            return $this->error(30000);
        }
        //判断用户是否已经存在直播
        $makeLive = [];
        $checkLiveExist = $this->liveModel->getLiveInfoByUserId($userId, [1, 2, 3]);
        if(!empty($checkLiveExist)){
            foreach ($checkLiveExist as $live) {
                switch ($live['status']) {
                    case 1: //非直播中设为失败
                    case 2:
                        $setData[]=['status',7];
                        $this->liveModel->updateLiveById($live['id'], $setData);
                        break;
                    case 3: //直播中设为结束有回放
                        $makeLive = $live;//保存正在直播的直播信息
                        if(!$isLast){
                            $this->endLive($userId, $roomInfo['id'], $roomInfo['live_id'], $roomInfo['chat_room_id']);
                        }
                        break;
                        
                }
            }
        }

        $liveCloud = $source==1 ? new QiniuUtil() : new JinShanCloudUtil();

        //继续上次直播
        if($isLast){
            //不存在正在进行的直播
            if(empty($makeLive)){
                //记录了上一次的直播ID
                if(!empty($roomInfo['latest_live_id'])){
                    //获取上一次的推流的ID
                    $latest_live_info = $this->liveModel->getLiveInfoById($roomInfo['latest_live_id']);
                    $streamId = $latest_live_info['stream_id'];
                    $streamInfo = $liveCloud->getStreamInfoByStreamId($streamId);
                    $chatId = $latest_live_info['chat_room_id'];
                    //设置直播状态
                    $setDataLate[] = ['status', 1];//创建中
                    $this->liveModel->updateLiveById($roomInfo['latest_live_id'],$setDataLate);
                    //更新直播房间数据
                    $setRoomData[] = ['live_id',$roomInfo['latest_live_id']];
                }else{
                    //生成视频流ID和聊天室ID
                    $streamId = $chatId = $this->_getLiveIncrId($roomInfo['id'])['data'];
                    $streamInfo = $liveCloud->createStream($streamId);
                    //新增直播记录
                    $liveInfo['user_id'] = $userId;
                    $liveInfo['stream_id'] = $streamInfo['id'];
                    $liveInfo['chat_room_id'] = $chatId;
                    $liveInfo['status'] = 1;//创建中
                    $liveInfo['source'] = $source;
                    $liveInfo['create_time'] = date('Y-m-d H:i:s');
                    $liveId = $this->liveModel->addLive($liveInfo);
                    //更新直播房间数据
                    $setRoomData[] = ['live_id',$liveId];
                }
            }else{
                $latest_live_info = $this->liveModel->getLiveInfoById($makeLive['id']);
                $streamId = $latest_live_info['stream_id'];
                $streamInfo = $liveCloud->getStreamInfoByStreamId($streamId);
                $chatId = $latest_live_info['chat_room_id'];
                //设置直播状态
                $setDataLate[] = ['status', 1];//创建中
                $this->liveModel->updateLiveById($makeLive['id'],$setDataLate);
                //更新直播房间数据
                $setRoomData[] = ['live_id',$makeLive['id']];
            }
        }else{
            //生成视频流ID和聊天室ID
            $streamId = $chatId = $this->_getLiveIncrId($roomInfo['id'])['data'];
            $streamInfo = $liveCloud->createStream($streamId);
            
            //新增直播记录
            $liveInfo['user_id'] = $userId;
            $liveInfo['stream_id'] = $streamInfo['id'];
            $liveInfo['chat_room_id'] = $chatId;
            $liveInfo['status'] = 1;//创建中
            $liveInfo['source'] = $source;//创建中
            $liveInfo['create_time'] = date('Y-m-d H:i:s');
            $liveId = $this->liveModel->addLive($liveInfo);
            //更新直播房间数据
            $setRoomData[] = ['live_id',$liveId];
        }
        //更新直播房间
        $setRoomData[] = ['chat_room_id',$chatId];
        $this->liveModel->updateLiveRoomById($roomInfo['id'], $setRoomData);
        
        //获取七牛的流信息失败
        if(empty($streamInfo)){
            return $this->error(30002);
        }
        //创建聊天室
        $chatRet = $this->rongCloud->chatroomCreate([$chatId=>'chatRoom'.$chatId]);
        if(!$chatRet){
            //创建聊天室失败
            return $this->error(30001);
        }
        //获取房间信息，查主库
        $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);
        $roomData = $this->getRoomLiveById($roomInfo['id'],$userId)['data'];
        \DB_Query::switchCluster($preNode);
        
        //让蜜芽兔加入聊天室
        $join_result = $this->rongCloud->joinChatRoom([3782852], $chatId);
        if(!$join_result){
            //加入聊天室失败
            return $this->error(30001);
        }
        //返回数据
        if($source==1){
            $data['qiniu_stream_info'] = json_encode($streamInfo);
        }elseif ($source==2) {
            $data['jinshan_stream_info'] = json_encode($streamInfo);
        }
        
        $data['room_info'] = $roomData;

        //创建直播时把主播user_id存入缓存
        $this->liveModel->addHostLiveUserId($userId,$this->deviceToken);

        return $this->succ($data);
    }
    
    /**
     * 开始直播
     */
    public function startLive($liveId) {
        //获取直播信息
        $live_info = $this->liveModel->getLiveInfoById($liveId);
        //更新直播状态
        $setData[]=['status',3];//直播中
        if($live_info['start_time'] == '0000-00-00 00:00:00' || empty($live_info['start_time'])){
            $setData[]=['start_time',date('Y-m-d H:i:s')];
        }
        $data = $this->liveModel->updateLiveById($liveId,$setData);
        return $this->succ($data);
    }
    
    /**
     * 结束直播
     */
    public function endLive($uid, $roomId, $liveId, $chatRoomId) {
        //断开聊天室
        $destroyInfo = $this->rongCloud->chatroomDestroy($chatRoomId);
        if(!$destroyInfo){
            //销毁聊天室失败
            return $this->error(30001);
        }
        //更新结束状态
        $setData[] = ['status', 4];//结束直播
        $setData[] = ['end_time',date('Y-m-d H:i:s')];
        $data = $this->liveModel->updateLiveById($liveId,$setData);
        if(!$data){
            //更新房间信息失败 +日志
        }
        
        //更新直播房间
        $roomSetData[] = ['live_id',''];
        $roomSetData[] = ['chat_room_id',''];
        $setRoomRes = $this->liveModel->updateLiveRoomById($roomId, $roomSetData);
        if(!$setRoomRes){
            //更新直播房间信息失败 + 日志
        }
        //更新latest_live_id
        $this->liveModel->recordRoomLatestLive_Id($roomId, $liveId);
        //发送结束直播消息
        $content = NormalUtil::getMessageBody(9,$chatRoomId);
        $this->rongCloud->messageChatroomPublish(NormalUtil::getConfig('busconf.rongcloud.fromUserId'), $chatRoomId, NormalUtil::getConfig('busconf.rongcloud.objectNameHigh'), $content);
        //结束直播的时候删除与主播有关的缓存
        $this->liveModel->delByUserId($uid);
        return $this->succ($setRoomRes);
    }
    
    /**
     * 记录待转成视频帖子的直播回放
     */
    public function addLiveToVideo($liveId) {
        $liveInfo = $this->liveModel->getLiveInfoById($liveId);
        if ($liveInfo['status'] != 4 || $liveInfo['subject_id'] > 0) {
            return $this->error(30007);
        }
        $result = $this->liveModel->addLiveToVideo($liveId);
        return $this->succ($result);
    }
    
    /**
     * 获取房间当前直播的信息
     */
    public function getRoomLiveById($roomId, $currentUid, $liveId = 0) {
        //获取房间信息
        $roomData = $this->getLiveRoomByIds([$roomId], $currentUid, array('user_info', 'live_info', 'share_info', 'settings', 'redbag'))['data'][$roomId];
        if(empty($roomData)){
            //没有直播房间信息
            return $this->error(30003);
        }

        $roomData['share_icon'] = '分享抽大奖'; //分享得好礼
        $roomData['sale_display'] = '0';
        $roomData['online_display'] = '1';
        //主播自己获取的share_info
        if($currentUid == $roomData['user_id'] && empty($roomData['settings']['share']['title'])) {
        	$liveConfig = \F_Ice::$ins->workApp->config->get('busconf.live');
            $share = $liveConfig['liveShare'];
            $liveShare = $liveConfig['liveShareInfo']['live_by_anchor'];
            //如果没有直播信息的话就去默认分享文案
            $shareTitle = !empty($roomData['share']['title']) ? $roomData['share']['title'] : $liveShare['title'];
            $shareDesc = !empty($roomData['share']['desc']) ? $roomData['share']['desc'] : $liveShare['desc'];
            $shareImage = !empty($roomData['share']['image_url']) ? $roomData['share']['image_url'] : $roomData['user_info']['icon'];
            // 替换搜索关联数组
            $replace = array(
                '{|title|}' => $shareTitle,
                '{|desc|}' => $shareDesc,
                '{|image_url|}' => $shareImage,
                '{|wap_url|}' => sprintf($liveShare['wap_url'], $roomData['id'], $roomData['live_id']), 
            );
            // 进行替换操作
            foreach ($share as $keys => $sh) {
                $share[$keys] = NormalUtil::buildGroupShare($sh, $replace);
                unset($share[$keys]['extend_text']);
            }
            $roomData['share_info'] = array_values($share);
        }
        // 获取红包信息
        if (intval($roomData['redbag']['id']) > 0) {
            $redbagService = new Redbag();
            $redBagStatus = $redbagService->checkRedbagAvailable($roomData['redbag']['id']);
            if ($redBagStatus['code'] > 0) {
                //如果红包失效，不显示红包
                unset($roomData['redbag']);
            } else {
                $splitStatus = $redbagService->getSplitStatus($roomData['redbag']['id'])['data'];
                if ($roomData['user_id'] == $currentUid && $splitStatus) {
                    //如果红包已发放过，不显示红包
                    unset($roomData['redbag']);
                } else {
                    $redbagNums = $redbagService->getRedbagNums($roomData['redbag']['id'])['data'];
                    $roomData['redbag']['nums'] = $redbagNums;
                    $redbagReceived = $redbagService->isReceivedRedbag($roomData['redbag']['id'], $currentUid)['data'];
                    $roomData['redbag']['is_received'] = $redbagReceived ? 1 : 0;
                }
            }
        }

        $qiniu = new QiniuUtil();
        $jinshan = new JinShanCloudUtil();
        if(empty($liveId)){
            //直播结束时
            if(empty($roomData['live_id'])){
                //存在最近的一次直播
                if(!empty($roomData['latest_live_id'])){
                    $liveInfo = $this->getBatchLiveInfoByIds(array($roomData['latest_live_id']), array(3, 4))['data'];
                    if (!empty($liveInfo[$roomData['latest_live_id']])) {
                        $liveInfo = $liveInfo[$roomData['latest_live_id']];
                        // 快照
                        $liveCloud = $liveInfo['source']==1 ? $qiniu : $jinshan;
                        $roomData['snapshot'] = $liveCloud->getSnapShot($liveInfo['stream_id']);
                        //回放地址
                        $roomData['play_back_hls_url'] = $liveInfo['play_back_hls_url'];
                        //如果设置了可以观看回放才可以观看回放
                        if(isset($roomData['is_show_playback']) && $roomData['is_show_playback'] === '0'){
                            $roomData['status'] = 0;//不能看回放
                        }else{
                            //直接播放回放地址
                            $roomData['status'] = 2;
                        }
                    }
                }
            }else{
                $liveInfo = $this->getBatchLiveInfoByIds(array($roomData['live_id']), array(3, 4))['data'];
                if (!empty($liveInfo[$roomData['live_id']])) {
                    $liveInfo = $liveInfo[$roomData['live_id']];
                    // 快照
                    $liveCloud = $liveInfo['source']==1 ? $qiniu : $jinshan;
                    $roomData['snapshot'] = $liveCloud->getSnapShot($liveInfo['stream_id']);
                    //回放地址
                    $roomData['play_back_hls_url'] = $liveInfo['play_back_hls_url'];
                }
            }
        }else if (intval($liveId) > 0) {
            // 获取快照和回放地址
            $liveInfo = $this->getBatchLiveInfoByIds(array($liveId), array(3, 4))['data'];
            if (!empty($liveInfo[$liveId])) {
                $liveInfo = $liveInfo[$liveId];
                // 快照
                $liveCloud = $liveInfo['source']==1 ? $qiniu : $jinshan;
                $roomData['snapshot'] = $liveCloud->getSnapShot($liveInfo['stream_id']);
                //回放地址
                $roomData['play_back_hls_url'] = $liveInfo['play_back_hls_url'];
            }
        }
        return $this->succ($roomData);
    }
    
    /**
     * 根据直播ID批量获取直播信息
     */
    public function getBatchLiveInfoByIds($liveIds,$status=array(3)) {
        if (empty($liveIds)) {
            return $this->succ(array());
        }
        $liveInfos = $this->liveModel->getBatchLiveInfoByIds($liveIds,$status);
        if (empty($liveInfos)) {
            return $this->succ(array());
        }
  
        $qiniu = new QiniuUtil();
        $jinshan = new JinShanCloudUtil();
        $redis = new Redis();
        foreach($liveInfos as $k=>$liveInfo){
            //如果是直播中的live要给url地址
            $liveCloud = $liveInfo['source']==1 ? $qiniu : $jinshan;
            if($liveInfo['status'] == 3){
                $addrInfo = $liveCloud->getLiveUrls($liveInfo['stream_id']);
                $liveInfo['hls_url'] = $addrInfo['hls'];
                $liveInfo['hdl_url'] = $addrInfo['hdl'];
                $liveInfo['rtmp_url'] = $addrInfo['rtmp'];
                
                //当前在线人数
                $audience_online_num_key = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_audience_online_num.key'),$k);
                $audience_online_num = $redis->get($audience_online_num_key);
                $liveInfo['audience_online_num'] = $audience_online_num ?: '0';
                //商品已售卖数
                $sale_num_key = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_sale_num.key'),$k);
                $sale_num = $redis->get($sale_num_key);
                $liveInfo['sale_num'] = $sale_num ?: '0';
            }
            //如果直播已结束，给回放地址
            if($liveInfo['status'] == 4 || $liveInfo['status'] == 3){
                $addrInfo = $liveCloud->getPalyBackUrls($liveInfo['stream_id']);
                $liveInfo['play_back_hls_url'] = $addrInfo['hls'];
            }
            $liveInfos[$k] = $liveInfo;
        }
        return $this->succ($liveInfos);
    } 
    
    /**
     * 生成直播ID
     */
    private function _getLiveIncrId($roomId) {
        $id = $roomId.time();
        return $this->succ($id);
    }

    /**
     * 更新直播房间设置
     * @author jiadonghui@mia.com
     */
    public function updateLiveRoomSettings($roomId, $settings = array()) {
        if (empty($roomId) || empty($settings)) {
            return $this->error(500);
        }
        
        $liveConfig = \F_Ice::$ins->workApp->config->get('busconf.live');
        $liveSetting = $liveConfig['liveSetting'];
        
        $settingItems = array_flip($liveSetting);
        $diffs = array_diff_key($settings, $settingItems);
        // 如果配置项不在设定值范围内，则报错
        if (!empty($diffs)) {
            return $this->error(500);
        }
        
        $setInfo = array('settings' => $settings);
        $updateRes = $this->liveModel->updateRoomSettingsById($roomId, $setInfo);
        
        if($updateRes){
            $roomData = $this->liveModel->getRoomInfoByRoomId($roomId);
            if(!empty($roomData['chat_room_id'])){
                //给聊天室发送更改的banners信息
                if(!empty($settings['banners'])){
                    $bannerArr = array();
                    //banner超过8个只显示8个
                    foreach($settings['banners'] as $banner){
                        if(!isset($banner['visible']) || $banner['visible'] == 1){
                            $bannerArr[] = $banner;
                        }
                    }
                    $bannerArr = (count($bannerArr) > 8) ? array_slice($bannerArr,0,8) : $bannerArr;
                    $content = NormalUtil::getMessageBody(12,$roomData['chat_room_id'],0,'',['banners'=>$bannerArr]);
                    $this->rongCloud->messageChatroomPublish(NormalUtil::getConfig('busconf.rongcloud.fromUserId'), $roomData['chat_room_id'], NormalUtil::getConfig('busconf.rongcloud.objectNameHigh'), $content);
                }
            }
            
        }
        
        return $this->succ($updateRes);
    }


    /**
     * 获取直播房间列表
     * @author jiadonghui@mia.com
     */
    public function getLiveRoomByIds($roomIds, $currentUid = 0, $field = array('user_info', 'live_info', 'share_info', 'settings')) {
    	if (empty($roomIds) || !array($roomIds)) {
             return $this->succ(array());
        }
        //批量获取房间信息
        $roomInfos = $this->liveModel->getBatchLiveRoomByIds($roomIds);
        if (empty($roomInfos)) {
            return $this->succ(array());
        }
        //获取批量userid，用于取出直播房间的主播信息
        $userIdArr = array();
        $liveIdArr = array();
        foreach ($roomInfos as $roomInfo) {
            $userIdArr[] = $roomInfo['user_id'];
            if (intval($roomInfo['live_id']) > 0) {
                $liveIdArr[] = $roomInfo['live_id'];
            }
        }
        //通过userids批量获取主播信息
        if (in_array('user_info', $field)) {
            $userIds = array_unique($userIdArr);
            $userService = new User();
            $userArr = $userService->getUserInfoByUids($userIds, $currentUid)['data'];
        }
        //通过liveids批量获取直播列表
        if (in_array('live_info', $field)) {
            $liveArr = $this->getBatchLiveInfoByIds($liveIdArr)['data'];
        }
        //将主播信息整合到房间信息中
        $roomRes = array();
        foreach($roomIds as $roomId){
            if (!empty($roomInfos[$roomId])) {
                $roomInfo = $roomInfos[$roomId];
            } else {
                continue;
            }
            $liveConfig = \F_Ice::$ins->workApp->config->get('busconf.live');
            $roomRes[$roomInfo['id']]['id'] = $roomInfo['id'];
            $roomRes[$roomInfo['id']]['live_id'] = $roomInfo['live_id'];
            $roomRes[$roomInfo['id']]['chat_room_id'] = $roomInfo['chat_room_id'];
            $roomRes[$roomInfo['id']]['settings'] = $roomInfo['settings'];
            $roomRes[$roomInfo['id']]['user_id'] = $roomInfo['user_id'];
            $roomRes[$roomInfo['id']]['subject_id'] = $roomInfo['subject_id'];
            $roomRes[$roomInfo['id']]['status'] = 0;
            $roomRes[$roomInfo['id']]['tips'] = $liveConfig['liveRoomTips']; //房间提示信息
            $roomRes[$roomInfo['id']]['latest_live_id'] = $roomInfo['latest_live_id']; //房间提示信息
            //用户信息
            if (in_array('user_info', $field)) {
                if(!empty($userArr[$roomInfo['user_id']])){
                    $roomRes[$roomInfo['id']]['user_info'] = $userArr[$roomInfo['user_id']];
                }
            }
            //直播信息
            if(in_array('live_info', $field)){
                if(!empty($liveArr[$roomInfo['live_id']]) && $liveArr[$roomInfo['live_id']]['status'] == 3){
                    $roomRes[$roomInfo['id']]['live_info'] = $liveArr[$roomInfo['live_id']];
                    $roomRes[$roomInfo['id']]['status'] = 1;
                } else {
                    $roomRes[$roomInfo['id']]['status'] = 0;
                }
            }
            if (in_array('settings', $field)) {
                $bannerArr = array();
                if(is_array($roomInfo['banners']) && !empty($roomInfo['banners'])){
                    foreach($roomInfo['banners'] as $banner){
                        if(!isset($banner['visible']) || $banner['visible'] == 1){
                            $bannerArr[] = $banner;
                        }
                    }
                }
                //如果可见banner数量大于8个，截取最新的8个
                $bannerArr = (count($bannerArr) > 8) ? array_slice($bannerArr,0,8) : $bannerArr;
                // 后台自定义的商品信息
                $roomRes[$roomInfo['id']]['banners'] = $bannerArr;
                // 是否显示分享得好礼
                $roomRes[$roomInfo['id']]['is_show_gift'] = isset($roomInfo['is_show_gift']) ? $roomInfo['is_show_gift'] : 0;
                $roomRes[$roomInfo['id']]['is_show_playback'] = isset($roomInfo['is_show_playback']) ? $roomInfo['is_show_playback'] : '1';//是否显示回放
            }
            // 红包信息
            if (in_array('redbag', $field)) {
                if (!empty($roomInfo['redbag'])) {
                    $redbagId = $roomInfo['redbag'];
                    $roomRes[$roomInfo['id']]['redbag']['id'] = $roomInfo['redbag'];
                }
            }
            
            if (in_array('share_info', $field)) {
                // 分享内容
                $share = $liveConfig['liveShare'];
                $liveShare = $liveConfig['liveShareInfo']['live_by_user'];
                //如果没有直播信息的话就去默认分享文案
                $shareTitle = !empty($roomInfo['share']['title']) ? $roomInfo['share']['title'] : sprintf($liveShare['title'], $roomRes[$roomInfo['id']]['user_info']['nickname']);
                $shareDesc = !empty($roomInfo['share']['desc']) ? $roomInfo['share']['desc'] : sprintf($liveShare['desc'], $roomRes[$roomInfo['id']]['user_info']['nickname']);
                $shareImage = !empty($roomInfo['share']['image_url']) ? $roomInfo['share']['image_url'] : $roomRes[$roomInfo['id']]['user_info']['icon'];
                // 替换搜索关联数组
                $replace = array(
                    '{|title|}' => $shareTitle,
                    '{|desc|}' => $shareDesc,
                    '{|image_url|}' => $shareImage,
                    '{|wap_url|}' => sprintf($liveShare['wap_url'], $roomInfo['id'], $roomInfo['live_id']), 
                );
                // 进行替换操作
                foreach ($share as $keys => $sh) {
                    $share[$keys] = NormalUtil::buildGroupShare($sh, $replace);
                    unset($share[$keys]['extend_text']);
                }
                $roomRes[$roomInfo['id']]['share_info'] = array_values($share);
            }
        }
        return $this->succ($roomRes);
    }
    
    
    /**
     * 检测用户是否有权限直播
     * @param $userId
     */
    public function checkLiveAuthByUserIds(array $userIds){
        $authInfo = [];
        $roomInfo = $this->liveModel->checkLiveRoomByUserIds($userIds);
        foreach($userIds as $userId){
            if(empty($roomInfo[$userId])){
                //无权限
                $authInfo[$userId] = 0;
            }else{
                //有权限
                $authInfo[$userId] = 1;
            }
        }
        return $this->succ($authInfo);
    }
    
    /**
     * 加入聊天室
     * @param unknown $userId
     * @param unknown $chatroomId
     */
    public function joinChatRoom($userId,$chatroomId){
        $rongCloudUserId = $userId.','.$this->deviceToken;
        $data = $this->rongCloud->joinChatRoom($rongCloudUserId, $chatroomId);
        return $this->succ($data);
    }

    /**
     * 领取直播红包
     */
    public function getLiveRedBag($userId, $redBagId, $roomId) {
        // 获取直播房间信息
        $liveRoomInfo = $this->getLiveRoomByIds(array($roomId), $userId, array('redbag'))['data'];
        $liveRoomInfo = $liveRoomInfo[$roomId];
        // 判断直播间是否配置了红包
        if (empty($liveRoomInfo['redbag'])) {
            return $this->error('1722');
        }
        // 判断该红包是否绑定了直播房间
        if ($liveRoomInfo['redbag']['id'] != $redBagId) {
            return $this->error('1722');
        }
        $redbagService = new Redbag();
        // 是否已领取
        $redbagReceived = $redbagService->isReceivedRedbag($redBagId, $userId)['data'];
        if ($redbagReceived) {
            return $this->error('1721');
        }
        // 领红包
        $redbagNums = $redbagService->getPersonalRedBag($userId, $redBagId);
        if ($redbagNums['code'] > 0) {
            return $this->error($redbagNums['code']);
        }
        //发送抢到红包的消息
        $userService = new User();
        $userInfo = $userService->getUserInfoByUserId($userId)['data'];
        if (!empty($userInfo)) {
            $content = NormalUtil::getMessageBody(0,$liveRoomInfo['chat_room_id'], \F_Ice::$ins->workApp->config->get('busconf.user.miaTuUid'), sprintf('恭喜%s抢到%s元红包', $userInfo['nickname'], $redbagNums['data']));
            $this->rongCloud->messageChatroomPublish(NormalUtil::getConfig('busconf.rongcloud.fromUserId'), $liveRoomInfo['chat_room_id'], NormalUtil::getConfig('busconf.rongcloud.objectNameHigh'), $content);
        }
        $redbagNums = $redbagNums['data'];
        $success = array('money' => $redbagNums . '元', 'success_msg' => '恭喜！抢到%s红包，快去买买买~');
        return $this->succ($success);
    }
    
    /**
     * 主播发送直播红包
     */
    public function sendLiveRedBag($roomId, $userId, $redBagId) {
        // 获取直播房间信息
        $liveRoomInfo = $this->getLiveRoomByIds(array($roomId), $userId, array('redbag'))['data'];
        $liveRoomInfo = $liveRoomInfo[$roomId];
        // 判断直播间是否配置了红包
        if (empty($liveRoomInfo['redbag'])) {
            return $this->error('1726');
        }
        // 判断该红包是否绑定了直播房间
        if ($liveRoomInfo['redbag']['id'] != $redBagId) {
            return $this->error('1726');
        }
        $redbagService = new Redbag();
        $splitResult = $redbagService->splitRedBag($redBagId);
        if ($splitResult['code'] > 0) {
            return $this->error($splitResult['code']);
        }
        //发送领取红包消息
        $content = NormalUtil::getMessageBody(7,$liveRoomInfo['chat_room_id'], 0, '', array('redbag_id' => $redBagId));
        $this->rongCloud->messageChatroomPublish(NormalUtil::getConfig('busconf.rongcloud.fromUserId'), $liveRoomInfo['chat_room_id'], NormalUtil::getConfig('busconf.rongcloud.objectNameHigh'), $content);
        return $this->succ();
    }
    
    /**
     * 新增直播房间
     * @author jiadonghui@mia.com
     */
    public function insertLiveRoom($userId) {
        if (empty($userId)) {
            return $this->error(500);
        }
        $info = $this->liveModel->checkLiveRoomByUserId($userId);
        if(empty($info)){
            $insertRes = $this->liveModel->addLiveRoom(['user_id' =>$userId]);
            return $this->succ($insertRes);
        }else{
            return $this->error(30003);
        }
        
    }
    
    /**
     * 向聊天室发送系统消息
     */
    public function sendSystemMessage($roomId, $message, $sendUid = 0){
        if (intval($sendUid) <= 0) {
            $sendUid = \F_Ice::$ins->workApp->config->get('busconf.user.miaTuUid');
        }
        $roomInfo = $this->liveModel->getRoomInfoByRoomId($roomId);
        if(empty($roomInfo)){
            //没有直播房间信息
            return $this->error(30003);
        }
        //发送系统消息
        $content = NormalUtil::getMessageBody(0,$roomInfo['chat_room_id'], $sendUid, $message);
        $this->rongCloud->messageChatroomPublish(NormalUtil::getConfig('busconf.rongcloud.fromUserId'), $roomInfo['chat_room_id'], NormalUtil::getConfig('busconf.rongcloud.objectNameHigh'), $content);
        return $this->succ();
    }

    /**
     * 封禁用户
     * @param $userId   用户 Id。（必传）
     * @param $minute   封禁时长,单位为分钟，最大值为43200分钟。（必传）
     * @return mixed
     */
    public function disableUser($userId,$minute)
    {
        $rongCloudUids = $this->liveModel->getRongCloudUidsByUserId($userId);
        foreach ($rongCloudUids as $rongCloudUserId) {
            $this->rongCloud->disableUser($rongCloudUserId,$minute);
        }
        return $this->succ();
    }

    /**
     * 删除直播房间
     * @author jiadonghui@mia.com
     */
    public function deleteLiveRoom($roomId) {
        if (empty($roomId)) {
            return $this->error(500);
        }
        
        $deleteRes = $this->liveModel->deleteLiveRoom($roomId);
        return $this->succ($deleteRes);
    }

    /**
     * 添加敏感词
     * @param $word 敏感词，最长不超过 32 个字符。（必传）
     * @return $mixed
     */
    public function wordfilterAdd($word)
    {
        $data = $this->rongCloud->wordfilterAdd($word);
        return $this->succ($data);
    }

    /**
     * 移除敏感词
     * @param $word 敏感词，最长不超过 32 个字符。（必传）
     * @return mixed
     */
    public function wordfilterDelete($word)
    {
        $data = $this->rongCloud->wordfilterDelete($word);
        return $this->succ($data);
    }


    /**
     * 查询敏感词列表
     * @return array
     */
    public function wordfilterList()
    {
        $data = $this->rongCloud->wordfilterList();
        return $this->succ($data);
    }

    /**
     * 判断直播是否被分享
     *
     * @return void
     * @author 
     **/
    public function liveIsShare($userId)
    {
        if (empty($userId)) {
            return $this->error(500);
        }

        $where['userId']      = array(':eq', 'userId', $userId);
        $where['contentType'] = array(':eq', 'contentType', 4);
        $data      = $this->liveModel->getChathistoryList($where,0,1);
        return $this->succ($data);
    }
    
    /**
     * 直播初始化
     */
    public function liveInit($userId){
        //判断用户是否有正在进行的直播（有则结束直播&显示是否继续直播弹层）
        $currLiveInfo = $this->liveModel->getLiveInfoByUserId($userId,[3]);
        if(!empty($currLiveInfo)){
            //显示
            $data['show_last_live'] = 1;
            $data['source'] = 2;
            return $this->succ($data);
        }
        //判断用户最近一次的直播的结束时间与当前时间是否相差60分钟（含）相差60分钟以上的则不显示是否继续直播弹层，反之不显示
        $data = $this->liveModel->checkLiveRoomByUserId($userId);
        if(empty($data['latest_live_id'])){
            $data['show_last_live'] = 0;
            $data['source'] = 2;
        }else{
            $latest_live_info = $this->liveModel->getLiveInfoById($data['latest_live_id']);
            if(time() - strtotime($latest_live_info['end_time']) > 3600){
                $data['show_last_live'] = 0;
            }else{
                $data['show_last_live'] = 1;
            }
            $data['source'] = 2;
        }
        return $this->succ($data);
    }

    /**
     * 金山直播心跳
     *
     * @return void
     * @author 
     **/
    public function heartbeat($liveId)
    {
        $liveInfo = $this->liveModel->getLiveInfoById($liveId,[3]);
        if(empty($liveInfo)){
            return $this->error(30006);
        }
        $liveStatusKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_status.key'), $liveInfo['stream_id']);
        $redis = new Redis();
        $redis->setex($liveStatusKey, time(), \F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_status.expire_time'));
        return $this->succ();
    }
}
