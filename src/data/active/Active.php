<?php
namespace mia\miagroup\Data\Active;

use Ice;

class Active extends \DB_Query {

    protected $dbResource = 'miagroup';
    protected $tableName = 'group_active';

    /**
     * 批量查活动信息
     */
    public function getBatchActiveInfos($page=1, $limit=20, $status = array(1), $activeIds = array()) {
        $offsetLimit = $page > 1 ? ($page - 1) * $limit : 0;
        $where[] = ['status',$status];
        if(!empty($activeIds)){
            $where[] = ['id',$activeIds];
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
    
}
