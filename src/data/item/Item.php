<?php
namespace mia\miagroup\Data\Item;

use Ice;

class Item extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'item';

    protected $mapping = array();

    /**
     * 批量获取商品信息
     * @param int $itemIds
     */
    public function getBatchItemInfoByIds($itemIds, $status = array(1)){
        if (empty($itemIds)) {
            return array();
        }
        $where = array();
        $where[] = ['id', $itemIds];
        if(!empty($status)){
            $where[] = ['status', $status];
        }
        
        $data = $this->getRows($where);
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['id']] = $v;
            }
        }
        return $result;
    }
    /**
     * 根据商品关联标识获取关联商品
     * @param  $relateFlags 商品关联标识
     */
    public function getBatchItemByFlags($relateFlags, $status = array(1)){
        if (empty($relateFlags)) {
            return array();
        }
        $where = array();
        $where[] = ['relate_flag', $relateFlags];
        if(!empty($status)){
            $where[] = ['status', $status];
        }
        
        $data = $this->getRows($where);
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['relate_flag']][$v['id']] = $v;
            }
        }
        return $result;
    }
    
}