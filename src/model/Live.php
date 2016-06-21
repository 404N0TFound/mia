<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Live\Live as LiveData;
use mia\miagroup\Data\Live\LiveRoom as LiveRoomData;

class Live {
    
    public $liveData;
    public $liveRoomData;
    
    public function __construct() {
        $this->liveData = new LiveData();
        $this->liveRoomData = new LiveRoomData();
    }
    
    /**
     * 获取直播房间信息
     */
    public function getRoomLive($roomId) {
        //获取房间数据
        //获取当前房间直播信息
    }
    
    /**
     * 新增直播
     */
    public function addLive($liveInfo) {
        
    }
    
    /**
     * 更新直播
     */
    public function updateLiveById($liveId, $liveInfo) {
        
    }
    
    /**
     * 获取单个直播信息
     */
    public function getLiveInfoById($liveId) {
        $liveInfo = $this->getBatchLiveInfoByIds(array($liveId));
        $liveInfo = !empty($liveInfo[$liveId]) ? $liveInfo[$liveId] : null;
        return $liveInfo;
    }
    
    /**
     * 根据直播ID批量获取直播信息
     */
    public function getBatchLiveInfoByIds($liveIds) {
        if (!empty($liveIds)) {
            return array();
        }
    }
}