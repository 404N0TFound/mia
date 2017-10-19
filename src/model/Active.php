<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Active\Active as ActiveData;
use \mia\miagroup\Data\Active\ActiveSubject as ActiveSubjectData;
use \mia\miagroup\Data\Active\ActiveSubjectRelation as RelationData;
use \mia\miagroup\Data\Active\ActivePrizeRecordData as ActivePrizeData;

class Active {
    protected $activeData = null;
    protected $activeSubjectData = null;
    protected $relationData = null;
    protected $activePrizeData = null;

    public function __construct() {
        $this->activeData = new ActiveData();
        $this->activeSubjectData = new ActiveSubjectData();
        $this->relationData = new RelationData();
        $this->activePrizeData = new ActivePrizeData();
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
                    // 消消乐活动规则引导处理
                    if(isset($extInfo['is_xiaoxiaole']) && !empty($extInfo['is_xiaoxiaole'])) {
                        // 消消乐活动标识
                        $activeArr[$active['id']]['is_xiaoxiaole'] = $extInfo['is_xiaoxiaole'];
                        // 消消乐活动设置
                        if(!empty($extInfo['xiaoxiaole_setting'])) {
                            $xiaoxiaole_setting = $extInfo['xiaoxiaole_setting'];
                            // 消消乐活动背景图
                            $activeArr[$active['id']]['back_image'] = $xiaoxiaole_setting['back_image'];
                            // 消消乐活动规则文案
                            $activeArr[$active['id']]['active_regular'] = $xiaoxiaole_setting['active_regular'];
                        }
                        // 消消乐预设
                        if(!empty($extInfo['xiaoxiaole_pre_setting'])) {
                            $show_tab_list = [];
                            $xiaoxiaole_pre_setting = $extInfo['xiaoxiaole_pre_setting'];
                            foreach ($xiaoxiaole_pre_setting as $k => $tabInfo) {
                                $now = strtotime("now");
                                $start = strtotime($tabInfo['start_time']);
                                $end = strtotime($tabInfo['end_time']);
                                if($start > $now || $end < $now) {
                                    continue;
                                }
                                $show_tab_list[$k] = $tabInfo;
                            }
                            $activeArr[$active['id']]['active_tab'] = array_values($show_tab_list);
                        }
                        // 消消乐奖项设置
                        $activeArr[$active['id']]['active_award'] = $extInfo['prize_list'];
                        // 消消乐活动配置
                        $xiaoxiaole_config = \F_Ice::$ins->workApp->config->get('busconf.active.xiaoxiaole.guide_init');
                        // 消消乐活动背景颜色
                        $activeArr[$active['id']]['back_color'] = $xiaoxiaole_config['back_color'];
                        // 消消乐活动文案链接
                        $activeArr[$active['id']]['active_regular_link'] = $xiaoxiaole_config['active_regular_link'];
                        // 消消乐活动日期文字颜色
                        $activeArr[$active['id']]['date_color'] = $xiaoxiaole_config['date_color'];
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

    /**
     * 删除帖子活动关联关系
     * */
    public function delSubjectActiveRelation($relationData) {
        $data = $this->relationData->delSubjectActiveRelation($relationData);
        return $data;
    }

    /*
     * 获取活动奖励列表
     * */
    public function getActiveWinPrizeRecord($active_id, $user_id, $conditions = [])
    {
        $data = $this->activePrizeData->getActiveWinPrizeRecord($active_id, $user_id, $conditions);
        return $data;
    }

    /*
     * 获取活动用户发帖数
     * */
    public function getActiveUserSubjectList($active_id, $user_id, $page = 1, $limit = 20, $conditions = [])
    {
        $data = $this->relationData->getActiveUserSubjectList($active_id, $user_id, $page, $limit, $conditions);
        return $data;
    }

    /*
     * 获取活动发帖用户排行
     * */
    public function getActiveSubjectsRank($active_id)
    {
        $data = $this->relationData->getActiveSubjectsRank($active_id);
        return $data;
    }

    /*
     * 获取活动用户发帖数
     * */
    public function getSubjectCountsByActive($active_id, $user_id = 0, $conditions = [])
    {
        $data = $this->relationData->getSubjectCountsByActive($active_id, $user_id, $conditions);
        return $data;
    }

    /*
     * 活动关联帖更新审核状态
     * */
    public function updateActiveSubjectVerify($setData, $id)
    {
        $data = $this->relationData->updateActiveSubjectVerify($setData, $id);
        return $data;
    }

    /*
     * 新增活动奖励发放记录
     * */
    public function addActivePrizeRecord($insertData)
    {
        $data = $this->activePrizeData->addActivePrizeRecord($insertData);
        return $data;
    }
}
