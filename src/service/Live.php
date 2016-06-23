<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Live as LiveModel;
use mia\miagroup\Service\User;
use mia\miagroup\Util\RongCloudUtil;
use mia\miagroup\Util\NormalUtil;
use mia\miagroup\Util\QiniuUtil;

class Live extends \FS_Service {
    
    public $liveModel;
    public $rongCloud;//融云聊天室api接口
    
    
    public function __construct() {
        $this->liveModel = new LiveModel();
        $this->rongCloud = new RongCloudUtil();
    }
    
    /**
     * 获取融云的token
     */
    public function getRongCloudToken($userId){
        //获取$name,$portratiuri
        $userService = new User();
        $userInfo = $userService->getUserBaseInfo($userId)['data'][$userId];
        if(empty($userInfo)){
            return $this->error(30000,'获取用户信息失败');
        }
        
        $RongtokenInfo = $this->rongCloud->getToken($userId, $userInfo['name'], $userInfo['icon']);
        if(!$RongtokenInfo){
            //获取token失败
            return $this->error(30000,'获取rongcloudToken失败');
        }
        
        $data['user_info'] = $userInfo;
        $data['token'] = $RongtokenInfo;
        
        return $this->succ($userInfo);
    }
    
    /**
     * 创建直播
     */
    public function addLive($userId) {
        //校验是否有直播权限
        $roomInfo = $this->liveModel->checkLiveRoomByUserId($userId);
        if(empty($roomInfo)){
            //没有权限直播
            return $this->error(30000,'您没有直播权限!');
        }
        //判断用户是否已经存在直播
        $checkLiveExist = $this->liveModel->getLiveInfoByUserId($userId);
        if(!empty($checkLiveExist)){
            //4是结束有回放
            $upLiveInfo = $this->liveModel->updateLiveByUserId($userId, 4);
            if(!$upLiveInfo){
                return $this->error(30000,'结束已存在直播失败');
            }
        }
        //生成视频流ID和聊天室ID
        $streamId = $chatId = $this->_getLiveIncrId($roomInfo['id'])['data'];
        //获取七牛视频流
        $qiniu = new QiniuUtil();
        $streamInfo = $qiniu->createStream($streamId);
        if(empty($streamInfo)){
            return $this->error(30000,'获取七牛的流信息失败');
        }

        //创建聊天室
        $chatRet = $this->rongCloud->chatroomCreate([$chatId=>'chatRoom'.$chatId]);
        if(!$chatRet){
            //创建失败
            return $this->error(30000,'创建聊天室失败');    
        }
        //新增直播记录
        $liveInfo['user_id'] = $userId;
        $liveInfo['stream_id'] = $streamInfo['id'];
        $liveInfo['chat_room_id'] = $chatId;
        $liveInfo['status'] = 1;//创建中
        $liveInfo['create_time'] = date('Y-m-d H:i:s');
        $liveId = $this->liveModel->addLive($liveInfo);
        //更新直播房间
        $setRoomData[] = ['live_id',$liveId];
        $setRoomData[] = ['chat_id',$chatId];
        $saveRoomInfo = $this->liveModel->updateLiveRoomById($roomInfo['id'], $setRoomData);
        if(!$saveRoomInfo){
            //更新房间信息失败
            return $this->error(30000,'更新房间信息失败');
        }  
        //获取房间信息
        $roomData = $this->getRoomLiveById([$roomInfo['id']])['data'];
        //返回数据
        $data['qiniu_stream_info'] = $streamInfo;
        $data['room_info'] = $roomData;
        
        return $this->succ($data);
    }
    
    /**
     * 开始直播
     */
    public function startLive($liveId) {
        //更新直播状态
        $setData[]=['status',3];//直播中
        $setData[]=['start_time',date('Y-m-d H:i:s')];
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
            //断开失败
            return $this->error(30000,'销毁聊天室失败');
        }
        //更新结束状态
        $setData[] = ['status', 4];//结束直播
        $setData[] = ['end_time',date('Y-m-d H:i:s')];
        $data = $this->liveModel->updateLiveById($liveId,$setData);
        if(!$data){
            return $this->error(30000,'更新状态失败');
        }
        
        //生成回放
//         $qiniu = new QiniuUtil();
//         $vedioUrl = $qiniu->getPalyBackUrls();
        
//         $subjectService = new \mia\miagroup\Service\Subject();
//         $subjectInfo = array("title" => 'zhibo', "text" => 'zhibo', "image_infos" => array(0 => array('height' => 522, 'url' => '/d1/p4/2016/06/17/89/09/8909b7a9830d432c8b338363c9fae326542443173.jpg', 'width' => 480)), "user_info" => array('username' => 'miya134****4368', 'nickname' => 'miya134****4368', 'child_birth_day' => '2015-03-11', 'user_status' => '0', 'consume_money' => '0.00', 'icon' => '', 'level' => '1', 'is_id_verified' => '2', 'is_cell_verified' => '1', 'mibean_level' => '2', 'create_date' => '2015-03-31 17:04:05', 'status' => '1', 'is_experts' => 0, 'user_id' => '1000008', 'level_number' => '1'), "active_id" => 0, "video_url" => 'video/2016/05/04/089912967ba54274ec761531a7796eb3.mp4');
        
//         $subjectRes = $subjectService->issue($subjectInfo);
//         if($subjectRes['code'] != 0){
//             return $this->error(30000,'生成帖子失败');
//         }
          
        //后台脚本处理赞数、评论、累计观众、最高在线等数据
        //更新直播房间
        $roomSetData[] = ['live_id',''];
        $roomSetData[] = ['caht_id',''];
        $setRoomRes = $this->liveModel->updateLiveRoomById($roomId, $setData);
        if(!$setRoomRes){
            return $this->error(30000,'更新直播房间信息失败');
        }
        
