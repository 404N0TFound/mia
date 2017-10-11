<?php
namespace mia\miagroup\Service;

use \F_Ice;
use mia\miagroup\Model\Active as ActiveModel;
use mia\miagroup\Model\Item;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Service\Label as LabelService;
use mia\miagroup\Util\NormalUtil;
use mia\miagroup\Service\ums\Active as UmsActiveService;
use mia\miagroup\Service\Item as ItemService;
use mia\miagroup\Service\User as UserService;

class Active extends \mia\miagroup\Lib\Service {

    public $activeModel = null;

    public function __construct() {
        parent::__construct();
        $this->activeModel = new ActiveModel();
    }
    
    /**
     * 获取活动列表
     */
    public function getActiveList($page, $limit, $fields = array('count'), $status = [1])
    {
        $activeRes = array();
        if($page == 1){
            $activeStatus = array(2,1);
            $activeInfos = $this->activeModel->getFirstPageActive($status,$activeStatus);
        }else{
            $page = $page - 1;
            $activeStatus = array('active_status'=>3);
            $activeInfos = $this->activeModel->getActiveList($page, $limit, $status,$activeStatus);
        }
    
        if (empty($activeInfos)) {
            return $this->succ(array());
        }
    
        $activeIds = array_keys($activeInfos);
        $activeCount = $this->activeModel->getBatchActiveSubjectCounts($activeIds);
    
        foreach($activeInfos as $activeInfo){
            $tmp = $activeInfo;
            $tmp['img_nums'] = 0;
            $tmp['user_nums'] = 0;
            if (in_array('count', $fields)) {
                $tmp['img_nums'] = isset($activeCount[$activeInfo['id']]['img_nums']) ? $activeCount[$activeInfo['id']]['img_nums'] : 0 ;
                $tmp['user_nums'] = isset($activeCount[$activeInfo['id']]['user_nums']) ? $activeCount[$activeInfo['id']]['user_nums'] : 0 ;
            }
            $activeRes[] = $tmp;
        }
        return $this->succ($activeRes);
    }

    /**
     * 获取单条活动信息
     */
    public function getSingleActiveById($activeId, $status = array(1), $fields = array('share_info')) {
        $condition = array('active_ids' => array($activeId));
        $activeRes = array();
        // 获取活动基本信息
        $activeInfos = $this->activeModel->getActiveList(1, 1, $status, $condition);
        if (empty($activeInfos[$activeId])) {
            return $this->succ(array());
        }
        $activeRes = $activeInfos[$activeId];
        
        if (in_array('share_info', $fields)) {
            // 分享内容
            $shareConfig = \F_Ice::$ins->workApp->config->get('busconf.active');
            $shareDefault = $shareConfig['defaultShareInfo']['active'];
            $share = $shareConfig['activeShare'];
            $shareTitle = $shareDefault['title'];
            $shareContent = "【".$activeRes['title']."】".$activeRes['introduction'];
            $shareDesc = sprintf($shareDefault['desc'],$shareContent);
            $h5Url = sprintf($shareDefault['wap_url'], $activeRes['id']);
            if (empty($shareImage)) {
                $shareImage = $shareDefault['img_url'];
            }
        
            // 替换搜索关联数组
            $replace = array('{|title|}' => $shareTitle, '{|desc|}' => $shareDesc, '{|image_url|}' => $shareImage, '{|wap_url|}' => $h5Url);
            // 进行替换操作
            foreach ($share as $keys => $sh) {
                $shareInfo[] = NormalUtil::buildGroupShare($sh, $replace);
            }
            $activeRes['share_info'] = $shareInfo;
        }
        
        return $this->succ($activeRes);
    }
    
    /**
     * 获取当前在线活动
     */
    public function getCurrentActive($limit=0) {
        $condition = array('active_status' => 2);
        $activeRes = array();
        // 获取所有活动基本信息
        $activeArr = $this->activeModel->getActiveList(0, false, array(1), $condition);
        if (empty($activeArr)) {
            return $this->succ($activeRes);
        }

        //如果传入数量，则需要过滤掉没有小图的活动，用到发帖页
        if($limit > 0){
            foreach($activeArr as $key=>$active){
                if(!isset($active['icon_img'])){
                    continue;
                }
                $activeRes[$key] = $active;
            }
            $activeRes = array_slice($activeRes,0,$limit);
        }else{
            //如果没有传入数量，则获取所有的活动，无需过滤没有小图的
            $activeRes = $activeArr;
        }
        
        return $this->succ($activeRes);
    }

