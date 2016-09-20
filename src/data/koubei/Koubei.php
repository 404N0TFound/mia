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
        $where[] = ['status', 2];
    
        $fields = 'id,subject_id,rank_score,created_time,title,content,score,rank,item_size';
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

}