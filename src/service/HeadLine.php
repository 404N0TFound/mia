<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\HeadLine as HeadLineModel;
use Qiniu\Auth;
use mia\miagroup\Remote\RecommendedHeadline as HeadlineRemote;
class HeadLine extends \mia\miagroup\Lib\Service {
    
    public $headLineModel;
    public $headlineRemote;
    public function __construct() {
        parent::__construct();
        $this->headLineModel = new HeadLineModel();
        $this->headlineRemote = new HeadlineRemote();
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
    public function getHomePageHeadLines()
    {
        $channelId = 1;
        $data = $this->headLineModel->getHeadLinesByChannel($channelId);
        return $this->succ($data);
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
    public function headLineReadNotify($currentUid, $subjectId, $channelId = 0)
    {
        if(empty($currentUid) || empty($subjectId)){
            return $this->error(500);
        }
        $data = $this->headlineRemote->headlineRead($currentUid, $subjectId, $channelId);
        return $this->succ($data);
    }
    
    /**
     * 获取头条的相关头条
     */
    public function getRelatedHeadLines($subjectId, $currentUid, $channelId = 0)
    {
        if(empty($currentUid) || empty($subjectId)){
            return $this->error(500);
        }
        $data = $this->headlineRemote->headlineRelate($subjectId, $currentUid,$channelId);
        return $this->succ($data);
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
    public function addOperateHeadLine($headLineInfo)
    {
        if(empty($headLineInfo) || !is_array($headLineInfo)){
            return $this->error(500);
        }
        if(!in_array($headLineInfo['relation_type'], [1,2,3,4,5,6])){
            return $this->error(500);
        }
        $data = $this->headLineModel->addOperateHeadLine($headLineInfo);
        return $this->succ($data);
    }
    
    /**
     * 编辑运营头条
     */
    public function editOperateHeadLine($id, $headLineInfo) {
        //@donghui
        //只能编辑ext_info、page、row、begin_time、end_time
        if(empty($id) || empty($headLineInfo) || !is_array($headLineInfo)){
            return $this->error(500);
        }
        $data = $this->headLineModel->editOperateHeadLine($id,$headLineInfo);
        return $this->succ($data);
    }
    
    /**
     * 删除运营头条
     */
    public function delOperateHeadLine($id) {
        if(empty($id)){
            return $this->error(500);
        }
        $data = $this->headLineModel->delOperateHeadLine($id);
        return $this->succ($data);
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