<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\HeadLine as HeadLineModel;
use Qiniu\Auth;

class HeadLine extends \mia\miagroup\Lib\Service {
    
    public $headLineModel;
    
    public function __construct() {
        parent::__construct();
        $this->$headLineModel = new HeadLineModel();
    }
    
    /**
     * 根据头条栏目获取头条
     */
    public function getHeadLinesByChannel($channelId, $page=1, $action = 'init',$currentUid = 0,$subjectIds=[]) {
        //@chaojiang
        //先调用服务
        //调用model
        //merge数据
    }
    
    /**
     * 获取首页轮播头条
     */
    public function getHomePageHeadLines() {
        //@chaojiang
    }
    
    /**
     * 获取头条栏目
     */
    public function getHeadLineChannels() {
        //@donghui
    }
    
    /**
     * 头条内容查看告知
     */
    public function headLineReadNotify($currentUid, $subjectId, $channelId = 0) {
        //@chaojiang
    }
    
    /**
     * 获取头条的相关头条
     */
    public function getRelatedHeadLines($subjectId, $channelId = 0, $currentUid = 0) {
        //@chaojiang
    }
    
    /**
     * 新增头条栏目
     */
    public function addHeadLineChannel($channelInfo) {
        //@donghui
    }
    
    /**
     * 下线头条栏目
     */
    public function offLineHeadLineChannel($channelId) {
        //@donghui
    }
    
    /**
     * 上线头条栏目
     */
    public function onLineHeadLineChannel($channelId) {
        //@donghui
    }
    
    /**
     * 编辑头条栏目
     */
    public function editHeadLineChannel($channelId, $channelInfo) {
        //@donghui
    }
    
    /**
     * 新增运营头条
     */
    public function addOperateHeadLine($headLineInfo) {
        //@donghui
        //归纳头条所有type，校验头条type
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