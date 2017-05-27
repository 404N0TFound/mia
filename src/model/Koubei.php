<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Koubei\Koubei as KoubeiData;
use mia\miagroup\Data\Koubei\KoubeiPic as KoubeiPicData;
use \mia\miagroup\Data\Koubei\KoubeiSubject as KoubeiSubjectData;
use \mia\miagroup\Data\Koubei\KoubeiAppeal as KoubeiAppealData;
use mia\miagroup\Data\Koubei\KoubeiTags;
use mia\miagroup\Data\Koubei\KoubeiTagsLayer;
use mia\miagroup\Data\Koubei\KoubeiTagsRelation;
use mia\miagroup\Data\Koubei\KoubeiCouponRule;

class Koubei {
    
    private $koubeiData;
    private $koubeiPicData;
    private $koubeiAppealData;
    private $koubeiTagsData;
    private $koubeiTagsLayerData;
    private $koubeiTagsRelationData;
    private $koubeiCouponRuleData;

    public function __construct() {
        $this->koubeiData = new KoubeiData();
        $this->koubeiPicData = new KoubeiPicData();
        $this->koubeiAppealData = new KoubeiAppealData();
        $this->koubeiTagsData = new KoubeiTags();
        $this->koubeiTagsLayerData = new KoubeiTagsLayer();
        $this->koubeiTagsRelationData = new KoubeiTagsRelation();
        $this->koubeiCouponRuleData = new KoubeiCouponRule();
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
    public function getKoubeiIdsByItemIds($itemIds, $limit = 10, $offset = 0, $condition = array()){
        if (empty($itemIds)) {
            return array();
        }
        $orderBy = 'is_bottom asc, auto_evaluate asc, rank_score desc, score desc, created_time desc';
        $koubeiData = $this->koubeiData->getKoubeiIdsByItemIds($itemIds, $limit, $offset, $orderBy, $condition);
        return $koubeiData;
    }
    
    /**
     * 获取商品带图口碑列表
     */
    public function getKoubeiByItemIdsAndCondition($item_ids, $conditon = array(), $limit = 10, $offset = 0){
        if (empty($item_ids)) {
            return array();
        }
        $order_by = 'is_bottom asc, auto_evaluate asc, rank_score desc, score desc, created_time desc';
        $koubei_data = $this->koubeiData->getKoubeiByItemIdsAndCondition($item_ids, $conditon, $limit, $offset, $order_by);
        return $koubei_data;
    }

    /**
     * 获取好评的口碑列表
     */
    public function getKoubeiPraisedList($item_ids, $condition = array(), $limit = 10, $offset = 0){
        if (empty($item_ids)) {
            return array();
        }
        $conditon = array();
        $conditon['item_id'] = $item_ids;
        $conditon['subject_id'] = 0;
        $conditon['status'] = 2;
        $conditon['score'] = 4;

        $order_by = 'rank_score desc, created_time desc';
        $koubei_data = $this->koubeiData->getKoubeiIdsByItemIds($item_ids, $limit, $offset, $order_by, $conditon);
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
     * @param $itemIds 商品id
     * @param $withPic
     * @param $type 口碑类型 [0:正常口碑，1：封测报告]
     * @return int
     */
    public function getItemKoubeiNums($itemIds, $withPic = 0, $conditions = array()){
        if (empty($itemIds)) {
            return 0;
        }
        $filed = ' count(distinct(koubei.id)) as nums ';
        $where = array();
        $where['item_id'] = $itemIds;
        $where['subject_id'] = 0;
        $where['status'] = 2;
        if ($withPic == 1) {
            $where['with_pic'] = 1;
        }
        if(!empty($conditions)) {
            foreach($conditions as $k => $v) {
                $where[$k] = $v;
            }
        }
        $koubeiNums = $this->koubeiData->getItemInvolveNums($filed, $where);
        return $koubeiNums;
    }

    /**
     * 获取发布口碑的用户数量
     * @param $itemIds 商品id
     * @return int
     */
    public function getItemKoubeiUserNums($itemIds, $withPic = 0){
        if(empty($itemIds)){
            return 0;
        }
        $filed = ' count(distinct(koubei.user_id)) as nums ';
        $where = array();
        $where['item_id'] = $itemIds;
        $where['subject_id'] = 0;
        $where['status'] = 2;
        $koubeiUserNums = $this->koubeiData->getItemInvolveNums($filed, $where);
        return $koubeiUserNums;
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
        $where['score'] = 1;
        $scoreNums = $this->koubeiData->getItemInvolveNums($filed, $where);
        return $scoreNums;
    }
    
    /**
     * 获取商品的蜜粉推荐(含关联商品),评分大于等于4
     * @param $itemIds int 商品id
     * @return array
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
    public function getItemKoubeiInfo($orderId, $itemId, $itemSize = '')
    {
        if(intval($orderId) < 0 || intval($itemId) < 0){
            return array();
        }
        $orderId = intval($orderId);
        $itemId = intval($itemId);
        
        $koubeiInfo = $this->koubeiData->getKoubeiByOrderItem($orderId, $itemId, $itemSize);
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
    public function setKoubeiRank($koubeiIds, $rank, $adminId){
        $koubeiSetInfo = array();
        $koubeiSetInfo[] = ['rank',$rank];
        if(isset($adminId) && $adminId> 0){
            $koubeiSetInfo[] = ['admin_id',$adminId];
            $koubeiSetInfo[] = ['verify_time',date('Y-m-d H:i:s')];
        }
        
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
        if(isset($koubeiInfo['verify_time'])){
            $koubeiSetInfo[] = ['verify_time',$koubeiInfo['verify_time']];
        }
        if(isset($koubeiInfo['admin_id'])){
            $koubeiSetInfo[] = ['admin_id',$koubeiInfo['admin_id']];
        }
        if(isset($koubeiInfo['rank_score'])){
            $koubeiSetInfo[] = ['rank_score',$koubeiInfo['rank_score']];
        }
        if(isset($koubeiInfo['immutable_score'])){
            $koubeiSetInfo[] = ['immutable_score',$koubeiInfo['immutable_score']];
        }
        if(isset($koubeiInfo['subject_id'])){
            $koubeiSetInfo[] = ['subject_id',$koubeiInfo['subject_id']];
        }
        //口碑状态
        if(isset($koubeiInfo['status']) && in_array(($koubeiInfo['status']),array(0,1,2))){
            $koubeiSetInfo[] = ['status',$koubeiInfo['status']];
        }
        //口碑工单
        if(isset($koubeiInfo['work_order'])){
            $koubeiSetInfo[] = ['work_order',$koubeiInfo['work_order']];
        }
        
        //更新口碑扩展字段
        if(isset($koubeiInfo['extr_info'])){
            $koubeiSetInfo[] = ['extr_info',$koubeiInfo['extr_info']];
        }
        
        //更新口碑精品状态
        if(isset($koubeiInfo['rank']) && in_array(($koubeiInfo['rank']),array(0,1))){
            $koubeiSetInfo[] = ['rank',$koubeiInfo['rank']];
        }
        //更新口碑沉帖状态
        if(isset($koubeiInfo['is_bottom']) && in_array(($koubeiInfo['is_bottom']),array(0,1))){
            $koubeiSetInfo[] = ['is_bottom',$koubeiInfo['is_bottom']];
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
        $setData[] = array('comment_id', $replyInfo['comment_id']);
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
    public function getBatchKoubeiByDefaultInfo($batch_info = array(), $issue_type = 'koubei'){
        $res = array();
        if(!empty($batch_info)){
            $issue_img      = $batch_info['issue_img'];
            $issue_skip_url = $batch_info['issue_skip_url'];
            // 发布首评奖励优先
            if(!empty($batch_info['issue_reward'])) {
                $res['issue_reward'] = $batch_info['issue_reward'];
            }
            if(!empty($batch_info['char_count'])) {
                $res['char_count'] = $batch_info['char_count'];
            }
            if(!empty($batch_info['image_count'])) {
                $res['image_count'] = $batch_info['image_count'];
            }
        }
        switch ($issue_type) {
            case 'subject':
                // banner 结构体
                $res['issue_tip_url']['pic']['url']   = $issue_img;
                $res['issue_tip_url']['pic']['width'] = $batch_info['issue_img_width'];
                $res['issue_tip_url']['pic']['height'] = $batch_info['issue_img_height'];
                $res['issue_tip_url']['url']          = !empty($issue_skip_url) ? $issue_skip_url : '';
                break;

            default:
                // banner 结构体
                $res['issue_tip_url']['pic']['url']   = $issue_img;
                $res['issue_tip_url']['pic']['width'] = $batch_info['issue_img_width'];
                $res['issue_tip_url']['pic']['height'] = $batch_info['issue_img_height'];
                $res['issue_tip_url']['url']          = !empty($issue_skip_url) ? $issue_skip_url : '';
                break;
        }

        return $res;
    }

    /*
     * 首评验证
     * */
    public function getCheckFirstComment($order_id, $item_id, $user_id = 0 ){
        $koubeiData = new KoubeiData();
        $result = $koubeiData->checkFirstComment($order_id, $item_id, $user_id);
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
     * 查询标签信息
     */
    public function getTags($tagsIds)
    {
        $where[] = ['id', $tagsIds];
        $tagsInfo = $this->koubeiTagsData->getTagsInfo($where);
        $res = [];
        if(!empty($tagsInfo)){
            foreach ($tagsInfo as $v){
                $res[$v["id"]] = $v;
            }
        }
        return $res;
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
        $where[] = ['id', $id];
        $res = $this->koubeiTagsData->updateTags($setData, $where);
        return $res;
    }

    /**
     * 更新标签
     */
    public function updateTagsByparentId($setData, $parentId)
    {
        $where[] = ['parent_id', $parentId];
        $res = $this->koubeiTagsData->updateTags($setData, $where);
        return $res;
    }

    /**
     * 更新口碑标签关系表
     * @param $setData
     * @param $tag_id_2
     * @return bool
     */
    public function updateRealtionByChildId($setData , $tag_id_2){
        $where[] = ['tag_id_2', $tag_id_2];
        $res = $this->koubeiTagsRelationData->updateRealtion($setData, $where);
        return $res;
    }

    public function updateRealtionByParentId($setData , $tag_id_1){
        $where[] = ['tag_id_1', $tag_id_1];
        $res = $this->koubeiTagsRelationData->updateRealtion($setData, $where);
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
     * 商品标签统计
     * 根据商品id，查询出所有父标签
     * @param $item_ids 商品id数组
     * @param array $tag_ids 查指定父标签的
     * @param int $count  是否统计数量(统计每个父标签的数量)
     * @return array
     */
    public function getItemKoubeiTags($item_ids,$tag_ids = [],$count = 0)
    {
        if (empty($item_ids)) {
            return [];
        }
        $where[] = ['koubei_tags_relation.item_id', $item_ids];

        if($count == 1){
            $cols = 'root,count(distinct(koubei_tags_relation.koubei_id)) as num';
        } else {
            $cols = 'root';
        }

        if (!empty($tag_ids)) {
            $where[] = ['koubei_tags_layer.root', $tag_ids];
        }

        $where[] = ['koubei.status', 2];
        $where[] = [':gt', 'koubei.subject_id', 0];

        $conditions['join_1'] = 'koubei_tags_layer';
        $conditions['join'] = 'koubei';

        $conditions['group_by'] = 'koubei_tags_layer.root';
        $tags = $this->koubeiTagsRelationData->getTagsKoubei($where, $cols, $conditions);
        $res = [];
        if (!empty($tags)) {
            foreach ($tags as $k => $v) {
                if (!empty($v['root'])) {
                    $res[$v['root']] = $v;
                }
            }
        }
        return $res;
    }

    /**
     * 获取口碑IDs
     * 根据商品和标签
     * @param $item_ids
     * @param $tag_id  根标签id
     * @return array
     */
    public function getItemKoubeiIds($item_ids, $tag_id, $limit, $offset)
    {
        if (empty($item_ids) || empty($tag_id)) {
            return [];
        }
        $where[] = ['koubei_tags_relation.item_id', $item_ids];


        $where[] = ['koubei_tags_layer.root', $tag_id];
        $where[] = ['koubei.status', 2];
        $where[] = ['koubei.type', 0];
        $where[] = [':gt', 'koubei.subject_id', 0];

        $conditions['join'] = 'koubei';
        $conditions['join_1'] = 'koubei_tags_layer';

        $conditions['limit'] = $limit;
        $conditions['offset'] = $offset;
        $conditions['order_by'] = 'koubei.rank_score desc, koubei.created_time desc';
        $koubeiIds = $this->koubeiTagsRelationData->getTagsKoubei($where, "koubei_id", $conditions);
        $res = [];
        if (!empty($koubeiIds)) {
            foreach ($koubeiIds as $v) {
                $res[] = $v['koubei_id'];
            }
        }
        return $res;
    }

    /**
     * 查询标签,口碑关系
     * @param $where
     * @return array
     */
    public function getItemTags($where)
    {
        $res = $this->koubeiTagsRelationData->getTags($where);
        return $res;
    }

    /**
     * 根据id删除记录
     * @param $delIds
     * @return mixed
     */
    public function delTagsKoubeiRelation($delIds)
    {
        $where[] = ["id",$delIds];
        $res = $this->koubeiTagsRelationData->delTagsKoubeiRelation($where);
        return $res;
    }


    /**
     * 添加layer表记录
     * @return KoubeiAppealData
     */
    public function addTagsLayer($setData)
    {
        $res = $this->koubeiTagsLayerData->addTagsLayer($setData);
        return $res;
    }


    /**
     * 判断是否是根标签
     * @return KoubeiAppealData
     */
    public function isRoot($tag_id)
    {
        $where[] = ["root",$tag_id];
        $where[] = ["parent",$tag_id];
        $where[] = ["tag_id",$tag_id];
        $res = $this->koubeiTagsLayerData->getInfo($where);
        return $res;
    }

    /**
     * 更新layer表
     * @param $setData
     * @param $where
     * @return bool
     */
    public function updateLayer($setData, $where)
    {
        $res = $this->koubeiTagsLayerData->updateLayer($setData, $where);
        return $res;
    }

    /**
     * 获取标签的根标签，是适用于单根
     * @param $tag_id
     * @return mixed
     */
    public function getRoot($tag_id)
    {
        $where[] = ["tag_id",$tag_id];
        $res = $this->koubeiTagsLayerData->getInfo($where);
        return $res;
    }

    /**
     * 递归求单个标签的所有子标签
     * @param $parentId
     * @return array
     */
    public function getChildList($parentId)
    {
        $where[] = ['parent', $parentId];
        $res = $this->koubeiTagsLayerData->getList($where);
        if (!empty($res)) {
            $return = [];
            foreach ($res as $v) {
                if ($v['tag_id'] == $parentId) {
                    continue;
                }
                $list = $this->getChildList($v['tag_id']);
                $return = array_merge($return, $list);
            }
            return array_merge($return, $res);
        } else {
            return $res;
        }
    }


    /**
     * 递归获取标签层级结构
     * @return mixed
     */
    public function showTrees()
    {
        $res = $this->koubeiTagsLayerData->showTrees();
        return $res;
    }

    /*
     * 获取代金券发放关系
     * */
    public function getCouponInfo($itemId = array(), $limit = 0, $offset = 0, $condition = array())
    {
        $order_by = 'created_time desc';
        $res = $this->koubeiCouponRuleData->koubeiCouponRule($itemId, $order_by, $offset, $limit, $condition);
        return $res;
    }


}