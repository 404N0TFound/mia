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
    public function getKoubeiIdsByItemIds($itemIds, $limit = 20, $offset = 0, $orderBy = false) {
        $result = array();
        if (empty($itemIds)) {
            return $result;
        }
        $where = array();
        $where[] = ['item_id', $itemIds];
        $where[] = ['status', 2];
        $where[] = [':gt','subject_id',0];
        
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
    public function getKoubeiWithPicByItemIds($itemIds, $limit = 20, $offset = 0, $orderBy = false) {
        $result = array();
        if (empty($itemIds)) {
            return $result;
        }
        $where = array();
        $where[] = ['koubei.item_id', $itemIds];
        $where[] = ['koubei.status', 2];
        $where[] = [':gt','koubei.subject_id',0];
        $where[] = [':notnull', 'koubei_pic.koubei_id'];
        $data = $this->getRows($where,'koubei.id as id',$limit,$offset,$orderBy, 'LEFT JOIN koubei_pic ON koubei.id = koubei_pic.koubei_id');
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
    
        $fields = 'id,subject_id,rank_score,created_time,title,content,score,rank,immutable_score,item_size,extr_info,item_id,user_id,status';
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
        
        $data = $this->getRow($where,$filed);
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
    public function getKoubeiByOrderItem($orderId, $itemId)
    {
        if(empty($orderId) || empty($itemId)){
            return array();
        }
        
        $where = array();
        $where[] = ['order_id', $orderId];
        $where[] = ['item_id', $itemId];
        
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
    public function delete($id, $userId){
        $where[] = ['id', $id];
        $where[] = ['user_id', $userId];
        $setData[] = ['status', 0];
        $affect = $this->update($setData, $where);
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
        $result = $this->getRows($where);
        return $result;
    }
    
    /**
     * 获取当天口碑帖子的星级信息
     */
    public function getTodayKoubeiItemId(){
        $date = date('Y-m-d');
        $where[] = ['status', 2];
        $where[] = [":literal","date(created_time) = '$date'"];
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
     * 查看口碑首评
     * */
    public function checkFirstComment($order_id, $item_id){
        if (empty($item_id) || empty($order_id)) {
            return false;
        }
        $where = array();
        $where[] = ['status', 2];
        $where[] = ['item_id', $item_id];
        $result = $this->getRows($where);
        return count($result);
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