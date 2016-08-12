<?php
namespace mia\miagroup\Data\Item;

use Ice;

class ItemPic extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'item_pictures';

    protected $mapping = array();
    
    /**
     * 批量获取关联商品的图片信息
     */
    public function getBatchRelatedItemPic($itemIds){
    
    }
    
    /**
     * 根据item_id获取一组图片
     */
    public function getBatchItemPicList($item_id ,$type = 'normal')
    {

        $where[] = ['type',$type];
        $where[] = ['item_id',$item_id];
        $where[] = ['status',1];
        $orderBy = "`index` ASC";
        
        $data = $this->getRows($where,'item_id,type,`index`,local_url',false,0,$orderBy);
        foreach ($data as $key => $value) {
            $data[$value['item_id']][$value['index']] = \F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $value['local_url'];
        }
        
        return $data;
    }
    
}