<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\Item\Item as ItemData;
use mia\miagroup\Data\Item\ItemPic as ItemPicData;
use mia\miagroup\Data\Item\ItemSpu as ItemSpuData;

class Item {
    
    public $itemData;
    public $itemPicData;
    public $itemSpuData;
    
    public function __construct() {
        $this->itemData = new ItemData();
        $this->itemPicData = new ItemPicData();
        $this->itemSpuData = new ItemSpuData();
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
    
    /**
     * 根据商品id获取套装id
     * @param  $itemId 商品id
     */
    public function getSpuByItemId($itemId){
        $spuData = $this->itemSpuData->getSpuByItemId($itemId);
        return $spuData;
    }
    
    /**
     * 根据spu_id获取商品
     * @param  $spuId 套装id
     */
    public function getItemBySpuId($spuId){
        $spuData = $this->itemSpuData->getItemBySpuId($spuId);
        return $spuData;
    }
    
    /**
     * 根据item_id获取一组图片
     */
    public function getBatchItemPicList($item_id ,$type = 'normal'){
        $data = $this->itemPicData->getBatchItemPicList($item_id,$type);
        return $data;
    }
    
    //批量获取商品信息
    public function getBatchItemBrandByIds($itemsIds)
    {
        return $this->itemData->getBatchItemBrandByIds($itemsIds);
    }

}