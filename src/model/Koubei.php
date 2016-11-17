<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Koubei\Koubei as KoubeiData;
use mia\miagroup\Data\Koubei\KoubeiPic as KoubeiPicData;
use \mia\miagroup\Data\Koubei\KoubeiSubject as KoubeiSubjectData;
use \mia\miagroup\Data\Koubei\KoubeiAppeal as KoubeiAppealData;

class Koubei {
    
    private $koubeiData;
    private $koubeiPicData;
    private $koubeiAppealData;
    
    
    public function __construct() {
        $this->koubeiData = new KoubeiData();
        $this->koubeiPicData = new KoubeiPicData();
        $this->koubeiAppealData = new KoubeiAppealData();
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
    public function getBatchKoubeiByIds($KoubeiIds, $status = array(2)){
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
     * 口碑回复状态更新
     */
    public function updateKoubeiReplyStatus($koubeiId, $replyInfo) {
        $koubeiData = new KoubeiData();
        $setData = array();
        $setData[] = array('reply', $replyInfo['reply']);
        $setData[] = array('comment_status', 1);
        $setData[] = array('comment_time', $replyInfo['comment_time']);
        $setData[] = array('comment_supplier_id', $replyInfo['comment_supplier_id']);
        $result = $koubeiData->updateKoubeiInfoById($koubeiId, $setData);
        return $result;
    }
    
    /**
     * 新增口碑申诉
     */
    public function addKoubeiAppeal($appeal_info)
    {
        $appeal_id = $this->koubeiAppealData->addKoubeiAppeal($appeal_info);
        return $appeal_id;
    }
    
    /**
     * 口碑申诉通过
     */
    public function passKoubeiAppeal($appeal_id, $operator_id = 0)
    {
        $appeal_info['status'] = 1;
        $appeal_info['pass_time'] = date('Y-m-d H:i:s');
        $appeal_info['operator_id'] = intval($operator_id);
        $result = $this->koubeiAppealData->updateAppealInfoByKoubeiId($appeal_id, $appeal_info);
        return $result;
    }

    /**
     * 口碑申诉驳回
     */
    public function refuseKoubeiAppeal($appeal_id, $refuse_reason = '', $operator_id = 0)
    {
        $appeal_info['status'] = 2;
        $appeal_info['refuse_time'] = date('Y-m-d H:i:s');
        if (!empty($refuse_reason)) {
            $appeal_info['refuse_reason'] = $refuse_reason;
        }
        $appeal_info['operator_id'] = $operator_id;
        $result = $this->koubeiAppealData->updateAppealInfoByKoubeiId($appeal_id, $appeal_info);
        return $result;
    }
    
    /**
     * 根据口碑ID获取口碑申诉信息
     */
    public function getAppealInfoByIds($appeal_ids, $status = array())
    {
        $result = $this->koubeiAppealData->getAppealInfoByIds($appeal_ids, $status);
        return $result;
    }
}