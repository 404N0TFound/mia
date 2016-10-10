<?php
 namespace mia\miagroup\Data\Item;
 
 use Ice;
 
 class ItemSpu extends \DB_Query {
 
     protected $dbResource = 'miadefault';
 
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
    
    /**
     * 获取spu的商品
     * @param int $spuId
     */
    public function getItemBySpuId($spuId)
    {
        $result = array();
        if (empty($spuId)) {
            return $result;
        }
        $where = array();
        $where[] = ['spu_id', $spuId];
         
        $data = $this->getRows($where);
         
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[] = $v['item_id'];
            }
        }
        return $result;
    }
    
}