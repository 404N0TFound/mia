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
    public function getLiveInfoById($liveId) {
        $liveInfo = $this->getBatchLiveInfoByIds(array($liveId));
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
    public function getLiveList($cond, $offset = 0, $limit = 20) {
        $liveList = $this->liveData->getLiveList($cond, $offset, $limit);
        return $liveList;
    }
}