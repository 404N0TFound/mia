<?php
namespace mia\miagroup\Data\Active;

use Ice;

class ActiveItemTab extends \DB_Query {

    protected $dbResource = 'miagroup';
    protected $tableName = 'group_active_item_tab';

    /*
     * 获取活动对应的sku
     * */
    public function getActiveTabItems($active_id, $tab_title, $limit = 20, $offset = 0)
    {
        if(empty($active_id) || empty($tab_title)) {
            return [];
        }
        $where = [];
        $where[] = ['active_id', $active_id];
        $where[] = ['item_tab', $tab_title];
        $where[] = ['status', 1];
        $where[] = ['is_pre_set', 0];
        $field = 'item_id';
        $orderBy = 'id desc';
        $res = $this->getRows($where, $field, $limit, $offset, $orderBy);
        return $res;
    }

    /*
     * 更新预设tab预设状态
     * */
    public function updateActiveItemPre($active_id, $updateData, $conditions = [])
    {
        if(empty($active_id) || empty($updateData)) {
            return false;
        }
        $where = $setData = [];
        $where[] = ['active_id', $active_id];
        foreach($conditions as $k => $v) {
            $where[] = [$k, $v];
        }
        foreach($updateData as $key=>$val){
            $setData[] = [$key,$val];
        }
        $data = $this->update($setData, $where);
        return $data;
    }
}