        return $this->succ();
    }
    
    /**
     * 获取房间当前直播
     */
    public function getRoomLiveById($roomId) {
        //获取房间信息
        $roomData = $this->getLiveRoomByIds([$roomId])['data'][$roomId];
        //获取在线人数和商品数
        return $this->succ($roomData);
    }
    
    /**
     * 根据直播ID批量获取直播信息
     */
    public function getBatchLiveInfoByIds($liveIds,$status=array(3)) {
        
        $wantLiveInfo = [];
        $liveInfos = $this->liveModel->getBatchLiveInfoByIds($liveIds,$status);
    
        $qiniu = new QiniuUtil();
        //如果是直播中的live要给url地址
        foreach($liveInfos as $k=>$liveInfo){
            if($liveInfos['status'] == 3){
                $addrInfo = $qiniu->getLiveUrls($liveInfo['stream_id']);
                $liveInfo['hls_url'] = $addrInfo['hls'];
                $liveInfo['hdl_url'] = $addrInfo['hdl'];
                $liveInfo['rtmp_rul'] = $addrInfo['rtmp'];
            }
            $wantLiveInfo[$k] = $liveInfo;
        }
        return $this->succ($wantLiveInfo);
    }
    
    /**
     * 获取在线人数和商品数
     */
    public function getOnlineNumber(){
        //在线人数:底数为1（含）-50（含）的随机数，每5s，叠加一个0（含）-20（含）的随机数，最大值14400
        
        //商品数：底数为0，每5s叠加一个1（含）-50（含）的随机数，最大值72000
        
    }
    
    
    
    /**
     * 生成直播ID
     */
    private function _getLiveIncrId($roomId) {
        $id = time() . $roomId;
        return $this->succ($id);
    }
    
    /**
     * 更新直播房间设置
     * @author jiadonghui@mia.com
     */
    public function updateLiveRoomSettings($roomId, $settings = array()) {
        if (empty($roomId) || empty($settings)) {
            $this->error();
        }
        
        $subjectConfig = \F_Ice::$ins->workApp->config->get('busconf.subject');
        $liveSetting = $subjectConfig['liveSetting'];
        
        $settingItems = $liveSetting;
        $settings = array_diff_key($settings, $settingItems);
        //如果配置项不在设定值范围内，则报错
        if(!empty($settings)){
            $this->error();
        }
        
        $setInfo = array('settings' => $settings);
        $updateRes = $this->liveModel->updateLiveRoomById($setInfo, $roomId);
        if (!$updateRes) {
            return false;
        }
        return $this->succ();
    }


    /**
     * 获取直播房间列表
     * @author jiadonghui@mia.com
     */
    public function getLiveRoomByIds($roomIds, $field = array('user_info', 'live_info', 'share_info', 'tips')) {
        if (empty($roomIds) || !array($roomIds)) {
            $this->error();
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
            $liveIdArr = $roomInfo['live_id'];
        }

        //通过userids批量获取主播信息
        $userIds = array_unique($userIdArr);
        $userService = new User();
        $userArr = $userService->getUserInfoByUids($userIds)['data'];
        //通过liveids批量获取直播列表,todo
        $liveIds = array_unique($liveIdArr);
        $liveArr = $this->getBatchLiveInfoByIds($liveIds)['data'];
        
        //将主播信息整合到房间信息中
        $roomRes = array();
        foreach($roomIds as $roomId){
            if (!empty($roomInfos[$roomId])) {
                $roomInfo = $roomInfos[$roomId];
            } else {
                continue;
            }
            $roomRes[$roomInfo['id']]['id'] = $roomInfo['id'];
            $roomRes[$roomInfo['id']]['live_id'] = $roomInfo['live_id'];
            $roomRes[$roomInfo['id']]['chat_id'] = $roomInfo['chat_id'];
            $roomRes[$roomInfo['id']]['user_id'] = $roomInfo['user_id'];
            $roomRes[$roomInfo['id']]['status'] = 0;
            //用户信息
            if (in_array('user_info', $field)) {
                if(!empty($userArr[$roomInfo['user_id']])){
                    $roomRes[$roomInfo['id']]['user_info'] = $userArr[$roomInfo['user_id']];
                }
            }
            if(in_array('live_info', $field)){
                if(!empty($liveArr[$roomInfo['live_id']])){
                    $roomRes[$roomInfo['id']]['live_info'] = $liveArr[$roomInfo['live_id']];
                    $roomRes[$roomInfo['id']]['status'] = 1;
                }
                $roomRes[$roomInfo['id']]['status'] = 0;
            }
            if (in_array('share_info', $field)) {
                // 分享内容
                $liveConfig = \F_Ice::$ins->workApp->config->get('busconf.subject');
                $share = $liveConfig['groupShare'];
                $tips = $liveConfig['liveRoomTips'];
                $shareTitle = 'title';//临时数据文案
                $shareDesc = "超过20万妈妈正在蜜芽圈热聊，快来看看~";//临时数据文案
                // 替换搜索关联数组
                $replace = array('{|title|}' => $shareTitle, '{|desc|}' => $shareDesc);
                // 进行替换操作
                foreach ($share as $keys => $sh) {
                    $share[$keys] = NormalUtil::buildGroupShare($sh, $replace);
                }
                $roomRes[$roomInfo['id']]['share_info'] = $share;
            }
            //房间提示信息
            if (in_array('tips', $field)) {
                $roomRes[$roomInfo['id']]['tips'] = $tips;
            }
        }
        return $this->succ($roomRes);
    }
    
}