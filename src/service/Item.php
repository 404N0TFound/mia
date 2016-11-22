<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Item as ItemModel;


class Item extends \mia\miagroup\Lib\Service {
    public $itemModel;
    public function __construct() {
        $this->itemModel = new ItemModel();
    }
    
    /**
     * 获取商品相关的套装id
     * @param $itemId 商品id
     */
    public function getSpuRelateItem($itemId){
        $spuArr = $this->itemModel->getSpuByItemId($itemId);
        return $this->succ($spuArr);
    }
    
    /**
     * 获取套装的商品
     * @param $spuId 套装id
     */
    public function getItemRelateSpu($spuId){
        $itmeArr = $this->itemModel->getItemBySpuId($spuId);
        return $this->succ($itmeArr);
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
    
    //批量获取商品信息
    public function getBatchItemBrandByIds($itemsIds)
    {
        $data = $this->itemModel->getBatchItemBrandByIds($itemsIds);
        return $this->succ($data);
    }
}
