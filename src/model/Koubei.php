<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Koubei\Koubei as KoubeiData;
use mia\miagroup\Data\Koubei\KoubeiPic as KoubeiPicData;
class Koubei {
    
    public $koubeiData;
    public $koubeiPicData;
    
    public function __construct() {
        $this->koubeiData = new KoubeiData();
        $this->koubeiPicData = new KoubeiPicData();
    }
    
    /**
     * 保存口碑信息
     * @param $koubeiData array() 口碑发布信息
     */
    public function saveKoubei($koubeiInfo){
        $data = $this->koubeiData->saveKoubei($koubeiInfo);
        return $data;
    }
    
    /**
     * 保存口碑相关图片信息
     * @param $koubeiPicData array() 口碑图片发布信息
     */
    public function saveKoubeiPic($koubeiPicData){
        $data = $this->koubeiPicData->saveKoubeiPic($koubeiPicData);
        return $data;
    }
    
    /**
     * 获取商品口碑列表
     * @param array $itemIds 商品id
     * @param $where
     */
    public function getKoubeiList($itemIds, $limit, $offset){
        $orderBy = 'rank_score desc, created_time desc';
        $koubeiData = $this->koubeiData->getBatchItemKoubei($itemIds, $limit, $offset, $orderBy);
        return $koubeiData;
    }
    
    /**
     * 获取口碑数量
     * @param $itemIds int 商品id
     */
    public function getItemKoubeiNums($itemIds){
        $filed = ' count(*) as nums ';
        $where = array();
        $where['item_id'] = $itemIds;
        $where['subject_id'] = 0;
        $where['status'] = 2;
        
        $koubeiNums = $this->koubeiData->getItemInvolveNums($filed, $where);
        return $koubeiNums;    
    }
    
    /**
     * 获取商品的用户评分
     * @param $itemIds int 商品id
     */
    public function getItemUserScore($itemIds){
        $filed = ' AVG(score) as nums ';
        $where = array();
        $where['item_id'] = $itemIds;
        $where['subject_id'] = 0;
        $where['status'] = 2;
        $scoreNums = $this->koubeiData->getItemInvolveNums($filed, $where);
        return $scoreNums;
    }
    
    /**
     * 获取商品的蜜粉推荐(含关联商品)
     * @param $itemIds int 商品id
     */
    public function getItemRecNums($itemIds){
        $filed = ' count(*) as nums ';
        $where = array();
        $where['item_id'] = $itemIds;
        $where['subject_id'] = 0;
        $where['status'] = 2;
        $where['score'] = 4;
        $recNums = $this->koubeiData->getItemInvolveNums($filed, $where);
        return $recNums;
    }
    
    /**
     * 查看订单商品是否有该口碑信息
     * @param  $orderId
     * @param  $itemId
     */
    public function getItemKoubeiInfo($orderId, $itemId)
    {
        $koubeiInfo = $this->koubeiData->getKoubeiByOrderItem($orderId, $itemId);
        return $koubeiInfo;
    }
    
    /**
     * 更新帖子id到口碑表中
     * @param int $koubeiId
     * @param int $subjectId
     */
    public function addSubjectIdToKoubei($koubeiId,$subjectId){
        $koubeiInfo = $this->koubeiData->updateKoubeiBySubjectid($koubeiId,$subjectId);
        return $koubeiInfo;
    }
}