    /**
     * 创建活动（用于后台活动发布）
     */
    public function addActive($activeInfo) {
        if (empty($activeInfo)) {
            return $this->error(500);
        }
        $extInfo = array();
        //参加活动的标签
        if (!empty($activeInfo['label_titles'])) {
            $labelService = new LabelService();
            $labelInfoArr = array();
            $labels = array();
            foreach($activeInfo['label_titles'] as $labelTitle){
                $labelId = $labelService->addLabel($labelTitle)['data'];
                $labelInfoArr['id'] = $labelId;
                $labelInfoArr['title'] = $labelTitle;
                $labels[] = $labelInfoArr;
            }
            if(!empty($labels)){
                $extInfo['labels']= $labels;
                unset($activeInfo['label_titles']);
            }
        }
        if(isset($activeInfo['image_count_limit'])){
            $extInfo['image_count_limit']= $activeInfo['image_count_limit'];
            unset($activeInfo['image_count_limit']);
        }
        
        if(isset($activeInfo['text_lenth_limit'])){
            $extInfo['text_lenth_limit']= $activeInfo['text_lenth_limit'];
            unset($activeInfo['text_lenth_limit']);
        }

        if(!empty($activeInfo['image_info'])){
            $extInfo['image']= $activeInfo['image_info'];
            unset($activeInfo['image_info']);
        }
        if(!empty($activeInfo['cover_img_info'])){
            $extInfo['cover_img']= $activeInfo['cover_img_info'];
            unset($activeInfo['cover_img_info']);
        }
        if(!empty($activeInfo['icon_img_info'])){
            $extInfo['icon_img']= $activeInfo['icon_img_info'];
            unset($activeInfo['icon_img_info']);
        }
        
        $activeInfo['ext_info'] = json_encode($extInfo);
        $insertActiveRes = $this->activeModel->addActive($activeInfo);
        
        if($insertActiveRes > 0){
            $insertId = $insertActiveRes;
            $updata = array('asort' => $insertId);
            $this->activeModel->updateActive($updata, $insertId);
            return $this->succ(true);
        }else{
            return $this->succ(false);
        }
    }

    
    /**
     * 更新活动信息（用于后台活动编辑）
     */
    public function updateActive($activeId, $activeInfo) {
        if (empty($activeId) || empty($activeInfo)) {
            return $this->succ(false);
        }
        //更新标签
        if (!empty($activeInfo['label_titles'])) {
            $labelService = new LabelService();
            $labelInfoArr = array();
            $labels = array();
            foreach($activeInfo['label_titles'] as $labelTitle){
                $labelId = $labelService->addLabel($labelTitle)['data'];
                $labelInfoArr['id'] = $labelId;
                $labelInfoArr['title'] = $labelTitle;
                $labels[] = $labelInfoArr;
            }
        }
        $extInfo = array();
        if(!empty($labels)){
            $extInfo['labels']= $labels;
            unset($activeInfo['label_titles']);
        }
        if(!empty($activeInfo['image_info'])){
            $extInfo['image']= $activeInfo['image_info'];
            unset($activeInfo['image_info']);
        }
        
        if(!empty($activeInfo['cover_img_info'])){
            $extInfo['cover_img']= $activeInfo['cover_img_info'];
            unset($activeInfo['cover_img_info']);
        }
        
        if(!empty($activeInfo['icon_img_info'])){
            $extInfo['icon_img']= $activeInfo['icon_img_info'];
            unset($activeInfo['icon_img_info']);
        }
        
        if(isset($activeInfo['image_count_limit'])){
            $extInfo['image_count_limit']= $activeInfo['image_count_limit'];
            unset($activeInfo['image_count_limit']);
        }
        if(isset($activeInfo['text_lenth_limit'])){
            $extInfo['text_lenth_limit']= $activeInfo['text_lenth_limit'];
            unset($activeInfo['text_lenth_limit']);
        }
        
        $activeInfo['ext_info'] = json_encode($extInfo);
        $this->activeModel->updateActive($activeInfo, $activeId);
        return $this->succ(true);
    }
    
    
    /**
     * 删除活动（用于后台活动管理删除活动）
     */
    public function deleteActive($activeId,$oprator){
        if(empty($activeId)){
            return $this->error(500);
        }
        $result = $this->activeModel->deleteActive($activeId,$oprator);
        return $this->succ($result);
    }
    
    /**
     * 新增活动帖子关联数据
     */
    public function addActiveSubjectRelation($relationSetInfo){
        if (empty($relationSetInfo)) {
            return $this->error(500);
        }
        $insertActiveRes = $this->activeModel->addActiveSubjectRelation($relationSetInfo);
        
        if($insertActiveRes > 0){
            return $this->succ(true);
        }else{
            return $this->succ(false);
        }
    }
    
