<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\HeadLine\HeadLineChannel;
use \mia\miagroup\Data\HeadLine\HeadLineChannelContent;
use \mia\miagroup\Data\HeadLine\HeadLineTopic;

class HeadLine {

    private $headLineChannelData;
    private $headLineChannelContentData;
    private $headLineTopicData;

    public function __construct() {
        $this->headLineChannelData = new HeadLineChannel();
        $this->headLineChannelContentData = new HeadLineChannelContent();
        $this->headLineTopicData = new HeadLineTopic();
    }
    
    /**
     * 根据头条栏目获取头条
     */
    public function getHeadLinesByChannel($channelId,$page=1) {
        $data = $this->headLineChannelContentData->getHeadLinesByChannel($channelId,$page);
        if (!empty($data)) {
            //以row为key重新拼装
            $sortedRowData = array();
            foreach ($data as $v) {
                $sortedRowData[$v['row']][] = $v;
            }
            foreach ($sortedRowData as $rowData) {
                if (count($rowData) > 1) {
                    //如果位置有重复，随机出一个
                    $randkey = array_rand($rowData, 1);
                    unset($rowData[$randkey]);
                    foreach ($rowData as $v) {
                        $key = $v['relation_id'] . '_' . $v['relation_type'];
                        unset($data[$key]);
                    }
                }
            }
        }
        
        return $data;
    }
    
    /**
     * 获取头条栏目
     */
    public function getHeadLineChannels($channelIds, $status = array(1)) {
        if(empty($channelIds)){
            return array();
        }
        $data = $this->headLineChannelData->getHeadLineChannelByIds($channelIds, $status);
        return $data;
    }
    
    /**
     * 新增头条栏目
     */
    public function addHeadLineChannel($channelInfo) {
        $data = $this->headLineChannelData->addHeadLineChannel($channelInfo);
        return $data;
    }
    
    /**
     * 更新头条栏目
     */
    public function updateHeadLineChannel($channelId, $channelInfo) {
        $data = $this->headLineChannelData->updateHeadLineChannel($channelId, $channelInfo);
        return $data;
    }
    
    /**
     * 设置栏目的上下线状态
     */
    public function setChannelStatusByIds($channelIds, $status = 1) {
        $data = $this->headLineChannelData->setChannelStatusByIds($channelIds, $status);
        return $data;
    }
    
    /**
     * 删除栏目
     */
    public function deleteHeadLineChannel($channelId) {
        $data = $this->headLineChannelData->deleteHeadLineChannel($channelId);
        return $data;
    }
    
    /**
     * 新增运营头条
     */
    public function addOperateHeadLine($headLineInfo)
    {
        //@donghui
        //归纳头条所有type，校验头条type
        //归纳所有ext_info，校验ext_info
        $data = $this->headLineChannelContentData->addOperateHeadLine($headLineInfo);
        return $data;
    }
    
    /**
     * 编辑运营头条
     */
    public function editOperateHeadLine($id, $headLineInfo)
    {
        //@donghui
        //只能编辑ext_info、page、row、begin_time、end_time
        $data = $this->headLineChannelContentData->updateHeadlineById($id,$headLineInfo);
        return $data;
    }
    
    /**
     * 删除运营头条
     */
    public function delOperateHeadLine($id)
    {
        $data = $this->headLineChannelContentData->delHeadlineById($id);
        return $data;
    }
    
    /**
     * 获取头条专题
     */
    public function getHeadLineTopics($topicIds, $status = array(1)) {
        if(empty($topicIds)){
            return array();
        }
        $data = $this->headLineTopicData->getHeadLineTopicByIds($topicIds, $status);
        if(!empty($data)){
            foreach ($data as $key=> $topic){
                $topicInfo = json_decode($topic['topic_info'], true);
                $data[$key]['id'] = $topic['id'];
                $data[$key]['title'] = $topicInfo['title'];
            }
        }
        return $data;
    }
    /**
     * 新增头条专题
     */
    public function addHeadLineTopic($topicInfo) {
        $data = $this->headLineTopicData->addHeadLineTopic($topicInfo);
        return $data;
    }
    
    /**
     * 编辑头条专题
     */
    public function editHeadLineTopic($topicId, $topicInfo) {
        $data = $this->headLineTopicData->updateHeadLineTopic($topicId, $topicInfo);
        return $data;
    }
    
    /**
     * 删除头条专题
     */
    public function delHeadLineTopic($topicId) {
        $data = $this->headLineTopicData->deleteHeadLineTopic($topicId);
        return $data;
    }
    
    /**
     * 设置专题的上下线状态
     */
    public function setTopicStatusByIds($topicIds, $status = 1) {
        $data = $this->headLineTopicData->setTopicStatusByIds($topicIds, $status);
        return $data;
    }
}