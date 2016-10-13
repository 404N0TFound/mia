<?php
namespace mia\miagroup\Data\HeadLine;

class HeadLineTopic extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_headline_topic';

    protected $mapping = array();

    /**
     * 添加专题
     */
    public function addHeadLineTopic($topicInfo) {
        if(empty($topicInfo)){
            return false;
        }
        $data = $this->insert($topicInfo);
        return $data;
    }
    
    /**
     * 编辑专题
     */
    public function updateHeadLineTopic($topicId, $updateData) {
        if(empty($topicId) || empty($updateData)){
            return false;
        }
        $where = array();
        $where[] = ['id', $topicId];
        $data = $this->update($updateData, $where);
        return $data;
    }
    
    /**
     * 删除专题
     */
    public function deleteHeadLineTopic($topicId) {
        if (empty($topicId)) {
            return false;
        }
        $where = array();
        $where[] = ['id', $topicId];
        
        $data = $this->delete($where);
        return $data;
    }
    
    /**
     * 根据专题ids获取专题
     */
    public function getHeadLineTopicByIds($topicIds, $status = array(1)) {
        $result = array();
        if (empty($topicIds)) {
            return $result;
        }
        $where = array();
        $where[] = ['id', $topicIds];
        $where[] = ['status', $status];
    
        $orderBy = "id desc ";
        
        $data = $this->getRows($where,'*',false,0,$orderBy);
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['id']] = $v;
            }
        }
        return $result;
    }
    
    /**
     * 设置栏目为上、下线状态
     * @param array $topicIds
     * @param int $setStatus（0为下线，1位上线）
     * @return boolean
     */
    public function setTopicStatusByIds($topicIds, $setStatus = 1)
    {
        if (empty($topicIds)) {
            return false;
        }
        $setData[] = ['status',$setStatus];
        $where[] = ['id',$topicIds];
    
        $data = $this->update($setData,$where);
        return $data;
    }
}