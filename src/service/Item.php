<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Item as ItemModel;


class Item extends \mia\miagroup\Lib\Service {
    public $itemModel;
    public function __construct() {
        parent::__construct();
        $this->itemModel = new ItemModel();
    }
    
    /**
     * 获取商品的关联商品或者套装单品
     */
    public function getRelateItemById($item_id)
    {
        //是否配置了商品口碑口碑转换
        $transfer_config = \F_Ice::$ins->workApp->config->get('busconf.koubei.itemKoubeiTransfer');
        if (intval($transfer_config[$item_id]) > 0) {
            return array($item_id, $transfer_config[$item_id]);
        }
        
        //获取商品信息
        $item_info = $this->itemModel->getBatchItemByIds([$item_id])[$item_id];
        if (empty($item_info)) {
            return array();
        }
        $item_ids = array();
        // 如果有设置关联款，取关联商品ID
        if (!empty($item_info['relate_flag'])) {
            $related_items = $this->itemModel->getBatchItemByFlags([$item_info['relate_flag']]);
            if (!empty($related_items)) {
                foreach ($related_items as $v) {
                    $item_ids[] = $v['id'];
                }
            }
        }
        //如果没有设置关联快，且是单品套装的情况
        if (empty($item_ids) && $item_info['is_spu'] == 1 && $item_info['spu_type'] == 1) {
            // 根据套装id获取套装的商品
            $spu_item_ids = $this->itemModel->getItemBySpuId($item_id);
            if (!empty($spu_item_ids)) {
                // 根据套装的商品，获取商品的所有套装，实现套装和套装的互通
                $item_id_array = $this->itemModel->getSpuByItemId($spu_item_ids[0]);
                // 如果该商品还有其他套装
                if (count($item_id_array) > 1) {
                    // 过滤掉其他套装中为多品套装的
                    $items = $this->itemModel->getBatchItemByIds($item_id_array);
                    foreach ($items as $item) {
                        if ($item['is_spu'] == 1 && $item['spu_type'] == 1) {
                            $item_ids[] = $item['id'];
                        }
                    }
                }
                // 将套装的商品id和所有套装id拼在一起，实现单品和套装互通
                array_push($item_ids, $spu_item_ids[0]);
            }
        }
        $item_ids[] = $item_id;
        return $item_ids;
    }
    
    /**
     * 根据商品id批量获取商品
     * @param int $itemIds
     */
    public function getItemList($itemIds, $status = array(0, 1))
    {
        $itemList = $this->itemModel->getBatchItemByIds($itemIds,$status);
        if (empty($itemList)) {
            return $this->succ(array());
        }
        //获取九个妈妈国家信息
        $nineMotherInfos = $this->itemModel->getNineMomCountryInfo($itemIds);
        
        //组装商品信息
        foreach ($itemList as $key => $item) {
            //是否自营
            if ($item['is_single_sale'] == 1 && in_array($item['warehouse_type'], array(1, 6, 8))) {
                $itemList[$key]['is_self'] = '自营';
            } else {
                $itemList[$key]['is_self'] = '';
            }
            //好评率
            if (!empty($item['feedback_rate']) && floatval($item['feedback_rate']) >= 80) {
                $itemList[$key]['favorable_comment_percent'] = intval($item['feedback_rate']) . '%好评';
            } else {
                $itemList[$key]['favorable_comment_percent'] = '';
            }
            //商品业务模式
            $business_mode = '';
            if ($item['is_single_sale'] == 1) {
                if (in_array($item['warehouse_type'], array(6, 7))) {
                    // 跨境仓，虚拟跨境仓
                    $business_mode = '全球购';
                } else if (isset($nineMomArr[$item['id']])) {
                    //9个妈妈
                    $business_mode = $nineMotherInfos[$item['id']]['chinese_name'] . '代购';
                } else if (in_array($item['warehouse_type'], array(5, 8)) && !empty($item['country_pic_name'])) {
                    $business_mode = $item['country_pic_name'];
                }
            }
            $itemList[$key]['business_mode'] = $business_mode;
        }
        return $this->succ($itemList);
    }
    
