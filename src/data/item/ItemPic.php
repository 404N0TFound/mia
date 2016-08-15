<?php
namespace mia\miagroup\Data\Item;

use Ice;

class ItemPic extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'item_pictrue';

    protected $mapping = array();
    
    /**
     * 批量获取关联商品的图片信息
     */
    public function getBatchRelatedItemPic($itemIds){
    
    }
}