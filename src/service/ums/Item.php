<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Item as ItemModel;

class Item extends \mia\miagroup\Lib\Service {
    
    public $itemModel;
    
    public function __construct() {
        parent::__construct();
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
    
    public function itemSearch($param)
    {
        $keyword = $param['key'];
        $page = isset($param['page']) ? $param['page'] : 0;
        $count = isset($param['count']) ? $param['count'] : 10;
        $order = isset($param['order']) ? $param['order'] : 'normal';
        $status = isset($param['so']) ? $param['so'] : 0;
        $brandId = isset($param['brand_id']) ? $param['brand_id'] : 0;
        $categoryId = isset($param['category_id']) ? $param['category_id'] : 0;
        $minPrice = isset($param['min_price']) ? $param['min_price'] : 0;
        $maxPrice = isset($param['max_price']) ? $param['max_price'] : 0;
        $propertyIds = isset($param['propertyIds']) ? $param['propertyIds'] : ''; // 4.0有结构体
        
        $sort = isset($param['sort']) ? $param['sort'] : 'desc';
        // 筛选器格式化
        if ($param['so'] == 8) {
            $status = 4; // 过滤规则
        } elseif ($param['so'] == 16) {
            $status = 8;
        } elseif ($param['so'] == 24) {
            $status = 4 | 8;
        }
        
        $items = [];
        if (!$keyword) {
            return $this->succ($items);
        }
        if (!in_array($sort, array('desc', 'asc'))) {
            return $this->succ($items);
        }
        if (!in_array($order, array('normal', 'sales', 'price', 'newGoods'))) {
            return $this->succ($items);
        }
        
        switch ($order) {
            case 'normal':
                $sort_by_field = 0;
                break;
            case 'price':
                $sort_by_field = 2;
                break;
            case 'sales':
                $sort_by_field = 3;
                break;
            case 'newGoods':
                $sort_by_field = 4;
                break;
            default:
                $sort_by_field = 0;
                break;
        }
        
        $searchArr["query"] = $keyword; // 搜索query，需要做urlencode
        $searchArr["rn"] = $count; // 搜索结果每页的结果数
        $searchArr["pn"] = $page - 1; // 搜索结果的翻页数
        $searchArr["sort_by_field"] = $sort_by_field; // 排序，默认是0；2价格排序；3销售量排序；4按新品排序
        $searchArr["sort_field_style"] = strtolower($sort) == "asc" ? 0 : 1; // 排序方式，1降序，0升序
        $searchArr["filter_by_field"] = $status; // 过滤规则
        $searchArr["search_type"] = 0x10 | 0x20; // 0x10 ：获取结果 0x20：获取筛选器。两个条件可做位或操作
        $searchArr["session"] = $param['session'];
        $searchArr["device_token"] = $param['device_token'];
        $searchArr["dvc_id"] = $param['dvc_id'];
        $searchArr["user_id"] = $param['user_id'];
        $searchArr["bi_session_id"] = $param['bi_session_id'];
        $searchArr["cluster_type"] = 0; // 0不做任何聚合，1进行默认方法聚合
        $searchArr["version"] = 1; // api版本,不传为老接口，传1为新接口
        
        if ($brandId) {
            $searchArr["filter_brand_ids"] = $brandId; // 筛选指定品牌id列表，以逗号分隔：2,3,5
        }
        if ($categoryId) {
            $searchArr["filter_category_ids"] = $categoryId; // 筛选指定的分类id列表，以逗号分隔：2,3,5
        }
        if (($minPrice >= 0) && $maxPrice) {
            $searchArr["min_price"] = $minPrice;
            $searchArr["max_price"] = $maxPrice;
        }
        
        if ($propertyIds) {
            $propertyIdsStr = "";
            $attrId = 0;
            $attr_v_array = [];
            foreach ($propertyIds as $value) {
                foreach ($value as $key => $propertyValue) {
                    if ($key == 'attr_id') { // 属性Id
                        $attrId = $propertyValue;
                    }
                    if ($key == 'attr_v_array') {
                        $attr_v_array = [];
                        foreach ($propertyValue as $k => $v) {
                            $attr_v_array[] = $v['attr_v_id']; // 属性中具体属性id
                        }
                    }
                }
                if ($attrId > 0 && !empty($attr_v_array)) {
                    $propertyIdsStr .= $attrId . ":" . implode(",", $attr_v_array) . "||";
                    continue;
                }
            }
            if ($propertyIdsStr) {
                $propertyIdsStr = substr($propertyIdsStr, 0, -2);
                $searchArr["attr_ids"] = $propertyIdsStr; // attr_id:attr_value_id1,attr_value_id2||.....(每个筛选属性组合以||分割)
            }
        }
        
        $result = ['matches' => [], 'total' => 0];
        $searchArr["query_source"] = 2; // 查询来源2.APP
        
        $datas = $this->searchRemote->itemSearch($searchArr);
        
        return $this->succ($items);
    }
}