    /**
     * 根据帖子id获取活动帖子信息
     */
    public function getActiveSubjectBySids($subjectIds, $status=array(1)) {
        if(empty($subjectIds)){
            return $this->error(500);
        }
        $subjectArrs = $this->activeModel->getActiveSubjectBySids($subjectIds, $status);
        return $this->succ($subjectArrs);
    }
    
    /**
     * 获取某活动下的所有/精华帖子
     */
    public function getActiveSubjects($activeId, $type='all', $currentId = 0, $page=1, $limit=20){
        $data = array('subject_lists'=>array());
        if($type == 'recommend'){
            //如果是结束活动的精华帖子，需要跟进行中的进行区分
            $activeInfo = $this->getSingleActiveById($activeId)['data'];
            if($activeInfo['end_time'] < date('Y-m-d H:i:s',time())){
                $type = 'active_over';
            }
        }
        $subjectIds = $this->activeModel->getBatchActiveSubjects($activeId, $type, $page, $limit);
        if(empty($subjectIds)){
            return $this->succ($data);
        }
        $subjectService = new SubjectService();
        $subjects = $subjectService->getBatchSubjectInfos($subjectIds,$currentId)['data'];
        $data['subject_lists'] = !empty($subjects) ? array_values($subjects) : array();
        return $this->succ($data);
    }
    
    /**
     * 更新活动帖子信息
     */
    public function upActiveSubject($relationData, $relationId){
        if(empty($relationId)){
            return $this->error(500);
        }
        $result = $this->activeModel->upActiveSubject($relationData, $relationId);
        return $this->succ($result);
    }


    /**
     * 删除帖子活动关联关系（用于后台帖子移出活动）
     */
    public function delSubjectActiveRelation($relationData){
        if(empty($relationData)){
            return $this->error(500);
        }
        $result = $this->activeModel->delSubjectActiveRelation($relationData);
        return $this->succ($result);
    }
    
    /**
     * 根据帖子ID批量分组获取帖子活动信息
     */
    public function getBatchSubjectActives($subjectIds) {
        //通过帖子id获取帖子活动基本关联信息
        $subjectActives = $this->getActiveSubjectBySids($subjectIds)['data'];
        if (empty($subjectActives)) {
            return $this->succ(array());
        }
        //从关联关系中获取活动id，且去重
        $activeIds = array();
        foreach ($subjectActives as $sid => $active) {
            $activeIds[] = $active['active_id'];
        }
        if(!empty($activeIds)){
            $activeIds = array_unique($activeIds);
        }
        
        $condition = array('active_ids' => $activeIds);
        $activeRes = array();
        //通过活动id获取活动信息
        $activeInfos = $this->activeModel->getActiveList(false, 0, array(), $condition);
        if (empty($activeInfos)) {
            return $this->succ(array());
        }
        //将活动完整信息按帖子来分组组装入帖子活动关联关系中
        $activesArr = array();
        foreach($subjectActives as $key=>$subjectActive){
            $activesArr[$key] = $activeInfos[$subjectActive['active_id']];
        }
        return $this->succ($activesArr);
    }

    /*
     * 获取消消乐活动信息（展示初始化）
     * return:当前正在进行的消消乐活动信息
     * */
    public function getXiaoxiaoleActiveInfo()
    {
        // 获取当前正在进行的活动列表
        $activeInfo = $this->getCurrentActive()['data'];
        $xiaoxiaole = [];
        if(empty($activeInfo)) {
            return $this->succ($xiaoxiaole);
        }
        foreach($activeInfo as $id => $active) {
            if(isset($active['is_xiaoxiaole']) && !empty($active['is_xiaoxiaole'])) {
                // 封装相关结构体（奖品结构体等）
                $xiaoxiaole[$id] = $active;
                break;
            }
        }
        return $this->succ($xiaoxiaole);
    }

