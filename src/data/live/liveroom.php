<?php
namespace mia\miagroup\Data\Live;

use Ice;

class LiveRoom extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_live_room';

    protected $mapping = array();
    
    /**
     * 新增直播房间
     */
    public function addLiveRoom($roomInfo) {
        
    }
    
    /**
     * 根据ID修改直播房间信息
     */
    public function updateLiveRoomById($liveId, $setData) {
    
    }
    
    /**
     * 根据获取房间ID批量获取房间信息
     */
    public function getBatchLiveRoomByIds($roomIds) {
        
    }
}