<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Live as LiveModel;
use mia\miagroup\Util\RongCloudUtil;

class Live extends \FS_Service {
    
    public $liveModel;
    public $rongCloud;//融云聊天室api接口
    
    
    public function __construct() {
        $this->liveModel = new LiveModel();
        $this->rongCloud = new RongCloudUtil();
    }
    
    /**
     * 创建直播
     */
    public function addLive($userId,$name,$portraitUri) {
        //校验是否有直播权限
        $roomInfo = $this->liveModel->checkLiveRoomByUserId($userId);
        if(empty($roomInfo)){
            //没有权限直播
            return $this->error(30000,'您没有直播权限!');
        }
        
        //生成视频流ID和聊天室ID
        $stream_id = $chatId = $this->_getLiveIncrId($roomInfo['id'])['data'];
        //获取七牛视频流

        //获取融云token
        $RongtokenInfo = $this->rongCloud->getToken($userId, $name, $portraitUri);
        if(!$RongtokenInfo){
            //获取token失败
            return $this->error(30000,'获取rongcloudToken失败');
        }
        //创建聊天室
        $chatRet = $this->rongCloud->chatroomCreate([$chatId=>'chatRoom'.$chatId]);
        if(!$chatRet){
            //创建失败
            return $this->error(30000,'创建聊天室失败');    
        }
        
        //新增直播记录
        $liveInfo['user_id'] = $userId;
        $liveInfo['stream_id'] = $stream_id;
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
        //返回数据
        //融云
        $data['rongcloud']['rongcloud_token'] = $RongtokenInfo;
        $data['rongcloud']['chatroom_id'] = $chatId;
        $data['live_id'] = $liveId;
        //七牛
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
        //生成回放
        $subjectService = new \mia\miagroup\Service\Subject();
        $subjectInfo = array("title" => 'zhibo', "text" => 'zhibo', "image_infos" => array(0 => array('height' => 522, 'url' => '/d1/p4/2016/06/17/89/09/8909b7a9830d432c8b338363c9fae326542443173.jpg', 'width' => 480)), "user_info" => array('username' => 'miya134****4368', 'nickname' => 'miya134****4368', 'child_birth_day' => '2015-03-11', 'user_status' => '0', 'consume_money' => '0.00', 'icon' => '', 'level' => '1', 'is_id_verified' => '2', 'is_cell_verified' => '1', 'mibean_level' => '2', 'create_date' => '2015-03-31 17:04:05', 'status' => '1', 'is_experts' => 0, 'user_id' => '1000008', 'level_number' => '1'), "active_id" => 0, "video_url" => 'video/2016/05/04/089912967ba54274ec761531a7796eb3.mp4');
        
        $subjectRes = $subjectService->issue($subjectInfo);
        if($subjectRes['code'] != 0){
            return $this->error(30000,'生成帖子失败');
        }
          
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
        $data = $this->liveModel->getRoomLive($roomId);
        return $this->succ($data);
    }
    
    /**
     * 生成直播ID
     */
    private function _getLiveIncrId($roomId) {
        $id = time() . $roomId;
        return $id;
    }
}