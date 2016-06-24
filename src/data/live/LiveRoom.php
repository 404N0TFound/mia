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
     * 批量检测用户是否有权限直播
     * 有房间的用户就有权限
     * @param $userId 
     */
    public function checkLiveRoomByUserIds($userIds){
        $roomInfo = [];
        
        $where[] = ['user_id',$userIds];
        $where[] = ['status',1];
        $data = $this->getRows($where);
        foreach($data as $room){
            $roomInfo[$room['user_id']] = $room;
        }
        return $roomInfo;
    }
    
    /**
     * 根据ID修改直播房间信息
     * @author jiadonghui@mia.com
     */
    public function updateLiveRoomById($roomId, $setData) {
    	$where = array();
    	$where[] = array('id', $roomId);
    	$data = $this->update($setData,$where);
    	return $data;
    }
    
    /**
     * 根据ID修改直播房间信息
     * @author jiadonghui@mia.com
     */
    public function updateRoomSettingsById($roomId, $setData) {
        if (!isset($setData['setting']) || empty($setData['setting'])){
            return false;
        }
//         $setData['settings'] = json_encode($setData['settings']);
        $where = array();
        $where[] = array('id', $roomId);

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
                
                if(isset($v['settings'])){
                	$settings = json_decode($v['settings'],true);
                	$result[$v['id']]['custom'] = $settings['custom'];
                	$result[$v['id']]['share'] = $settings['share'];
                	$result[$v['id']]['redbag'] = $settings['redbag'];
                	$result[$v['id']]['is_show_gift'] = $settings['is_show_gift'];
                	unset($v['settings']);
                }
            }
            return $result;
        }
    }
    
}