    /*
     * 消消乐活动参与用户展示信息
     * */
    public function getActiveUserClockInfo($active_id, $user_id)
    {
        if (empty($active_id) || empty($user_id)) {
            return $this->succ([]);
        }
        $return = $conditions = [];
        $subject_count = $bean_count = 0;
        // 获取用户信息
        $userService = new UserService();
        $res = $userService->getUserInfoByUids([$user_id])['data'];
        $return['user_info'] = $res;

        $s_time = date('Y-m-01', strtotime(date("Y-m-d")));
        $e_time = date('Y-m-d 23:59:59', strtotime("$s_time +1 month -1 day"));
        $conditions['s_time'] = $s_time;
        $conditions['e_time'] = $e_time;

        // 获取用户发帖数(自然月)
        $subjectService =new Subject();
        $conditions['active_id'] = $active_id;
        $userSubjectsCount = $subjectService->getBatchUserSubjectCounts([$user_id], $conditions)['data'];
        if(!empty($userSubjectsCount)) {
            $return['user_info'] = $userSubjectsCount;
        }
        // 获取用户蜜豆数
        $activeService = new Active();
        unset($conditions['active_id']);
        $res = $activeService->getActiveWinPrizeRecord($active_id, $user_id, $conditions)['data'];
        if(!empty($res)) {
            $bean_count = $res['prize_num'];
        }
        // 用户消消乐发帖数及蜜豆文案
        $xxl_word = F_Ice::$ins->workApp->config->get('busconf.active.xiaoxiaole_init')['active_prize_init'];
        $return['init_word'] = sprintf($xxl_word, $subject_count, $bean_count);
        // 用户打卡日历
        $res = $this->getActiveUserClockCalendar($user_id, $active_id);
        // 用户打卡提示（发布奖励配置）
        return $this->succ($return);
    }

    /*
    * 获取活动奖励蜜豆发放信息
    * */
    public function getActiveWinPrizeRecord($active_id, $user_id = 0, $conditions = [])
    {
        $return = ['prize_list' => [], 'prize_num' => 0];
        if(empty($active_id)) {
            return $this->succ($return);
        }
        $res = $this->activeModel->getActiveWinPrizeRecord($active_id, $user_id, $conditions);
        if(empty($res)) {
            return $this->succ($return);
        }
        $return['prize_list'] = $res['prize_list'];
        $return['prize_num'] = $res['prize_num'];
        return $this->succ($return);
    }

    /*
     * 消消乐活动用户打卡日历
     * */
    public function getActiveUserClockCalendar($active_id, $user_id)
    {
        if(empty($active_id) || empty($user_id)) {
            return $this->succ([]);
        }
        $conditions = $calendar = [];
        // 当月用户发帖情况
        $subjectService =new Subject();
        $xxl_word = F_Ice::$ins->workApp->config->get('busconf.active.active_clock_init')['active_create_init'];
        for($i = 1; $i <= date('d'); $i ++) {
            // 开始时间
            $s_time = date('Y-m-'.$i, strtotime(date("Y-m-d")));
            $conditions['s_time'] = $s_time;
            // 结束时间
            $conditions['e_time'] = date('Y-m').'-'.$i.' 23:59:59';
            $userSubjectsCount = $subjectService->getBatchUserSubjectCounts([$user_id], $conditions)['data'];
            $calendar[$i]['subject_count'] = $userSubjectsCount;
            $calendar[$i]['date_show'] = date('m').'.'.$i;
            $calendar[$i]['create_show'] = sprintf($xxl_word, $userSubjectsCount);
        }
        return $this->succ($calendar);
    }

    /**
     * 获取活动tab关联商品的帖子信息
     */
    public function getActiveSubjectByItems($active_id, $tab_id) {
        // 获取tab信息
        $tab_info = [];
        // 获取当前tab下的商品列表
        $item_ids = [];
        if(empty($item_ids)) {
            return $this->succ([]);
        }
        // 批量获取商品信息
        $result = [];
        $itemService = new ItemService();
        $itemInfos = $itemService->getItemList($item_ids)['data'];
        if(empty($itemInfos)) {
            return $this->succ([]);
        }
        $activeService = new UmsActiveService();
        foreach ($itemInfos as $item_id => &$item) {
            $params = [];
            $params['item_id'] = $item_id;
            $params['active_id'] = $active_id;
            $result = $activeService->getActiveSubjectCountByItems($params)['data'];
            if(empty($result[$item_id])) {
                // 0贴文案，奖励50蜜豆
                $item['zero_koubei_issue'] = '';
            }
            // 活动关联商品对应的发帖数
            $item['subject_count'] = $result[$item_id];
        }
        return $this->succ($result);
    }

    /*
     * 消消乐活动用户发帖排行
     * */
    public function getActiveUserRankList($active_id)
    {
        $return = [];
        if(empty($active_id)) {
            return $this->succ([]);
        }
        // 获取活动的所有帖子
        $res = $this->activeModel->getActiveSubjectsRank($active_id);
        // 遍历，获取排行榜
        return $this->succ($return);
    }

    /*
     * 活动用户打卡明细
     * */
    public function getActiveUserClockDetail($active_id, $conditions = [])
    {
        $return = [];
        // 奖励类型，起止时间条件
        $res = $this->getActiveWinPrizeRecord($active_id, 0, $conditions);
        foreach ($res as $info) {
            // 封装用户信息
            // 查询用户优质贴数量
        }
        return $this->succ($return);
    }
    
}

