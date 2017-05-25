<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\PointTags as TagsModel;
use mia\miagroup\Service\Item as ItemService;

class PointTags extends \mia\miagroup\Lib\Service {
    
    public $tagsModel;
    public $itemService;
    
    public function __construct() {
        parent::__construct();
        $this->tagsModel = new TagsModel();
        $this->itemService = new ItemService();
    }
    
    /**
     * 保存蜜芽圈帖子标记信息
     * @param $subjectId 帖子id
     * @param itemIds array() 帖子标记信息
     */
    public function saveBatchSubjectTags($subjectId, $itemIds, $action = null){
        if (empty($subjectId) || empty($itemIds)) {
            return $this->error(500);
        }
        //判断是否已经存在过
        $info = $this->tagsModel->getInfoByIds($subjectId, $itemIds);
        $res_item_id = array_column($info, 'item_id');
        $itemIds = array_diff($itemIds, $res_item_id);
        if(empty($itemIds)){
            return $this->error(201,'请不要重复添加！');
        }
        // 封测报告未上线商品处理
        if(!empty($action) && $action == 'is_pick') {
            $itemInfos = $this->itemService->getItemList($itemIds,array())['data'];
        }else{
            $itemInfos = $this->itemService->getItemList($itemIds)['data'];
        }

        foreach($itemInfos as $itemId => $itemInfo){
            $tagSetInfo = array(
                "point_id" => 0,
                "title"       => $itemInfo['name'],
                "type"        => 'sku',
                "resource_id" => $itemInfo['brand_id'],
                "subject_id" => $subjectId,
                "item_id"     => $itemInfo['id'],
                "product_type"   => 1,
                "is_spu"      => 0,
            );
            $setData[] = $tagSetInfo;
        }
        $data = $this->tagsModel->saveBatchSubjectTags($setData);
        if ($action == 'ums_add_point') {
            //关联更新入队列
            $this->tagsModel->addSubjectUpdateQueue($subjectId);
        }
        return $this->succ($data);
    }
    
    /**
     * 批量查帖子相关商品id
     * @param $subjectIds array() 图片ids
     * @return array() 图片相关商品id列表
     */
    public function getBatchSubjectItmeIds($subjectIds){
        $data = $this->tagsModel->getBatchSubjectItmeIds($subjectIds);
        return $this->succ($data);
    }
    
    /**
     * 删除帖子关联商品
     */
    public function delSubjectTagById($subjectId,$itemIds){
        $data = $this->tagsModel->delSubjectTagById($subjectId, $itemIds);
        //关联更新入队列
        $this->tagsModel->addSubjectUpdateQueue($subjectId);
        return $this->succ($data);
    }
}
