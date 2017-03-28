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
     * @param array $itemIds
     * @param array $status
     * @return array
     */
    public function getBatchItemInfoByIds($itemIds, $status = [])
    {
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
    public function getBatchItemByFlags($relateFlags, $status = array()){
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

    /**
     * 获取九个妈妈国家信息
     */
    public function getNineMomCountryInfo($itemIds)
    {
        if (empty($itemIds) || !is_array($itemIds)) {
            return array();
        }
        $itemIds = implode(',', $itemIds);
        $sql = "SELECT i.id, cs.name, c.short_name, c.chinese_name
                FROM item AS i
                INNER JOIN customer_supplier AS cs ON i.supplier_id = cs.id
                INNER JOIN country c ON i.country_id = c.id
                WHERE i.supplier_id > 0
                AND cs.is_c2c IN (1, 2)
                AND i.id IN ({$itemIds})";
        $data = $this->query($sql);
        $result_arr = array();
        if (!empty($data)) {
            foreach ($data as $val) {
                $result_arr[$val['id']] = $val;
            }
        }
        return $result_arr;
    }

    /**
     * 获取待计算好评率的口碑商品
     */
    public function getListById($id = 0, $limit = 100) {
        $where[] = ['status', [0, 1]];
        if (intval($id) > 0) {
            $where[] = [':gt','id', $id];
        }
        $data = $this->getRows($where, '*', $limit, 0, 'id asc');
        return $data;
    }
    
    /**
     * 更新商品信息
     */
    public function updateItemInfoById($itemId, $itemInfo) {
        if (empty($itemId) || empty($itemInfo)) {
            return false;
        }
        $where[] = ['id', $itemId];
        $data = $this->update($itemInfo, $where);
        return $data;
    }

    /*
     * 获取商品的四级分类（new）
     * */
    public function getItemNewCategory($item_id)
    {
        if (empty($item_id)) {
            return false;
        }
        $where[] = ['id', $item_id];
        $where[] = ['status', 1];
        $fields = 'category_id_ng';
        $data = $this->getRows($where, $fields);
        $category_id_ng = $data[0]['category_id_ng'];
        return $category_id_ng;
    }
}