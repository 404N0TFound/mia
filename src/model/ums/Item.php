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
     * 查找所有一级类目
     */
    public function getAllHeadCategory() {
        $this->tableName = $this->tableItemCategory;
        $where[] = array('parent_id', 0);
        $where[] = array('is_leaf', 0);
        
        $data = $this->getRows($where);
        return $data;
    }
    
    /**
     * 查找一级类目对应的二级类目
     */
    public function getLeafCategoryByParentId($parentId) {
        $this->tableName = $this->tableItemCategory;
        if (empty($parentId)) {
            return false;
        }
        $where[] = array('parent_id', $parentId);
        $where[] = array('is_leaf', 1);
        
        $data = $this->getRows($where);
        return $data;
    }
}