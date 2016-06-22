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
        $data = $this->insert($roomInfo);
        return $data;
    }
    
    /**
     * 检测房间是否存在
     * @param $userId 
     */
    public function checkLiveRoomByUserId($userId){
        $where[] = ['user_id',$userId];
        $where[] = ['status',1];
        $data = $this->getRow($where);
        return $data;
    }
    
    /**
     * 根据ID修改直播房间信息
     * @author jiadonghui@mia.com
     */
    public function updateLiveRoomById($roomId, $setData) {
        if (!isset($setData['setting']) || empty($setData['setting'])){
            return false;
        }
        
        $setData['settings'] = json_encode($setData['settings']);
        $where = array();
        $where[] = array(':in', 'id', $roomId);

        $data = $this->update($setData,$where);
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
        $where = array();
        $where[] = array(':in', 'id', $roomIds);
        $where[] = array(':eq', 'status', '1');
        
        $data = $this->getRows($where);
        $result = array();
        if(!$data){
            return array();
        }else{
            foreach($data as $v){
                $result[$v['id']] = $v;
            }
            return $result;
        }
    }
    
}