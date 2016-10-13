<?php
namespace mia\miagroup\Data\Item;

use Ice;
use mia\miagroup\Data\Item\ItemPic;

class Item extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'item';

    protected $mapping = array();

    /**
     * 批量获取商品信息
     * @param int $itemIds
     */
    public function getBatchItemInfoByIds($itemIds, $status = array()){
        if (empty($itemIds)) {
            return array();
        }
        $where = array();
        $where[] = ['id', $itemIds];
        if(!empty($status)){
            $where[] = ['status', $status];
        }
        
        $data = $this->getRows($where);
        $result = array();
        if (!empty($data)) {
            //批量获取图片信息
            $itemPic = new ItemPic();
            $imgInfo = $itemPic->getBatchItemPicList($itemIds);
            foreach ($data as $v) {
                $result[$v['id']] = $v;
                $result[$v['id']]['img'] = $imgInfo[$v['id']] ?: [];
            }
        }
        return $result;
    }
    /**
     * 根据商品关联标识获取关联商品
     * @param  $relateFlags 商品关联标识
     */
    public function getBatchItemByFlags($relateFlags, $status = array(1)){
        if (empty($relateFlags)) {
            return array();
        }
        $where = array();
        $where[] = ['relate_flag', $relateFlags];
        if(!empty($status)){
            $where[] = ['status', $status];
        }
        
        $data = $this->getRows($where);
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['id']] = $v;
            }
        }
        return $result;
    }
    
    //批量获取商品信息
    public function getBatchItemBrandByIds($itemsIds)
    {
        if(empty($itemsIds)){
            return array();
        }
        $itemsIds = implode(',', $itemsIds);
    
        $sql = "select i.id as item_id, i.name as item_name, i.sale_price, i.brand_id, b.name as brand_name
        from {$this->tableName} as i
        left join `item_brand` as b
        on i.brand_id = b.id
        where i.item_type = 0 and i.id in ({$itemsIds})";
        $itemResult = $this->query($sql);
    
        $itemArr = array();
        //添加商品图片
        if(!empty($itemResult)){
            foreach ($itemResult as $value) {
                $value['item_img'] = \mia\miagroup\Util\NormalUtil::show_picture('447_447', $value['item_id']);
                $itemArr[$value['item_id']] = $value;
            }
        }
    
        return $itemArr;
    }
    
}