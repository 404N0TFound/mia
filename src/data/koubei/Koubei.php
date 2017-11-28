<?php
namespace mia\miagroup\Data\Koubei;

use Ice;

class Koubei extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'koubei';

    protected $mapping = array();
    
    
    /**
     * 根据商品id批量获取商品口碑id
     * @param array() $itemIds 商品id
     * @return array()
     */
    public function getKoubeiIdsByItemIds($itemIds, $limit = 20, $offset = 0, $orderBy = false ,$condition = array()) {
        $result = array();
        if (empty($itemIds)) {
            return $result;
        }
        $where = array();
        $where[] = ['item_id', $itemIds];
        if(isset($condition["status"])) {
            $where[] = ['status', $condition['status']];
        }else{
            $where[] = ['status', 2];
        }
        $where[] = [':gt','subject_id',0];
        if (isset($condition["koubei_id"])) {
            $where[] = ['id', $condition["koubei_id"]];
        }

        if (isset($condition["score"])) {
            $where[] = [":ge",'score', $condition["score"]];
        }

        // 封测报告
        if(isset($condition['is_pick'])) {
            $where [] = [
                ':and', [
                    [':or', [':ne', 'auto_evaluate', $condition['auto_evaluate']]],
                    [':or', [':ne', 'type', $condition['type']]]
                ]
            ];
        }else {
            if (isset($condition["type"])) {
                $where[] = ['type', $condition["type"]];
            }
            if (isset($condition["type"])) {
                $where[] = ['auto_evaluate', $condition["auto_evaluate"]];
            }
        }

        $fields = 'id,subject_id,rank_score,created_time,title,content,score,rank,item_size';
        $data = $this->getRows($where,$fields,$limit,$offset,$orderBy);
        if (!empty($data)) {
            foreach($data as $v){
                $result[] = $v['id'];
            }
        }
        return $result;
    }
    
    /**
     * 根据商品id批量获取商品带图口碑id
     */
    public function getKoubeiByItemIdsAndCondition($item_ids, $conditon, $limit = 20, $offset = 0, $order_by = false) {
        $result = array();
        if (empty($item_ids)) {
            return $result;
        }
        $where = array();
        $join = false;
        $where[] = ['koubei.item_id', $item_ids];
        $where[] = ['koubei.status', 2];
        $where[] = [':gt','koubei.subject_id', 0];
        if (!empty($conditon)) {

            // 封测报告
            if(isset($conditon['is_pick'])) {
                $where [] = [
                    ':and', [
                        [':or', [':ne', 'koubei.auto_evaluate', $conditon['auto_evaluate']]],
                        [':or', [':ne', 'koubei.type', $conditon['type']]]
                    ]
                ];
                unset($conditon['auto_evaluate']);
                unset($conditon['type']);
                unset($conditon['is_pick']);
            }

            foreach ($conditon as $k => $v) {
                switch ($k) {
                    case 'with_pic':
                        $join = 'LEFT JOIN koubei_pic ON koubei.id = koubei_pic.koubei_id';
                        $where[] = $v ? [':notnull', 'koubei_pic.koubei_id'] : [':isnull', 'koubei_pic.koubei_id'];
                        break;
                    default:
                        $where[] = ["koubei.$k", $v];
                }
            }
        }

        $data = $this->getRows($where, 'distinct(koubei.id) as id', $limit, $offset, $order_by, $join);
        if (!empty($data)) {
            foreach($data as $v){
                $result[] = $v['id'];
            }
        }
        return $result;
    }
    
    /**
     * 根据口碑id批量获取口碑信息
     * @param array() $koubeiIds 口碑id
     * @return array()
     */
    public function getBatchKoubeiByIds($koubeiIds, $status = array(2))
    {
        $result = array();
        if (empty($koubeiIds)) {
            return $result;
        }
        $where = array();
        $where[] = ['id', $koubeiIds];
        if (!empty($status)) {
            $where[] = ['status', $status];
        }

        $fields = 'id,subject_id,rank_score,created_time,title,content,score,machine_score,rank,immutable_score,item_size,extr_info,item_id,comment_id,user_id,status,order_id,work_order,auto_evaluate,type';
        $data = $this->getRows($where,$fields);
        
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['id']] = $v;
            }
        }
        return $result;
    }
    
    /**
     * 获取商品的相关计数(商品口碑数量/用户评分/蜜粉推荐)
     */
    public function getItemInvolveNums($filed, $con){
        $nums = 0;
        if (!isset($con['item_id']) || empty($con['item_id']) || empty($filed)) {
            return $nums;
        }
        $where = array();
        $where[] = ['item_id', $con['item_id']];
        if(isset($con['status'])){
            $where[] = ['status', $con['status']];
        }
        if(isset($con['subject_id'])){
            $where[] = [':gt','subject_id',$con['subject_id']];
        }
        if(isset($con['score'])){
            $where[] = [':ge','score',$con['score']];
        }
        if(isset($con['score_in'])){
            $where[] = ['score', $con['score_in']];
        }

        // 封测报告首页报告数逻辑处理(不包含口碑数)
        if(isset($con['home_show_pick_count'])) {
            if(isset($con['type'])){
                $where[] = ['type',$con['type']];
            }
        }

        // 封测报告
        if(isset($con['is_pick'])) {
            $where [] = [
                ':and', [
                    [':or', [':ne', 'auto_evaluate', $con['auto_evaluate']]],
                    [':or', [':ne', 'type', $con['type']]]
                ]
            ];
        }else{
            if(isset($con['auto_evaluate'])){
                $where[] = ['auto_evaluate', $con['auto_evaluate']];
            }
        }

        $order_by = FALSE;
        $join = FALSE;
        if(isset($con['with_pic'])){
            $join = 'LEFT JOIN koubei_pic ON koubei.id = koubei_pic.koubei_id';
            $where[] = [':notnull', 'koubei_pic.koubei_id'];
        }
        $data = $this->getRow($where, $filed, $order_by, $join);
        if(!empty($data) && $data['nums'] > 0){
            $nums = $data['nums'];
        }
        return $nums;
    }
    
    /**
     * 查看订单商品是否有该口碑信息
     * @param  $orderId
     * @param  $itemId
     */
    public function getKoubeiByOrderItem($orderId, $itemId, $itemSize = '')
    {
        if(empty($orderId) || empty($itemId)){
            return array();
        }
        
        $where = array();
        $where[] = ['order_id', $orderId];
        $where[] = ['item_id', $itemId];
        if(!empty($itemSize)) {
            $where[] = ['item_size', $itemSize];
        }
        
        $data = $this->getRow($where);
        return $data;
    }
    
    /**
     * 保存口碑信息
     * @param array $koubeiData
     */
    public function saveKoubei($koubeiInfo)
    {
        $koubeiData = $this->insert($koubeiInfo);
        return $koubeiData;
    }
    
    /**
     * 更新帖子id到口碑中
     * @param int $koubeiId
     * @param int $subjectId
     * @return 失败 FALSE
     * @return 成功 影响行数
     */
    public function updateKoubeiBySubjectid($koubeiId,$subjectId){
        $where[] = ['id', $koubeiId];
        $setData[] = ['subject_id', $subjectId];
        $data = $this->update($setData, $where);
        return $data;
    }
    
    /**
     * 更新口碑信息
     */
    public function updateKoubeiInfoById($koubeiId, $koubeiInfo) {
        if (empty($koubeiId) || empty($koubeiInfo)) {
            return false;
        }
        $where[] = ['id', $koubeiId];
        $data = $this->update($koubeiInfo, $where);
        return $data;
    }
    
    /**
     * 删除口碑
     */
    public function deleteKoubei($id, $userId, $conditions = []){
        $where[] = ['id', $id];
        $where[] = ['user_id', $userId];
        $setData[] = ['status', 0];
        if(isset($conditions['syn_del']) && $conditions['syn_del'] == 1) {
            // 物理删除
            $affect = $this->delete($where, FALSE, 1);
        }else {
            $affect = $this->update($setData, $where);
        }
        return $affect;
    }

    /**
     * 获取一段时间内的口碑
     */
    public function getKoubeiListByTime($startTime = '', $endTime = '', $offset = 0, $limit = 10, $orderBy = '') {
        if (empty($startTime) && empty($endTime)) {
            return false;
        }
        if (!empty($startTime)) {
            $where[] = [':ge','created_time', $startTime];
        }
        if (!empty($endTime)) {
            $where[] = [':le','created_time', $endTime];
        }
        $data = $this->getRows($where, '*', $limit, $offset, $orderBy);
        return $data;
    }
    
    /**
     * 获取用户的口碑帖子
     */
    public function getKoubeisByUid($userId){
        if (empty($userId)) {
            return array();
        }
        $where = array();
        $where[] = ['user_id',$userId];
        $where[] = ['status',2];
        $result = $this->getRows($where);
        return $result;
    }
    
    /**
     * 批量删除口碑
     */
    public function deleteKoubeis($koubeiIds){
        $where[] = ['id', $koubeiIds];
        $setData[] = ['status', 0];
        $affect = $this->update($setData, $where);
        return $affect;
    }
    
    /**
     * 根据蜜芽贴id查询口碑
     * @param int $subjectId
     * @param int $itemId
     */
    public function getKoubeiBySubjectId($subjectId){
        $where = array();
        $where[] = ['subject_id', $subjectId];
        $result = $this->getRow($where);
        return $result;
    }
    
    /**
     * 获取当天口碑帖子的星级信息
     */
    public function getTodayKoubeiItemId(){
        $date = date("Y-m-d",time()-86400);
        //$date = '2016-12-08';
        $startTime = $date . " 00:00:00";
        $endTime = $date . " 23:59:59";
        $where[] = ['status', 2];
        $where[] = [':ge','created_time', $startTime];
        $where[] = [':le','created_time', $endTime];

        $groupBy = 'item_id';
        $field = "item_id";
        $data = $this->getRows($where,$field,false,0,false,false,$groupBy);
        if(!empty($data)){
            $item_ids = array_column($data, 'item_id');
        }else{
            $item_ids = [];
        }
        return $item_ids;
    }
    
    //口碑
    public function getKoubeiScoreByItemIds($itemIds){
        $date = date('Y-m-d');
        $where[] = ['status', 2];
        $where[] = ['item_id',$itemIds];
        $groupBy = 'item_id';
        $field = "group_concat(score) as score,item_id";
        $data = $this->getRows($where,$field,false,0,false,false,$groupBy);
        return $data;
    }
    
    /**
     * 更新口碑分数
     */
    public function updateKoubeiCount($koubeiIds, $num, $countType) {
        if (empty($koubeiIds)) {
            return false;
        }
    
        $sql = "update $this->tableName set $countType = $countType+$num where id in ($koubeiIds)";
        $result = $this->query($sql);
        return $result;
    }


    /*
     * 查看口碑首评(新增封测报告逻辑)
     * */
    public function checkFirstComment($order_id, $item_id, $user_id){
        $where = array();
        $where[] = ['status', 2];
        if(!empty($item_id)) {
            $where[] = ['item_id', $item_id];
        }
        if(!empty($user_id)) {
            $where[] = ['user_id', $user_id];
        }
        if(!empty($order_id)) {
            $where[] = ['order_id', $order_id];
        }
        $result = $this->count($where);
        return $result;
    }

    public function getBatchBestKoubeiIds($itemIds){
        if(!is_array($itemIds) || empty($itemIds)){
            return false;
        }
        $sql = 'SELECT
                    a.*
                FROM
                    (
                        SELECT
                            id,
                            item_id,
                            rank_score
                        FROM
                            koubei
                        WHERE
                            item_id IN ('.(implode(",",$itemIds)).')
                        AND 
                            status = 2
                        ORDER BY
                            rank_score DESC
                    ) AS a
                GROUP BY
                    a.item_id';
        $ids = $this->query($sql);
        if(!empty($ids)){
            $ids = array_column($ids, 'id');
        }
        return $ids;
    }

}