    /**
     * 根据商品ID批量获取蜜芽圈商品展示信息
     */
    public function getBatchItemBrandByIds($itemIds, $is_show_cart = true)
    {
        if (empty($itemIds)) {
            return $this->succ(array());
        }
        $items = $this->getItemList($itemIds,[1])['data'];
        $itemList = array();
        if (!empty($items)) {
            foreach ($items as $item) {
                $tmp = null;
                $tmp['item_id'] = $item['id'];
                $tmp['item_name'] = $item['name'];
                $tmp['item_img'] = isset($item['img'][4]) ? $item['img'][4] : '';
                $tmp['brand_id'] = $item['brand_id'];
                $tmp['category_id'] = $item['category_id'];
                $tmp['brand_name'] = isset($item['brand_info']['name']) ? $item['brand_info']['name'] : '';
                $tmp['sale_price'] = $item['sale_price'];
                $tmp['market_price'] = $item['market_price'];
                $tmp['is_self'] = $item['is_self'];
                $tmp['business_mode'] = $item['business_mode'];
                $tmp['favorable_comment_percent'] = $item['favorable_comment_percent'];
                $tmp['show_cart'] = $is_show_cart ? 1 : 0;
                // 甄选商品标识（v5.3） 0:普通 1:甄选
                $tmp['is_pick'] = $item['is_pick'];
                $itemList[$item['id']] = $tmp;
            }
        }
        return $this->succ($itemList);
    }
    
    /**
     * 批量查询用户是否为商家
     */
    public function getBatchUserSupplierMapping($user_ids)
    {
        if (empty($user_ids)) {
            return $this->succ(array());
        }
        $result = $this->itemModel->getBatchUserSupplierMapping($user_ids);
        return $this->succ($result);
    }
    
    /**
     * 通过商家ID查找用户id
     */
    public function getMappingBySupplierId($supplier_id)
    {
        $result = $this->itemModel->getMappingBySupplierId($supplier_id);
        return $this->succ($result);
    }
    
    /**
     * 添加商家和蜜芽圈用户的关联关系
     */
    public function addUserSupplierMapping($supplier_id, $user_id)
    {
        $result = $this->itemModel->addUserSupplierMapping($supplier_id, $user_id);
        return $this->succ($result);
    }

    /*
     * 获取四级类目列表
     * */
    public function getCategoryFourIds($three_cate, $flag)
    {
        if (empty($three_cate) || empty($flag)) {
            return array();
        }
        $result = $this->itemModel->getCategoryFourList($three_cate, $flag);
        return $this->succ($result);;
    }

    /*
     * 获取四级品牌名称列表
     * */
    public function getRelationBrandName($brand_ids)
    {
        if(empty($brand_ids)) {
            return array();
        }
        $result = $this->itemModel->getRelationBrandNameList($brand_ids);
        return $this->succ($result);
    }

    /*
     * 根据商品分类对应路径
     * */
    public function getRelationCateId($item_id, $level)
    {
        if(empty($item_id)) {
            return '';
        }
        $parent_category_id = '';
        $catgory_id_ng = $this->getCategoryIdNgByItem($item_id)['data'];
        $category_path = $this->getParentCatePath($catgory_id_ng)['data'];
        $parent_cate_id = explode('-', $category_path);
        if(!empty($parent_cate_id)) {
            $parent_category_id = $parent_cate_id[$level - 1];
        }
        return $this->succ($parent_category_id);
    }

    /*
     * 根据商品ID获取新类目ID
     * */
    public function getCategoryIdNgByItem($item_id)
    {
        if(empty($item_id)) {
            return '';
        }
        $catgory_id_ng = $this->itemModel->itemCategoryIdNg($item_id);
        return $this->succ($catgory_id_ng);
    }

    /*
     * 根据商品四级类目获取父类目路径
     * */
    public function getParentCatePath($item_id)
    {
        if(empty($item_id)) {
            return '';
        }
        $catgory_path = $this->itemModel->parentCatePath($item_id);
        return $this->succ($catgory_path);
    }
}
