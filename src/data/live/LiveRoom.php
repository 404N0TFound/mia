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
    public function checkLiveRoomByUserIds($userIds,$status = array(1)) {
        $roomInfo = [];
        if(empty($userIds)){
            return array();
        }
        $where[] = ['user_id', $userIds];
        if(!empty($status)){
            $where[] = ['status', $status];
        }
        
        $data = $this->getRows($where);
        foreach ($data as $room) {
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
        $where[] = ['id', $roomId];
        $data = $this->update($setData, $where);
        return $data;
    }

    /**
     * 根据ID修改直播房间信息
     * @author jiadonghui@mia.com
     */
    public function updateRoomSettingsById($roomId, $setData) {
        if (!isset($setData['settings']) || empty($setData['settings'])) {
            return false;
        }
        $setDataNew[] = ['settings', json_encode($setData['settings'])];
        $where = array();
        $where[] = ['id', $roomId];
        $data = $this->update($setDataNew, $where);
        return $data;
    }

    /**
     * 根据获取房间ID批量获取房间信息
     * @author jiadonghui@mia.com
     */
    public function getBatchLiveRoomByIds($roomIds,$status = array(1)) {
        if (empty($roomIds)) {
            return array();
        }
        $where = array();
        $where[] = ['id', $roomIds];
        if(!empty($status)){
            $where[] = ['status', $status];
        }
        
        $data = $this->getRows($where);
        $result = array();
        if (!$data) {
            return array();
        } else {
            foreach ($data as $v) {
                $result[$v['id']] = $v;
                $v['settings'] = str_replace('http:\/\/', 'https:\/\/', strval($v['settings']));
                $settings = json_decode(strval($v['settings']), true);
                if (array_key_exists('banners', $settings)) {
                    $result[$v['id']]['banners'] = is_array($settings['banners']) ? array_values($settings['banners']) : array();
                }
                if (array_key_exists('share', $settings)) {
                    $result[$v['id']]['share'] = $settings['share'];
                }
                if (array_key_exists('redbag', $settings)) {
                    $result[$v['id']]['redbag'] = $settings['redbag'];
                }
                if (array_key_exists('is_show_gift', $settings)) {
                    $result[$v['id']]['is_show_gift'] = $settings['is_show_gift'];
                }
                if (array_key_exists('is_show_playback', $settings)) {
                    $result[$v['id']]['is_show_playback'] = $settings['is_show_playback'];
                }
                if (array_key_exists('source', $settings)) {
                    $result[$v['id']]['source'] = $settings['source'];
                }
                if (array_key_exists('title', $settings)) {
                    $result[$v['id']]['title'] = $settings['title'];
                }
                if (array_key_exists('user_num', $settings)) {
                    $result[$v['id']]['user_num'] = $settings['user_num'];
                }
                if (array_key_exists('coupon', $settings)) {
                    $result[$v['id']]['coupon'] = $settings['coupon'];
                }
                $result[$v['id']]['settings'] = $settings;
            }
            return $result;
        }
    }

    /**
     * 根据用户ID批量获取房间配置信息
     */
    public function getBatchLiveRoomByUserIds($userIds, $status = array(1))
    {
        if (empty($userIds)) {
            return array();
        }
        $where = array();
        $where[] = ['user_id', $userIds];
        if (!empty($status)) {
            $where[] = ['status', $status];
        }
        $fields = "settings,user_id,id";
        $data = $this->getRows($where, $fields);
        $result = array();
        if (!$data) {
            return array();
        } else {
            foreach ($data as $v) {
                if (isset($v['settings'])) {
                    $settings = json_decode($v['settings'], true);
                    $result[$v['user_id']]['push_time'] = $settings['push_time'] ? $settings['push_time'] : 0;
                    $result[$v['user_id']]['id'] = $v['id'];
                }
            }
            return $result;
        }
    }

    /**
     * 获取所有正在直播的房间
     * @author jiadonghui@mia.com
     */
    public function getAllLiveRoom($status = array(1)) {
        if(!empty($status)){
            $where[] = ['status', $status];
        }
        $result = $this->getRows($where);
        return $result;
    }

    /**
     * 删除直播房间
     * @param int $roomId
     * @return boolean|unknown
     */
    public function deleteLiveRoom($roomId) {
        if (empty($roomId)) {
            return false;
        }
        $where = array();
        $where[] = ['id', $roomId];
        
        $data = $this->delete($where);
        return $data;
    }
    
    /**
     * 记录直播房间最近的一次直播ID
     * @param unknown $roomId
     * @param unknown $latestLiveId
     * @return unknown
     */
    public function recordRoomLatestLive_Id($roomId,$latestLiveId){
        $where[] = ['id',$roomId];
        $setData[] = ['latest_live_id',$latestLiveId];
        $affection = $this->update($setData,$where);
        return $affection;
    }

    /**
     * 获取所有直播房间列表
     */
    public function getLiveRoomList($conditions)
    {
        if (isset($conditions['where'])) {
            $where = [];
            foreach ($conditions['where'] as $k => $v) {
                $where[] = $v;
            }
        }
        if (isset($conditions['fields'])) {
            $fields = $conditions['fields'];
        }
        if (isset($conditions['limit'])) {
            $limit = $conditions['limit'];
        }
        if (isset($conditions['offset'])) {
            $offset = $conditions['offset'];
        }
        if (isset($conditions['orderBy'])) {
            $orderBy = $conditions['orderBy'];
        }
        $data = $this->getRows($where, $fields, $limit, $offset, $orderBy);
        return $data;
    }
}