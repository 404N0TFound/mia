<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Item\Item as ItemData;
class Item {
    
    public $itemData;
    
    public function __construct() {
        $this->itemData = new ItemData();
    }
    
    /**
     * 根据商品id批量获取商品
     * @param int $itemIds 商品id
     */
    public function getBatchItemByIds($itemIds){
        $itemData = $this->itemData->getBatchItemInfoByIds($itemIds);
        return $itemData;
    }
    /**
     * 根据商品关联标识获取关联商品
     * @param  $relateFlags 商品关联标识
     */
    public function getBatchItemByFlags($relateFlags){
        $itemListData = $this->itemData->getBatchItemByFlags($relateFlags);
        return $itemListData;
    }

}