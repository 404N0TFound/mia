<?php
namespace mia\miagroup\Data\Active;

use Ice;

class Active extends \DB_Query {

    protected $dbResource = 'miagroup';
    protected $tableName = 'group_active';
    protected $tableActiveItem = 'group_active_item_tab';

    /**
     * 批量查活动信息
     */
    public function getBatchActiveInfos($page=1, $limit=20, $status = array(1), $condition = array()) {
        $offsetLimit = $page > 1 ? ($page - 1) * $limit : 0;
        if (!empty($status)) {
            $where[] = ['status',$status];
        }
        $currentTime = date('Y-m-d H:i:s',time());
        if(!empty($condition['active_ids'])){
            $where[] = ['id',$condition['active_ids']];
        }
        
        if(isset($condition['active_status']) && in_array($condition['active_status'], array(1,2,3))){
            if($condition['active_status'] ==2){//当前在线活动
                $where[] = [':le','start_time', $currentTime];
                $where[] = [':ge','end_time', $currentTime];
            }elseif($condition['active_status'] == 3){//已结束
                $where[] = [':lt','end_time', $currentTime];
            }else{
                $where[] = [':gt','start_time', $currentTime];//未开始
            }
        }

        $orderBy = 'asort desc, created desc';
        $activeArrs = $this->getRows($where,'*', $limit, $offsetLimit, $orderBy);
        
        return $activeArrs;
    }

    //创建活动
    public function addActive($insertData){
        $data = $this->insert($insertData);
        return $data;
    }
    
    //编辑活动
    public function updateActive($activeData, $activeId){
        if (empty($activeData)) {
            return false;
        }
        $where[] = ['id', $activeId];
        $setData = array();
        foreach($activeData as $key=>$val){
            $setData[] = [$key,$val];
        }
        $data = $this->update($setData, $where);
        return $data;
    }
    
    //删除活动
    public function deleteActive($activeId, $operator){
        $setData[] = ['status',-2];
        $where[] = ['id',$activeId];
        $where[] = ['operator',$operator];
        $affect = $this->update($setData,$where);
        return $affect;
    }

    /*
     * 获取活动对应的sku
     * */
    public function getActiveTabItems($active_id, $tab_title)
    {
        if(empty($active_id) || empty($tab_title)) {
            return [];
        }
        $where = [];
        $where[] = ['active_id', $active_id];
        $where[] = ['item_tab', $tab_title];
        $where[] = ['status', 1];
        $field = 'item_id';
        $orderBy = 'id desc';
        $this->tableName = $this->tableActiveItem;
        $res = $this->getRows($where, $field, FALSE, 0, $orderBy);
        return $res;
    }
    
}
