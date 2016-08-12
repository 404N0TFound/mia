<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\Item\Item as ItemData;
use mia\miagroup\Data\Item\ItemPic as itemPicData;

class Item {
    
    public $itemData;
    public $itemPicData;
    
    public function __construct() {
        $this->itemData = new ItemData();
        $this->itemPicData = new itemPicData();
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
     * 根据item_id获取一组图片
     */
    public function getBatchItemPicList($item_id ,$type = 'normal'){
        $data = $this->itemPicData->getBatchItemPicList($item_id,$type);
        return $data;
    }

}