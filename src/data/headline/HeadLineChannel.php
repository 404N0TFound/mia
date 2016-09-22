<?php
namespace mia\miagroup\Data\HeadLine;

class HeadLineChannel extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_headline_channel';

    protected $mapping = array();

    /**
     * 添加栏目
     */
    public function addHeadLineChannel($channelInfo) {
        if(empty($channelInfo)){
            return false;
        }
        $data = $this->insert($channelInfo);
        return $data;
    }
    
    /**
     * 更新栏目
     */
    public function updateHeadLineChannel($channelId, $updateData) {
        if(empty($channelId) || empty($updateData)){
            return false;
        }
        $where = array();
        $where[] = ['id', $channelId];
        $data = $this->update($updateData, $where);
        return $data;
    }
    
    /**
     * 删除栏目
     */
    public function deleteHeadLineChannel($channelId) {
        if (empty($channelId)) {
            return false;
        }
        $where = array();
        $where[] = ['id', $channelId];
        
        $data = $this->delete($where);
        return $data;
    }
    
    /**
     * 根据栏目ids获取头条栏目
     */
    public function getHeadLineChannelByIds($channelIds = array(), $status = array(1)) {
        $result = array();
        $where = array();
        if (!empty($channelIds)) {
            $where[] = ['id', $channelIds];
        }
        $where[] = ['status', $status];
        $orderBy = 'sort desc';
        $data = $this->getRows($where, '*', 0, 0, $orderBy);
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['id']] = $v;
            }
        }
        return $result;
    }
    
    /**
     * 设置栏目为上、下线状态
     * @param array $channelIds
     * @param int $setStatus（0为下线，1位上线）
     * @return boolean
     */
    public function setChannelStatusByIds($channelIds, $setStatus = 1)
    {
        if (empty($channelIds)) {
            return false;
        }
        $setData[] = ['status',$setStatus];
        $where[] = ['id',$channelIds];
    
        $data = $this->update($setData,$where);
        return $data;
    }
    
}