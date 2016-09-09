<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\HeadLine\HeadLineChannel;
use \mia\miagroup\Data\HeadLine\HeadLineChannelContent;
use \mia\miagroup\Data\HeadLine\HeadLineTopic;
use \mia\miagroup\Data\HeadLine\HeadLineUserCategory;

class HeadLine {

    private $headLineChannelData;
    //private $headLineChannelContentData;
    private $headLineTopicData;
    //private $headLineUserCategoryData;

    public function __construct() {
        $this->headLineChannelData = new HeadLineChannel();
        //$this->headLineChannelContentData = new HeadLineChannelContent();
        $this->headLineTopicData = new HeadLineTopic();
        //$this->headLineUserCategoryData = new HeadLineUserCategory();
    }
    
    /**
     * 根据头条栏目获取头条
     */
    public function getHeadLinesByChannel($channelId) {
        //@chaojiang
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
    public function addOperateHeadLine($headLineInfo) {
        //@donghui
        //归纳头条所有type，校验头条type
        //归纳所有ext_info，校验ext_info
    }
    
    /**
     * 编辑运营头条
     */
    public function editOperateHeadLine($id, $headLineInfo) {
        //@donghui
        //只能编辑ext_info、page、row、begin_time、end_time
    }
    
    /**
     * 删除运营头条
     */
    public function delOperateHeadLine($id) {
        //@donghui
    }
    
    /**
     * 获取头条专题
     */
    public function getHeadLineTopics($topicIds, $status = array(1)) {
        if(empty($topicIds)){
            return array();
        }
        $data = $this->headLineTopicData->getHeadLineTopicByIds($channelIds, $status);
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