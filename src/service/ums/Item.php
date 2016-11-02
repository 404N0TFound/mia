<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Item as ItemModel;

class Item extends \mia\miagroup\Lib\Service {
    
    public $itemModel;
    
    public function __construct() {
        $this->itemModel = new ItemModel();
    }
    
    /**
     * 获取商品的全部一级类目
     */
    public function getAllHeadCategory() {
        $result = $this->itemModel->getAllHeadCategory();
        return $this->succ($result);
    }
    
    /**
     * 获取商品一级类目下的所有二级类目
    */
    public function getLeafCategoryByParentId($parentId) {
        $result = $this->itemModel->getLeafCategoryByParentId($parentId);
        return $this->succ($result);
    }
}