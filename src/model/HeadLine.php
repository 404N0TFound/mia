<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\HeadLine\HeadLineChannel;
use \mia\miagroup\Data\HeadLine\HeadLineChannelContent;
use \mia\miagroup\Data\HeadLine\HeadLineTopic;
use \mia\miagroup\Data\HeadLine\HeadLineUserCategory;

class HeadLine {

    private $headLineChannelData;
    private $headLineChannelContentData;
    private $headLineTopicData;
    private $headLineUserCategoryData;

    public function __construct() {
        $this->headLineChannelData = new HeadLineChannel();
        $this->headLineChannelContentData = new HeadLineChannelContent();
        $this->headLineTopicData = new HeadLineTopic();
        $this->headLineUserCategoryData = new HeadLineUserCategory();
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
    public function getHeadLineChannels() {
        //@donghui
    }
    
    /**
     * 新增头条栏目
     */
    public function addHeadLineChannel($channelInfo) {
        //@donghui
    }
    
    /**
     * 更新头条栏目
     */
    public function updateHeadLineChannel($channelId, $updateData) {
        //@donghui
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
     * 新增头条专题
     */
    public function addHeadLineTopic($topicInfo) {
        //@donghui
    }
    
    /**
     * 编辑头条专题
     */
    public function editHeadLineTopic($id, $topicInfo) {
        //@donghui
    }
    
    /**
     * 删除头条专题
     */
    public function delHeadLineTopic($id) {
        //@donghui
    }
}