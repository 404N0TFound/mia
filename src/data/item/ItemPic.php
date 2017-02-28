<?php
namespace mia\miagroup\Data\Item;

use Ice;

class ItemPic extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'item_pictures';

    protected $mapping = array();
    
    /**
     * 批量获取商品图片
     */
    public function getBatchItemPicList($item_ids ,$type = 'normal')
    {
        if (empty($item_ids)) {
            return array();
        }
        $where[] = ['type',$type];
        $where[] = ['item_id',$item_ids];
        $where[] = ['status',1];
        $orderBy = "`index` ASC";
        
        $data = $this->getRows($where,'item_id,type,`index`,local_url',false,0,$orderBy);
        $result = array();
        if (!empty($data)) {
            foreach ($data as $value) {
                $result[$value['item_id']][$value['index']] = \F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $value['local_url'];
            }
        }
        return $result;
    }
}