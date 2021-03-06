<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\Item\Item as ItemData;
use mia\miagroup\Data\Item\ItemPic as ItemPicData;
use mia\miagroup\Data\Item\ItemSpu as ItemSpuData;
use mia\miagroup\Data\Item\ItemCateRelation as ItemCateRelationData;
use mia\miagroup\Data\Item\UserSupplierMapping as UserSupplierMappingData;
use mia\miagroup\Data\Item\ItemBrand as ItemBrandData;

class Item {
    
    private $itemData;
    private $itemPicData;
    private $itemSpuData;
    private $userSupplierData;
    private $itemCateRelationData;
    private $itemBrandData;

    public function __construct() {
        $this->itemData = new ItemData();
        $this->itemPicData = new ItemPicData();
        $this->itemSpuData = new ItemSpuData();
        $this->itemCateRelationData = new ItemCateRelationData();
        $this->userSupplierData = new UserSupplierMappingData();
        $this->itemBrandData = new ItemBrandData();
    }
    
    /**
     * 根据商品id批量获取商品
     * @param int $itemIds 商品id
     */
    public function getBatchItemByIds($itemIds,$status=[0,1]){
        //获取商品基本信息
        $itemDatas = $this->itemData->getBatchItemInfoByIds($itemIds,$status);
        if (empty($itemDatas)) {
            return array();
        }
        $brandIds = array();
        foreach ($itemDatas as $v) {
            $brandIds[] = $v['brand_id'];
        }
        //获取商品图片信息
        $itemPics = $this->itemPicData->getBatchItemPicList($itemIds);
        //获取商品品牌信息
        $itemBrandInfos = $this->itemBrandData->getBatchBrandInfoByIds($brandIds);
        $result = array();
        foreach ($itemIds as $itemId) {
            if (empty($itemDatas[$itemId])) {
                continue;
            }
            $tmpItem = $itemDatas[$itemId];
            $tmpItem['brand_info'] = isset($itemBrandInfos[$tmpItem['brand_id']]) ? $itemBrandInfos[$tmpItem['brand_id']] : array();
            $tmpItem['img'] = isset($itemPics[$tmpItem['id']]) ? $itemPics[$tmpItem['id']] : array();
            $result[$itemId] = $tmpItem;
        }
        return $result;
    }
    /**
     * 根据商品关联标识获取关联商品
     * @param  $relateFlags 商品关联标识
     */
    public function getBatchItemByFlags($relateFlags){
        $itemListData = $this->itemData->getBatchItemByFlags($relateFlags);
        return $itemListData;
    }
    
    /**
     * 根据商品id获取套装id
     * @param  $itemId 商品id
     */
    public function getSpuByItemId($itemId){
        $spuData = $this->itemSpuData->getSpuByItemId($itemId);
        return $spuData;
    }
    
    /**
     * 根据spu_id获取商品
     * @param  $spuId 套装id
     */
    public function getItemBySpuId($spuId){
        $spuData = $this->itemSpuData->getItemBySpuId($spuId);
        return $spuData;
    }
    
    /**
     * 批量查询用户是否为商家
     */
    public function getBatchUserSupplierMapping($user_ids) 
    {
        $result = $this->userSupplierData->getBatchUserSupplierMapping($user_ids);
        return $result;
    }
    
    /**
     * 通过商家ID查找用户id
     */
    public function getMappingBySupplierId($supplier_id) {
        $result = $this->userSupplierData->getMappingBySupplierId($supplier_id);
        return $result;
    }
    
    /**
     * 添加商家和蜜芽圈用户的关联关系
     */
    public function addUserSupplierMapping($supplier_id, $user_id) {
        $data = $this->userSupplierData->getMappingBySupplierId($supplier_id);
        if (empty($data)) {
            $mapping_info = array('supplier_id' => $supplier_id, 'user_id' => $user_id, 'create_time' => date('Y-m-d H:i:s'));
            $result = $this->userSupplierData->addUserSupplierMapping($mapping_info);
            return $result;
        } else {
            if ($data['status'] == 0) {
                $this->userSupplierData->updateMappingById($data['id'], array('status' => 1));
                return $data['id'];
            } else {
                return $data['id'];
            }
        }
    }

    /**
     * 获取商品的九个妈妈信息
     */
    public function getNineMomCountryInfo($itemIds) {
        $data = $this->itemData->getNineMomCountryInfo($itemIds);
        return $data;
    }

    /**
     * 获取类目四级关联列表
     **/
    public function getCategoryFourList($three_cate, $flag)
    {
        $res = $this->itemCateRelationData->cateFourList($three_cate,$flag);
        return $res;
    }

    /**
     * 获取品牌名称列表
     */
    public function getRelationBrandNameList($brand_ids)
    {
        $data = $this->itemBrandData->getBatchBrandInfoByIds($brand_ids);
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                if (!empty($v['chinese_name'])) {
                    $result[$v['id']] = array('id' => $v['id'], 'name' => $v['chinese_name']);
                } else {
                    $result[$v['id']] = array('id' => $v['id'], 'name' => $v['name']);
                }
            }
        }
        return $result;
    }

    /*
     * 获取商品新定义分类
     * */
    public function itemCategoryIdNg($item_id, $condition = array())
    {
        $res = $this->itemData->getItemNewCategory($item_id, $condition);
        return $res;
    }

    /*
     * 获取商品分类
     * 维度：等级
     * */
    public function parentCatePath($category_id_ng, $condition = array())
    {
        $res = $this->itemCateRelationData->getparentCategoryPath($category_id_ng, $condition);
        return $res;
    }

    /*
     * 获取分类信息
     * */
    public function categoryInfo($category_id, $condition)
    {
        $res = $this->itemCateRelationData->getCategoryIdInfo($category_id, $condition);
        return $res;
    }

    /*
     * 更新商品信息
     * */
    public function updateItemInfo($itemId, $itemInfo)
    {
        $res = $this->itemData->updateItemInfo($itemId, $itemInfo);
        return $res;
    }

}