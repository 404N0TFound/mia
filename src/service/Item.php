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
    
    /**
     * 批量获取商品信息
     */
    public function getBatchItemBrandByIds($itemsIds)
    {
        $data = $this->itemModel->getBatchItemBrandByIds($itemsIds);
        return $this->succ($data);
    }
    
    /**
     * 批量查询用户是否为商家
     */
    public function getBatchUserSupplierMapping($user_ids)
    {
        if (empty($user_ids)) {
            return $this->succ(array());
        }
        $result = $this->itemModel->getBatchUserSupplierMapping($user_ids);
        return $this->succ($result);
    }
    
    /**
     * 通过商家ID查找用户id
     */
    public function getMappingBySupplierId($supplier_id)
    {
        $result = $this->itemModel->getMappingBySupplierId($supplier_id);
        return $this->succ($result);
    }
    
    /**
     * 添加商家和蜜芽圈用户的关联关系
     */
    public function addUserSupplierMapping($supplier_id, $user_id)
    {
        $result = $this->itemModel->addUserSupplierMapping($supplier_id, $user_id);
        return $this->succ($result);
    }
}
