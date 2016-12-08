<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Item as ItemModel;

class Item extends \mia\miagroup\Lib\Service {
    
    public $itemModel;
    
    public function __construct() {
        $this->itemModel = new ItemModel();
    }
    
    /**
     * 获取商品的一级类目和二级类目
     */
    public function getItemCategory($parentId = 0, $isLeaf = 0) {
        //所有一级类目
        $headCategory = $this->itemModel->getItemCategory($parentId, $isLeaf);
        //所有二级类目
        $leafCategory = $this->itemModel->getItemCategory(null, 1);
        
        $result = array();
        foreach ($leafCategory as $category) {
            if (intval($category['parent_id']) <= 0) {
                continue;
            }
            if (!isset($result[$category['parent_id']]) && !empty($headCategory[$category['parent_id']])) {
                $result[$category['parent_id']] = array(
                    'id' => $headCategory[$category['parent_id']]['id'], 
                    'category_name' => $headCategory[$category['parent_id']]['name'],
                    'sub_categorys' => array()
                );
            }
            if (!empty($result[$category['parent_id']])) {
                $result[$category['parent_id']]['sub_categorys'][] = array(
                    'id' => $category['id'],
                    'category_name' => $category['name'],
                );
            }
        }
        return $this->succ(array_values($result));
    }
    
}