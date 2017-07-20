<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Active\Active as ActiveData;
use \mia\miagroup\Data\Active\ActiveSubject as ActiveSubjectData;
use \mia\miagroup\Data\Active\ActiveSubjectRelation as RelationData;

class Active {
    protected $activeData = null;
    protected $activeSubjectData = null;
    protected $relationData = null;

    public function __construct() {
        $this->activeData = new ActiveData();
        $this->activeSubjectData = new ActiveSubjectData();
        $this->relationData = new RelationData();
    }
    
    //活动列表第一页的取所有进行中和未开始活动
    public function getFirstPageActive($status = array(1), $activeStatus){
        $activeRes = array();
        foreach($activeStatus as $value){
            $condition = array();
            $condition['active_status'] = $value;//活动状态（进行中、未开始）
            $activeRes += $this->getActiveList(false, 0, $status,$condition);
        }
        return $activeRes;
    }
    
    //获取活动列表
    public function getActiveList($page, $limit, $status = array(1), $condition){
        $activeRes = $this->activeData->getBatchActiveInfos($page, $limit, $status,$condition);
        $activeArr = array();
        if(!empty($activeRes)){
        foreach($activeRes as $active){
                $activeArr[$active['id']] = $active;
                if(!empty($active['ext_info'])){
                    $extInfo = json_decode($active['ext_info'],true);
                    if(!empty($extInfo['labels'])){
                        $activeArr[$active['id']]['labels'] = $extInfo['labels'];
                        $activeArr[$active['id']]['label_titles'] = implode(',',array_column($extInfo['labels'], 'title'));
                    }
                    if(!empty($extInfo['image'])){
                        $activeArr[$active['id']]['top_img'] = $extInfo['image'];
                        $activeArr[$active['id']]['top_img_url'] = $active['top_img'];
                    }
                    if(isset($extInfo['cover_img']) && !empty($extInfo['cover_img'])){
                        $activeArr[$active['id']]['cover_img'] = $extInfo['cover_img'];
                    }else{
                        $activeArr[$active['id']]['cover_img'] = $extInfo['image'];
                    }
                    if(isset($extInfo['icon_img']) && !empty($extInfo['icon_img'])){
                        $activeArr[$active['id']]['icon_img'] = $extInfo['icon_img'];
                    }
                    if(isset($extInfo['image_count_limit']) && !empty($extInfo['image_count_limit'])){
                        $activeArr[$active['id']]['image_count_limit'] = $extInfo['image_count_limit'];
                    }
                    if(isset($extInfo['text_lenth_limit']) && !empty($extInfo['text_lenth_limit'])){
                        $activeArr[$active['id']]['text_lenth_limit'] = $extInfo['text_lenth_limit'];
                    }
                }
                //如果传入了活动的进行状态，就直接返回改状态
                if(isset($condition['active_status'])){
                    $activeArr[$active['id']]['active_status'] = $condition['active_status'];
                }else{
                    //如果没有传入，活动的进行状态就根据开始结束时间判断
                    $activeStatus = '';
                    $currentTime = date('Y-m-d H:i:s');
                    if($active['start_time']<=$currentTime && $active['end_time']>=$currentTime){
                        $activeStatus = 2;
                    }elseif($active['end_time']<$currentTime){
                        $activeStatus = 3;
                    }elseif($active['start_time']>$currentTime){
                        $activeStatus = 1;
                    }
                    $activeArr[$active['id']]['active_status'] = $activeStatus;
                }
            }
        }
        return $activeArr;
    }
    //创建活动
    public function addActive($insertData){
        $data = $this->activeData->addActive($insertData);
        return $data;
    }
    
    //编辑活动
    public function updateActive($activeInfo, $activeId){
        $data = $this->activeData->updateActive($activeInfo, $activeId);
        return $data;
    }
    
    //删除活动
    public function deleteActive($activeId, $operator){
        $affect = $this->activeData->delete($activeId, $operator);
        return $affect;
    }
    
    
    //批量获取活动下图片计数（图片数，发帖用户数）（数据导入关联表后可以改方法）
    public function getBatchActiveSubjectCounts($activeIds) {
        $data = $this->relationData->getBatchActiveSubjectCounts($activeIds);
        return $data;
    }
    
    /**
     * 根据帖子id批量获取活动帖子信息
     */
    public function getActiveSubjectBySids($subjectIds, $status) {
        $subjectArr = $this->relationData->getActiveSubjectBySids($subjectIds, $status);
        return $subjectArr;
    }
    
    /**
     * 根据活动id批量获取帖子信息
     */
    public function getBatchActiveSubjects($activeId, $type = 'all', $page, $limit) {
        if (empty($activeId)) {
            return array();
        }
        //如果没有session信息，直接返回翻页结果
        if (empty(\F_Ice::$ins->runner->request->ext_params['dvc_id'])) {
            $data = $this->relationData->getBatchActiveSubjects($activeId, $type, $page, $limit);
            return $data;
        }
        $redis_key =  sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.activeKey.active_subject_read_session.key'), $activeId, $type, \F_Ice::$ins->runner->request->ext_params['dvc_id']);
        $redis = new \mia\miagroup\Lib\Redis();
        //如果是第一页，刷新已读缓存
        if ($page == 1) {
            $redis->del($redis_key);
        }
        $data = $this->relationData->getBatchActiveSubjects($activeId, $type, $page, $limit);
        if (empty($data)) {
            return array();
        }
        //获取已读数据
        $read_data = [];
        $read_count = 0;
        if ($redis->exists($redis_key)) {
            $read_count = $redis->llen($redis_key);
            $read_data = $redis->lrange($redis_key, 0, $read_count);
        }
        //去重
        $diff_data = array_diff($data, $read_data);
        if (empty($diff_data)) {
            return array();
        }
        //记录本次已读数据
        if ($read_count < 1000) {
            foreach ($diff_data as $v) {
                $r = $redis->lpush($redis_key, $v);
            }
            $redis->expire($redis_key, \F_Ice::$ins->workApp->config->get('busconf.rediskey.activeKey.active_subject_read_session.expire_time'));
        }
        return $diff_data;
    }
    
    /**
     * 新增活动帖子关联数据
     */
    public function addActiveSubjectRelation($insertData){
        $data = $this->relationData->addActiveSubjectRelation($insertData);
        return $data;
    }
    
    /**
     * 更新活动帖子
     */
    public function upActiveSubject($relationData,$relationId){
        $data = $this->relationData->updateActiveSubjectRelation($relationData,$relationId);
        return $data;
    }

}
