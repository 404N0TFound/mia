<?php
namespace mia\miagroup\Data\Active;

use Ice;

class ActiveItemTab extends \DB_Query {

    protected $dbResource = 'miagroup';
    protected $tableName = 'group_active_item_tab';
    protected $tablePointTags = 'group_subject_point_tags';
    protected $tableActiveRelation = 'group_subject_active_relation';

    /*
     * 获取活动用户对应的sku
     * */
    public function getActiveUserTabItems($active_id, $user_id = 0, $status = [1], $limit = 20, $offset = 0, $conditions = [])
    {
        if(empty($active_id) || empty($user_id)) {
            return [];
        }
        $where = [];
        $where[] = [$this->tableName.'.active_id', $active_id];
        if(!empty($conditions['item_tab'])) {
            $where[] = [$this->tableName.'.item_tab', $conditions['item_tab']];
        }
        if(!empty($conditions['is_pre_set'])) {
            $where[] = [$this->tableName.'.is_pre_set', $conditions['is_pre_set']];
        }
        $where[] = [':isnull', 'tmp.subject_id'];
        if(!empty($status)) {
            $where[] = [$this->tableName.'.status', $status];
        }
        $join = 'LEFT JOIN (SELECT '.$this->tablePointTags.'.item_id,'.$this->tablePointTags.'.subject_id FROM '.$this->tablePointTags.' 
        LEFT JOIN '.$this->tableActiveRelation.' ON '.$this->tablePointTags.'.subject_id = '.$this->tableActiveRelation.'.subject_id 
        WHERE '.$this->tableActiveRelation.'.user_id = '.$user_id.' AND '.$this->tableActiveRelation.'.active_id = '.$active_id.' 
        GROUP BY '.$this->tablePointTags.'.item_id) AS tmp ON '.$this->tableName.'.item_id = tmp.item_id';
        $field = $this->tableName.'.item_id';
        $orderBy = $this->tableName.'.sort desc,'.$this->tableName.'.id desc';
        $res = $this->getRows($where, $field, $limit, $offset, $orderBy, $join);
        return $res;
    }

    /*
     * 更新活动tab对应商品
     * */
    public function updateActiveItemTab($active_id, $updateData, $conditions = [])
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

    /*
     * 获取活动tab关联sku列表
     * */
    public function getActiveTabItems($active_id, $status = [1], $limit = 20, $offset = 0, $conditions = [])
    {
        if(empty($active_id)) {
            return [];
        }
        $where = [];
        $where[] = ['active_id', $active_id];
        if(!empty($status)) {
            $where[] = ['status', $status];
        }
        if(!empty($conditions['item_tab'])) {
            $where[] = ['item_tab', $conditions['item_tab']];
        }
        if(!empty($conditions['is_pre_set'])) {
            $where[] = ['is_pre_set', $conditions['is_pre_set']];
        }
        $field = 'id, active_id, item_tab, item_id, is_pre_set, status';
        $orderBy = 'sort desc,id desc';
        $res = $this->getRows($where, $field, $limit, $offset, $orderBy);
        return $res;
    }
}
