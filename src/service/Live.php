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
    public function addLive($roomId, $liveInfo) {
        //生成视频流ID和聊天室ID
        $liveId = $chatId = $this->_getLiveIncrId($roomId);
        //获取七牛视频流
        //获取融云token
        $tokenInfo = $this->rongCloud->getToken($userId, $name, $portraitUri);
        //创建聊天室
        $chatRet = $this->rongCloud->chatroomCreate($data);
        if(!$chatRet){
            //创建失败
                
        }
        //新增直播记录
        //更新直播房间
        //返回数据
    }
    
    /**
     * 开始直播
     */
    public function startLive($liveId) {
        //更新直播状态
    }
    
    /**
     * 结束直播
     */
    public function endLive($uid, $liveId, $liveInfo) {
        //断开聊天室
        $this->rongCloud->chatroomDestroy($chatroomId);
        
        //更新结束状态
        //生成回放
        //后台脚本处理赞数、评论、累计观众、最高在线等数据
        //更新直播房间
    }
    
    /**
     * 获取房间当前直播
     */
    public function getRoomLiveById($roomId) {
        //获取房间信息
        //获取房间当前直播
    }
    
    /**
     * 更新直播房间设置
     */
    public function updateLiveRoomSettings($roomId, $settings = array()) {
        if (empty($roomId) || empty($settings)) {
            $this->error();
        }
        $settingItems = array('hongbao');
        $settings = array_flip($settings, $settingItems);
    }
    
    /**
     * 生成直播ID
     */
    private function _getLiveIncrId($roomId) {
        $util = new \mia\miagroup\Util\NormalUtil();
        $id = time() . $roomId;
        $id = $util->encode_uid($id);
        return $id;
    }
}