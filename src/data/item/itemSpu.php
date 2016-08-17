<?php
namespace mia\miagroup\Data\Item;

use Ice;
use mia\miagroup\Data\Item\ItemSpu;

class ItemSpu extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'spu_sku_relation';

    protected $mapping = array();

    /**
     * 获取商品相关spu
     * @param int $itemId
     */
    public function getSpuByItemId($itemId)
    {
        $result = array();        
        if (empty($itemId)) {
            return $result;
        }
        $where = array();
        $where[] = ['item_id', $itemId];
        
        $data = $this->getRows($where);

        if (!empty($data)) {
            foreach ($data as $v) {
                $result[] = $v['spu_id'];
            }
        }
        return $result;
    }
    
}