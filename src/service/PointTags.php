<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\PointTags as TagsModel;
use mia\miagroup\Service\Item as ItemService;

class PointTags extends \mia\miagroup\Lib\Service {
    
    public $tagsModel;
    public $itemService;
    
    public function __construct() {
        $this->tagsModel = new TagsModel();
        $this->itemService = new ItemService();
    }
    
    /**
     * 保存蜜芽圈帖子标记信息
     * @param $subjectId 帖子id
     * @param $tagsData array() 帖子标记信息
     */
    public function saveSubjectTags($subjectId,$tagInfo){
        if(empty($tagInfo)){
            return $this->error();
        }
        $itemInfo = $this->itemService->getItemList([$tagInfo['item_id']])['data'][$tagInfo['item_id']];
        //品牌id
        if (isset($itemInfo['brand_id']) && intval($itemInfo['brand_id']) > 0) {
            $resourceId = intval($itemInfo['brand_id']);
        }else{
             return $this->error();
        }
        //商品id
        if (isset($itemInfo['id']) && intval($itemInfo['id']) > 0) {
            $itemId = intval($itemInfo['id']);
        }else{
             return $this->error();
        }
        //商品名称 ---title
        if (isset($itemInfo['name']) && !empty($itemInfo['name']) ) {
            $title = $itemInfo['name'];
        }else{
             return $this->error();
        }
        
        $tagSetInfo = array(
            "point_id" => 0,
            "title"       => $title,
            "type"        => 'sku',
            "resource_id" => $resourceId,
            "subject_id" => $subjectId,
            "item_id"     => $itemId,
            "product_type"   => 1,
            "is_spu"      => 0,
        );
        $insertId = $this->tagsModel->saveSubjectTags($tagSetInfo);
        if(!$insertId){
            return $this->succ();
        }
        return $this->succ($insertId);
    }
}
