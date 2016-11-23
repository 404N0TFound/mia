<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Koubei\Koubei as KoubeiData;
use mia\miagroup\Data\Koubei\KoubeiPic as KoubeiPicData;
use \mia\miagroup\Data\Koubei\KoubeiSubject as KoubeiSubjectData;

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
     * 获取商品口碑ids列表
     * @param array $itemIds 商品id
     * @param $limit
     * @param $offset
     */
    public function getKoubeiIds($itemIds, $limit, $offset){
        if (empty($itemIds)) {
            return array();
        }
        $orderBy = 'rank_score desc, created_time desc';
        $koubeiData = $this->koubeiData->getKoubeiIdsByItemIds($itemIds, $limit, $offset, $orderBy);
        return $koubeiData;
    }
    
    /**
     * 批量获取商品口碑
     * @param array $KoubeiIds 口碑id
     * @param $status
     */
    public function getBatchKoubeiByIds($KoubeiIds,$status){
        if (empty($KoubeiIds)) {
            return array();
        }
        $koubeiData = $this->koubeiData->getBatchKoubeiByIds($KoubeiIds, $status);
        $koubeiInfos = array();
        foreach ($KoubeiIds as $koubeiId) {
            if (!empty($koubeiData[$koubeiId])) {
                $koubeiInfos[$koubeiId] = $koubeiData[$koubeiId];
            }
        }
        return $koubeiInfos;
    }
    
    /**
     * 获取口碑数量
     * @param $itemIds int 商品id
     */
    public function getItemKoubeiNums($itemIds){
        if(empty($itemIds)){
            return 0;
        }
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
        if(empty($itemIds)){
            return 0;
        }
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
        if(empty($itemIds)){
            return 0;
        }
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
        if(intval($orderId) < 0 || intval($itemId) < 0){
            return array();
        }
        $orderId = intval($orderId);
        $itemId = intval($itemId);
        
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
    
    /**
     * 删除口碑
     */
    public function delete($id, $userId){
        $result = $this->koubeiData->delete($id,$userId);
        return $result;
    }
    
    /**
     * 根据用户id查询口碑帖子
     * @param int $userId
     */
    public function getKoubeisByUid($userId){
        $result = $this->koubeiData->getKoubeisByUid($userId);
        return $result;
    }
    
    /**
     * 批量删除口碑
     */
    public function deleteKoubeis($koubeiIds){
        $result = $this->koubeiData->deleteKoubeis($koubeiIds);
        return $result;
    }
    
    /**
     * 加精口碑
     */
    public function setKoubeiRank($koubeiIds, $rank){
        $koubeiSetInfo = array();
        $koubeiSetInfo[] = ['rank',$rank];
        $result = $this->koubeiData->updateKoubeiInfoById($koubeiIds, $koubeiSetInfo);
        //修改口碑的相关分数
        if($rank == 1){
            $num = 3;
        }else{
            $num = -3;
        }
        $koubeiIds = implode(',', $koubeiIds);
        
        $this->koubeiData->updateKoubeiCount($koubeiIds, $num, 'rank_score');
        $this->koubeiData->updateKoubeiCount($koubeiIds, $num, 'immutable_score');
        return $result;
    }
    
    /**
     * 修改口碑通过状态
     */
    public function setKoubeiStatus($koubeiId, $koubeiInfo){
        $koubeiSetInfo = array();
        $koubeiSetInfo[] = ['status',$koubeiInfo['status']];
        $result = $this->koubeiData->updateKoubeiInfoById($koubeiId, $koubeiSetInfo);
    }
    
    /**
     * 新增口碑贴信息
     */
    public function addKoubeiSubject($data){
        $koubeiSubjectData = new KoubeiSubjectData();
        $result = $koubeiSubjectData->saveKoubeiSubject($data);
        return $result;
    }
    
    /**
     * 口碑蜜芽贴中的通过状态
     */
    public function updateKoubeiSubjectStatus($subjectId, $isAudited){
        if (!in_array($isAudited, array(0, 1))) {
            return false;
        }
        $koubeiSubjectData = new KoubeiSubjectData();
        $result = $koubeiSubjectData->updateKoubeiSubject(array('is_audited' => $isAudited), $subjectId);
        return $result;
    }
    
    /**
     * 根据蜜芽贴id查询口碑
     */
    public function getKoubeiBySubjectId($subjectId){
        $koubeiData = new KoubeiData();
        $result = $koubeiData->getKoubeiBySubjectId($subjectId);
        return $result;
    }

    /**
     * 首评口碑奖励及图片提示
     */
    public function getBatchKoubeiByDefaultInfo($batch_info = array()){
        $res = array();
        if(!empty($batch_info)){
            $issue_img      = $batch_info['issue_img'];
            $issue_skip_url = $batch_info['issue_skip_url'];
            $res['issue_reward'] = $batch_info['issue_reward'];
        }
        $img_info = json_decode(file_get_contents($issue_img.'?imageInfo'),true);
        // banner 结构体
        $res['issue_tip_url']['pic']['url']   = $issue_img;
        $res['issue_tip_url']['pic']['width'] = $img_info['width'];
        $res['issue_tip_url']['pic']['hight'] = $img_info['height'];
        $res['issue_tip_url']['url']          = $issue_skip_url;
        return $res;
    }

    /*
     * 首评验证
     * */
    public function getCheckFirstComment($order_id, $item_id){
        $koubeiData = new KoubeiData();
        $result = $koubeiData->checkFirstComment($order_id, $item_id);
        return $result;
    }

    public function getBatchKoubeiIds($itemIds){
        $ids = $this->koubeiData->getBatchKoubeiIds($itemIds);
        return $ids;
    }

}