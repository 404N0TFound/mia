<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Koubei\Koubei as KoubeiData;
use mia\miagroup\Data\Koubei\KoubeiPic as KoubeiPicData;
use \mia\miagroup\Data\Koubei\KoubeiSubject as KoubeiSubjectData;
use \mia\miagroup\Data\Koubei\KoubeiAppeal as KoubeiAppealData;
use mia\miagroup\Data\Koubei\KoubeiTags;
use mia\miagroup\Data\Koubei\KoubeiTagsRelation;

class Koubei {
    
    private $koubeiData;
    private $koubeiPicData;
    private $koubeiAppealData;
    private $koubeiTagsData;
    private $koubeiTagsRelationData;
    
    
    public function __construct() {
        $this->koubeiData = new KoubeiData();
        $this->koubeiPicData = new KoubeiPicData();
        $this->koubeiAppealData = new KoubeiAppealData();
        $this->koubeiTagsData = new KoubeiTags();
        $this->koubeiTagsRelationData = new KoubeiTagsRelation();
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
    public function getKoubeiIdsByItemIds($itemIds, $limit = 10, $offset = 0, $conditon = array()){
        if (empty($itemIds)) {
            return array();
        }
        $orderBy = 'rank_score desc, created_time desc';
        $koubeiData = $this->koubeiData->getKoubeiIdsByItemIds($itemIds, $limit, $offset, $orderBy);
        return $koubeiData;
    }
    
    /**
     * 获取商品带图口碑列表
     */
    public function getKoubeiByItemIdsAndCondition($item_ids, $conditon = array(), $limit = 10, $offset = 0){
        if (empty($item_ids)) {
            return array();
        }
        $order_by = 'rank_score desc, created_time desc';
        $koubei_data = $this->koubeiData->getKoubeiByItemIdsAndCondition($item_ids, $conditon, $limit, $offset, $order_by);
        return $koubei_data;
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
        //口碑状态
        if(isset($koubeiInfo['status'])){
            $koubeiSetInfo[] = ['status',$koubeiInfo['status']];
        }
        //口碑工单
        if(isset($koubeiInfo['work_order'])){
            $koubeiSetInfo[] = ['work_order',$koubeiInfo['work_order']];
        }
        
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
    
    /**
     * 检查申诉是否已存在
     */
    public function checkAppealInfoExist($koubei_id, $koubei_comment_id = 0)
    {
        $result = $this->koubeiAppealData->checkAppealInfoExist($koubei_id, $koubei_comment_id);
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
        // banner 结构体
        $res['issue_tip_url']['pic']['url']   = $issue_img;
        $res['issue_tip_url']['pic']['width'] = $batch_info['issue_img_width'];
        $res['issue_tip_url']['pic']['height'] = $batch_info['issue_img_height'];
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
        $ids = $this->koubeiData->getBatchBestKoubeiIds($itemIds);
        return $ids;
    }

    /**
     * 查询口碑印象
     */
    public function getTagInfo($tagName)
    {
        $where[] = [':eq', 'tag_name', $tagName];
        $tagsInfo = $this->koubeiTagsData->getTagInfo($where);
        return $tagsInfo;
    }

    /**
     * 查询标签
     */
    public function getTags($where)
    {
        $tagsInfo = $this->koubeiTagsData->getTagsInfo($where);
        return $tagsInfo;
    }

    /**
     * 添加口碑标签
     */
    public function addTag($data)
    {
        $tagId = $this->koubeiTagsData->addTag($data);
        return $tagId;
    }

    /**
     * 获取当前标签的子类
     */
    public function getChildTags($parentId)
    {
        $where[] = [':eq', 'parent_id', $parentId];
        $res = $this->koubeiTagsData->getTagsInfo($where);
        if (empty($res)) {
            return [];
        }
        foreach ($res as $k => $v) {
            $childArr[] = $v['id'];
        }
        return $childArr;
    }


    /**
     * 更新标签
     */
    public function updateTags($setData, $id)
    {
        $res = $this->koubeiTagsData->updateTags($setData, $id);
        return $res;
    }

    /**
     * 添加标签和口碑关系
     */
    public function addTagsRelation($insertData)
    {
        $tagId = $this->koubeiTagsRelationData->addTagsRealtion($insertData);
        return $tagId;
    }

    /**
     * 商品标签查询，父子都可能有
     * array ( 5 => '2', 6 => '2', 7 => '1', 8 => '1', 9 => '1',) id和数量的组合
     * $where = array(), $cols = '*', $limit = FALSE, $offset = 0, $orderBy = FALSE, $join = FALSE, $groupBy = FALSE, $having = FALSE, $tableOptions = FALSE, $selectOptions = FALSE
     */
    public function getItemKoubeiTags($item_id)
    {
        if (empty($item_id)) {
            return [];
        }
        $where[] = [':eq', 'item_id', $item_id];
        $tags = $this->koubeiTagsRelationData->getTags($where, $cols = 'tag_id,count(id) as num', $limit = FALSE, $offset = 0, $orderBy = FALSE, $join = FALSE, $groupBy = "tag_id");
        $tagArr = [];
        if(!empty($tags)){
            foreach ($tags as $v){
                $tagArr[$v['tag_id']] = $v['num'];
            }
        }
        return $tagArr;
    }


    /**
     * 查询标签,口碑关系
     */
    public function getItemTags($where)
    {
        $res = $this->koubeiTagsRelationData->getTags($where);
        return $res;
    }
}