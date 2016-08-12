<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Item as ItemModel;

class Item extends \mia\miagroup\Lib\Service {
    public $itemModel;
    public function __construct() {
        $this->itemModel = new ItemModel();
    }
    
    /**
     * 根据商品id批量获取商品
     * @param int $itemIds
     */
    public function getItemList($itemIds){
        $itemInList = $this->itemModel->getBatchItemByIds($itemIds);
        return $this->succ($itemInList);
    }
    /**
     * 根据商品关联标识获取关联商品
     * @param  $relateFlags 商品关联标识
     */
    public function getRelateItemList($relateFlags){
        $itemInList = $this->itemModel->getBatchItemByFlags($relateFlags);
        return $this->succ($itemInList);
    }
}
