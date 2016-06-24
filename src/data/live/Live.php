<?php
namespace mia\miagroup\Data\Live;

use Ice;

class Live extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_live';

    protected $mapping = array();
    
    /**
     * 新增直播
     */
    public function addLive($liveData) {
        $data = $this->insert($liveData);
        return $data;
    }
    
    /**
     * 根据ID修改直播信息
     */
    public function updateLiveById($liveId, $setData) {
        $where[] = ['id',$liveId];
        $data = $this->update($setData,$where);
        return $data;
    }
    
    /**
     * 根据ID批量获取视频信息
     * status 状态 (1创建中 2确认中 3直播中 4结束(有回放) 5结束(无回放) 6禁用 7失败)
     */
    public function getBatchLiveInfoByIds($liveIds, $status = array(3)) {
        $result = [];
        
        $where[] = ['id', $liveIds];
        if (!empty($status)) {
            $where[] = ['status', $status];
        }
        $data = $this->getRows($where);
        
        if(!$data){
            return false;
        }else{
            foreach($data as $v){
                $result[$v['id']] = $v;
            }
            return $result;
        }
    }
    
    /**
     * 获取直播列表
     * index：user_id subject_id start_time create_time
     */
    public function getLiveList($cond, $offset = 0, $limit = 100) {
        if (empty($cond['user_id']) && empty($cond['subject_id'] && empty($cond['start_time'] && empty($cond['create_time'])))) {
            //不用索引返回false
            return false;
        }
        
        foreach($cond as $k=>$v){
            $where[] = [$k,$v];
        }
        $orderBy = '';//暂定
        $data = $this->geRows($where,'*',$limit,$offset,$orderBy);
        return $data;
    }
    
    /**
     * 根据usreId获取用户的直播信息
     */
    public function getLiveInfoByUserId($userId,$status=[3]){
        $where[] = ['user_id',$userId];
        if (!empty($status)) {
            $where[] = ['status',$status];
        }
        
        return $this->getRows($where);
    }
    
    /**
     * 根据userId更新直播状态
     */
    public function updateLiveByUserId($userId,$status){
        $setData[] = ['status',$status];
        $where[] = ['user_id',$userId];
        
        $data = $this->update($setData,$where);
        return $data;
    }
    
    
    
}