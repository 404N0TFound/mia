<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Item extends \DB_Query {

    protected $dbResource = 'miadefaultums';

    protected $tableItem = 'item';
    protected $tableItemBrand = 'item_brand';
    protected $tableItemCategory = 'item_category';
    
    /**
     * 根据品牌名称查询brand_id
     */
    public function getBrandIdByName($brandName) {
        $this->tableName = $this->tableItemBrand;
        if (empty($brandName)) {
            return false;
        }
        $where[] = array(':eq','name', $brandName);
        
        $data = $this->getRow($where, 'id');
        if (!empty($data)) {
            return $data['id'];
        } else {
            return false;
        }
    }
    
    /**
     * 查询品牌下的所有商品
     */
    public function getAllItemByBrandId($brandId) {
        $this->tableName = $this->tableItem;
        if (empty($brandId)) {
            return false;
        }
        $where[] = array('brand_id', $brandId);
        $where[] = array('status', array(0, 1));
        $data = $this->getRows($where, 'id');
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[] = $v['id'];
            }
        }
        return $result;
    }
    
    /**
     * 查询类目下的所有商品
     */
    public function getAllItemByCategoryId($categoryId) {
        $this->tableName = $this->tableItem;
        if (empty($categoryId)) {
            return false;
        }
        $where[] = array('category_id', $categoryId);
        $where[] = array('status', array(0, 1));
        $data = $this->getRows($where, 'id');
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[] = $v['id'];
            }
        }
        return $result;
    }
    
    /**
     * 查找商品类目
     */
    public function getItemCategory($parentId = null, $isLeaf = null) {
        $this->tableName = $this->tableItemCategory;
        $where[] = array('status', 1);
        if ($parentId !== null) {
            $where[] = array('parent_id', $parentId);
        }
        if ($parentId !== null) {
            $where[] = array('is_leaf', $isLeaf);
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
     * 查询某供应商下的所有商品
     */
    public function getAllItemBySupplyId($supplyId) {
        $this->tableName = $this->tableItem;
        if (empty($supplyId)) {
            return false;
        }
        $where[] = array('supplier_id', $supplyId);
        $where[] = array('status', array(0, 1));
        $data = $this->getRows($where, 'id');
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[] = $v['id'];
            }
        }
        return $result;
    }

    /*
     * 更新商品信息
     * */
    public function updateItemInfoById($updateData, $itemId)
    {
        if (empty($updateData) || empty($itemId)) {
            return false;
        }
        $this->tableName = $this->tableItem;
        $where[] = ['id', $itemId];
        $setData = array();
        foreach($updateData as $key => $val){
            $setData[] = [$key, $val];
        }
        $data = $this->update($setData, $where);
        return $data;
    }
}