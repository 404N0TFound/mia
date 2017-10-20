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
        if ($activeInfo['active_type'] == 'xiaoxiaole') {
            $extInfo['is_xiaoxiaole'] = 1;
            unset($activeInfo['active_type']);
        }
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
        if(!empty($activeInfo['xiaoxiaole_setting'])) {
            $extInfo['xiaoxiaole_setting'] = $activeInfo['xiaoxiaole_setting'];
            unset($activeInfo['xiaoxiaole_setting']);
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
        if ($activeInfo['active_type'] == 'xiaoxiaole') {
            $extInfo['is_xiaoxiaole'] = 1;
            unset($activeInfo['active_type']);
        }
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
        if(!empty($activeInfo['xiaoxiaole_setting'])) {
            $extInfo['xiaoxiaole_setting'] = $activeInfo['xiaoxiaole_setting'];
            unset($activeInfo['xiaoxiaole_setting']);
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
    public function getActiveInit()
    {
        $xiaoxiaole =  $tab_setting = [];
        // 获取当前正在进行的活动列表
        $activeInfos = $this->getCurrentActive()['data'];
        if(empty($activeInfos)) {
            return $this->succ($xiaoxiaole);
        }
        // 消消乐活动类型
        $active_type = $this->getActiveConfig('xiaoxiaole', 'active_type');
        foreach($activeInfos as $active) {
            if ($active['active_type'] == $active_type) {
                $xiaoxiaole = $active;
                break;
            }
        }
        // 获取活动的tab预设开始时间
        $pre_show_time = $xiaoxiaole['xiaoxiaole_setting']['pre_set_time'];
        if(strtotime($pre_show_time) > strtotime('now')) {
            // show current
            $tab_list = $xiaoxiaole['xiaoxiaole_setting']['item_tab_list'];
        }else {
            // show pre
            $tab_list = $xiaoxiaole['xiaoxiaole_pre_setting']['pre_set_item_tab_list'];
        }
        // 封装tab数据
        if(!empty($tab_list)) {
            foreach($tab_list as $k => $tab) {
                $tab_setting[$k]['tab_title'] = $tab['name'];
                if(!empty($tab['img_url'])) {
                    $imgInfo = getimagesize($tab['img_url']);
                    $tab_setting[$k]['tab_img']['url'] = $tab['img_url'];
                    $tab_setting[$k]['tab_img']['width'] = $imgInfo[0];
                    $tab_setting[$k]['tab_img']['height'] = $imgInfo[1];
                }
            }
        }
        $active_guide = $this->getActiveConfig('xiaoxiaole', 'guide_init');
        $xiaoxiaole['date_color'] = $active_guide['date_color'];
        $xiaoxiaole['active_regular_link'] = $active_guide['active_regular_link'];
        $xiaoxiaole['active_tab'] = $tab_setting;
        $xiaoxiaole['active_award'] = $xiaoxiaole['prize_list'];
        $xiaoxiaole['back_img'] = $xiaoxiaole['xiaoxiaole_setting']['striation_img_url'];
        $xiaoxiaole['back_color'] = $xiaoxiaole['xiaoxiaole_setting']['backgroundcolor'];
        $xiaoxiaole['active_regular'] = $xiaoxiaole['xiaoxiaole_setting']['xiaoxiaole_rule'];

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
        $return = $conditions = $activeInfo = $calendarList = [];

        // 获取用户信息
        $userService = new UserService();
        $userInfo = $userService->getUserInfoByUids([$user_id])['data'];
        $return['user_info'] = $userInfo[$user_id];

        // 获取活动信息
        $condition = array('active_ids' => array($active_id));
        $activeInfo = $this->activeModel->getActiveList(0, FALSE, array(1), $condition);
        if(empty($activeInfo)) {
            return $this->succ([]);
        }
        // 获取活动的起止时间
        $conditions['s_time'] = $activeInfo[$active_id]['start_time'];
        $conditions['e_time'] = $activeInfo[$active_id]['end_time'];

        // 获取用户发帖数(自然月)
        $res = $this->getActiveSubjectCounts($active_id, $user_id, $conditions)['data'];
        $return['koubei_num'] = $res['count'];

        // 活动用户是否是首贴标识(用于客户端区分打卡文案)
        if(empty($return['koubei_num'])) {
            $return['is_first_pub'] = 1;
        }else {
            $return['is_first_pub'] = 0;
        }

        // 获取用户蜜豆数
        $res = $this->getActiveWinPrizeRecord($active_id, $user_id, $conditions)['data'];
        $return['mibean_num'] = $res['prize_num'];

        // 用户打卡日历
        $handleCalendar = [];
        $calendarList = $this->getActiveUserClockCalendar($active_id, $user_id, $conditions)['data'];

        // 处理日历月份展示形式
        foreach($calendarList as $k => $calendar) {
            if(substr($calendar['issue_date'], 8, 2) == '01') {
                if(!empty($calendar['is_today'])) {
                    $handleCalendar[$k]['issue_date'] = '今天';
                }else {
                    $handleCalendar[$k]['issue_date'] = substr($calendar['issue_date'], 5, 2)."月";
                }
            }else {
                if(!empty($calendar['is_today'])) {
                    $handleCalendar[$k]['issue_date'] = '今天';
                }else {
                    $handleCalendar[$k]['issue_date'] = substr($calendar['issue_date'], 8, 2);
                }
            }
            $handleCalendar[$k]['subject_nums'] = $calendar['subject_nums'];
            $handleCalendar[$k]['is_today'] = $calendar['is_today'];
        }
        $return['mark_calendar'] = $handleCalendar;

        // 用户消消乐打卡提示
        $calendarNum = $threeClock = $sevenClock = $three_prize = $seven_prize = $month_prize = 0;
        $continue_day_count = [];
        $active_config = $this->getActiveConfig('xiaoxiaole', 'user_show_init');

        // 日历背景图
        $return['back_img'] = $active_config['calendar_image'];

        // 获取当月的天数
        $current_day = date('t');

        foreach($calendarList as $k => $value) {
            if (!empty($value['subject_nums'])) {
                $calendarNum += 1;
                $continue_day_count[$k] = $calendarNum;
            } else {
                $continue_day_count[$k] = $calendarNum - 1;
                $calendarNum = 0;
            }
            if ($calendarNum >= 3 && $calendarNum < 7) {
                $threeClock = 1;
            }else if($calendarNum >= 7 && $calendarNum < $current_day) {
                $sevenClock = 1;
            }
        }

        // 获取活动奖励列表
        $prizeList = $activeInfo[$active_id]['prize_list'];
        // 打卡奖励配置
        $calendar_config = $active_config['calendar_prize'];
        foreach($prizeList as $prize) {
            if($prize['prize_type'] == $calendar_config['three']) {
                // 3日打卡奖励
                $three_prize = $prize['bean_count'];
            }
            if($prize['prize_type'] == $calendar_config['seven']) {
                // 7日打卡奖励
                $seven_prize = $prize['bean_count'];
            }
            if($prize['prize_type'] == $calendar_config['month']) {
                // 月度打卡奖励
                $month_prize = $prize['bean_count'];
            }
        }

        // 用户打卡提示及打卡相差天数文案
        if(empty($threeClock) && empty($sevenClock)) {
            $return['apart_days'] = 3 - max($continue_day_count);
            // 展示三日打卡提示
            $return['mark_notice'] = sprintf($active_config['mark_notice'], $return['mibean_num'], 3, $three_prize);
        }else if(!empty($threeClock) && empty($sevenClock)) {
            $return['apart_days'] = 7 - max($continue_day_count);
            // 展示七日打卡提示
            $return['mark_notice'] = sprintf($active_config['mark_notice'], $return['mibean_num'], 7, $seven_prize);
        }else {
            // 展示月度打卡提示
            $return['apart_days'] = $current_day - max($continue_day_count);
            // 展示七日打卡提示
            $return['mark_notice'] = sprintf($active_config['mark_notice'], $return['mibean_num'], $current_day, $month_prize);
        }

        return $this->succ($return);
    }

    /*
     * 获取活动tab商品展示
     * */
    public function getActiveTabItemList($active_id, $tab_title)
    {
        $tab_items = ['tab_info' =>[], 'items' => []];
        if (empty($active_id) || empty($tab_title)) {
            return $this->succ([]);
        }
        // 获取所有活动基本信息
        $condition = array('active_ids' => array($active_id));
        $activeInfo = $this->activeModel->getActiveList(0, FALSE, array(1), $condition);
        if (empty($activeInfo)) {
            return $this->succ([]);
        }
        // 展示tab
        $active_setting = $activeInfo[$active_id]['xiaoxiaole_setting'];
        if(empty($active_setting)) {
            $tab_items['tab_info'] = [];
        }
        $pre_tab_flag = FALSE;
        $pre_show_time = $active_setting['pre_set_time'];
        if(strtotime($pre_show_time) < strtotime('now')) {
            // show current
            $tab_list = $active_setting['item_tab_list'];
        }else {
            // show pre
            $tab_list = $active_setting['pre_set_item_tab_list'];
            $tab_name_list = array_column($tab_list, 'name');
            $pre_tab_flag = TRUE;
        }

        // 封装tab数据(图片的宽高，数据待定)

        foreach($tab_list as $tab) {
            if($tab['name'] == $tab_title) {
                $tab_items['tab_info'] = $tab;
            }
        }

        // 预设tab，需要处理tab预设关系(修改状态)
        if(!empty($pre_tab_flag) && !empty($tab_name_list)) {
            $updateData = ['is_pre_set' => 0];
            $where = ['active_id' => $active_id, 'item_tab' => $tab_name_list];
            $res = $this->activeModel->updateActiveItemPre($where, $updateData);
        }

        $res = $this->activeModel->getActiveTabItems($active_id, $tab_title);
        if (empty($res)) {
            return $this->succ([]);
        }
        $item_ids = array_column($res, 'item_id');
        //$item_ids = [1014636, 1000016];
        $itemService = new ItemService();
        $itemInfos = $itemService->getBatchItemBrandByIds($item_ids)['data'];
        $activeUmsService = new UmsActiveService();
        $params = [];
        $params['item_id'] = $item_ids;
        $params['active_id'] = $active_id;
        // 活动起止时间
        $params['start_time'] = $activeInfo[$active_id]['start_time'];
        $params['end_time'] = $activeInfo[$active_id]['end_time'];
        $result = $activeUmsService->getActiveSubjectCountByItems($params)['data'];

        // 获取活动奖励（针对0口碑的蜜豆奖励）

        foreach ($itemInfos as $item_id => &$item) {
            if (empty($result[$item_id])) {
                // 0贴文案，奖励50蜜豆
                // 奖励类型
                $item['prize_type'] = 1;
                // 奖励文案
                $item['prize_desc'] = '消灭它，得50蜜豆';
            }
            // 发帖数
            $item['koubei_num'] = $result[$item_id];
        }
        $tab_items['items'] = array_values($itemInfos);
        return $this->succ($tab_items);
    }

    /*
     * 获取活动奖品列表
     * */
    public function getActivePrizeList($active_id, $user_id)
    {
        $prizeList = [];
        if(empty($active_id) || empty($user_id)) {
            return $this->succ($prizeList);
        }
        // 获取活动信息
        $condition = array('active_ids' => array($active_id));
        $activeInfo = $this->activeModel->getActiveList(1, 1, array(1), $condition);
        if(empty($activeInfo)) {
            return $this->succ($prizeList);
        }
        $prize = $activeInfo[$active_id]['active_award'];
        $prizeList['prize_desc'] = '';
        $prizeList['prize_desc_color'] = '';
        $prizeList['prizes'] = $prize;
        // 消消乐活动收货地址
        $userService = new UserService();
        $groupUserInfo = $userService->getGroupUserInfo([$user_id])['data'];
        if(!empty($groupUserInfo)) {
            $prizeList['dst_address'] = $groupUserInfo[$user_id]['dst_address'];
        }
        return $this->succ($prizeList);
    }

    /*
     * 获取活动关联帖子列表及计数
     * */
    public function getActiveSubjectCounts($active_id, $user_id = 0, $conditions = [])
    {
        $result = ['subject_ids' => [] ,'count' => 0];
        if(empty($active_id)){
            return $this->succ($result);
        }
        $res = $this->activeModel->getSubjectCountsByActive($active_id, $user_id, $conditions);
        // 封装数据
        $result['subject_ids'] = $res['list'];
        $result['count'] = $res['count'];
        return $this->succ($result);
    }

    /*
     * 活动关联帖奖励审核，是否中奖，蜜豆下发
     * */
    public function activeSubjectVerify($id, $status = 1)
    {
        $return = $setData = $insertData = [];
        if(empty($active_id) || empty($subject_id)) {
            return $this->succ($return);
        }

        // 更新帖子审核状态
        $setData['is_qualified'] = intval($status);
        $res = $this->activeModel->updateActiveSubjectVerify($setData, $id);

        // 用户是否中奖及奖励下发
        $res = $this->checkActiveSubjectPrize($id, $status);
    }

    /*
     * 判断活动用户是否中奖
     * return：中奖类型（prize_type）
     * */
    public function checkActiveSubjectPrize($id, $status = 1)
    {
        // 获取活动帖子关系信息
        $activeSubjectInfo = [];
        $active_id = '';
        $user_id = '';
        $subject_id = '';

        // 写入奖励发放记录表
        $insertData = [];
        $insertData['active_id'] = $active_id;
        $insertData['user_id'] = $user_id;
        $insertData['subject_id'] = $subject_id;

        // 获取活动信息（奖励信息）
        $prizeInfo = $conditions = [];

        $conditions['s_time'] = '';
        $conditions['e_time'] = '';
        $first = $zero = $three = $seven = false;

        // 判断当前处理贴是否是0口碑贴
        $res = $this->getZeroActiveSubject($active_id, $user_id);
        if($res == $subject_id) {
            // 0口碑帖处理
            $zero = true;
        }

        // 判断当前贴是否为用户首贴
        $res = $this->activeModel->getSubjectCountsByActive($active_id, $user_id, $conditions);
        if(!empty($res)) {
            $first = true;
        }

        // 判断当前贴是否符合3日打卡及7日打卡
        $res = $this->checkActiveSatisfySubject($active_id, $user_id, $subject_id);

        if($status == 1) {
            // 下发蜜豆奖励
            $insertData['status'] = 1;
            if(!empty($zero)) {
                // 下发0口碑奖励
            }
            if(!empty($first)) {
                // 下发首贴奖励
            }
            if(!empty($three)) {
                // 下发三日打卡奖励
            }
            if(!empty($three)) {
                // 下发七日打卡奖励
            }
            // 新增打卡奖励记录
            $insertData['status'] = 1;
        }

        if($status == -1) {
            // 扣除蜜豆奖励
            /*  0口碑：直接扣除
                首贴：
                day<=3
                首贴，影响3日打卡，扣除首贴奖及3日打卡奖励
                4<=day
                首贴，不影响3日打卡，影响7日打卡，扣除首贴奖励及7日打卡奖励
                首贴，不影响3日打卡，不影响7日打卡，扣除首贴奖
                非首贴：
                day<=3
                影响3日打卡，扣除3日打卡奖励
                4<=day<=7
                不影响3日打卡，影响7日打卡，扣除7日打卡奖励
                8<=day
                不影响3日打卡，不影响7日打卡，影响当月打卡，扣除当月打卡奖励
            */
            // 新增打卡奖励记录
        }

    }

    /*
     * 活动用户对应商品首贴
     * return subject_id
     * */
    public function getZeroActiveSubject($active_id, $user_id)
    {
        // 连表查
    }

    /*
     * 活动用户商品展示
     * 已发帖则下沉
     * */
    public function getActiveUserItemsSortList($active_id, $user_id)
    {

    }

    /*
    * 获取活动奖励蜜豆发放信息
    * */
    public function getActiveWinPrizeRecord($active_id, $user_id = 0, $conditions = [])
    {
        $return = ['prize_num' => 0];
        if(empty($active_id)) {
            return $this->succ($return);
        }
        // 获取用户有效蜜豆奖励列表
        $res = $this->activeModel->getActiveWinPrizeRecord($active_id, $user_id, $conditions);
        if(empty($res)) {
            return $this->succ($return);
        }
        foreach($res as $prizeInfo) {
            $return['prize_num'] += $prizeInfo['prize_num'];
        }
        /*// 蜜豆奖励合并
        $prize_total_num = 0;
        $handlePrizeList = [];
        foreach($res as $prizeInfo) {
            $subject_id = $prizeInfo['subject_id'];
            $active_id = $prizeInfo['active_id'];
            // 关联relation查询帖子对应的审核状态（is_qualified）

        }
        $return['prize_list'] = $res['prize_list'];
        $return['prize_num'] = $prize_num;*/
        return $this->succ($return);
    }

    /*
     * 消消乐活动用户打卡日历
     * */
    public function getActiveUserClockCalendar($active_id, $user_id = 0, $conditions = [])
    {
        if(empty($active_id) || empty($user_id)) {
            return $this->succ([]);
        }
        $calendar = $effect = [];

        // 获取当前时间日期
        $today = date('Y-m-d', time());

        // 查询用户总发帖列表
        $page = 1;
        $limit = 1000;
        while (true) {
            $list = [];
            $list = $this->activeModel->getActiveUserSubjectList($active_id, $user_id, $page, $limit, $conditions);
            if(empty($list)) {
                break;
            }
            foreach($list as $value) {
                $subject_time = $value['create_time'];
                $date = substr($subject_time, 0, 10);
                $effect[$date]['issue_date'] = $date;
                $effect[$date]['subject_nums'] += 1;
            }
            if(count($list) < $limit) {
                break;
            }
            $page ++;
        }

        if(empty($effect)) {
            return $this->succ([]);
        }

        // 活动时间内的日历
        $fs_time = strtotime($conditions['s_time']);
        $fe_time = strtotime($conditions['e_time']);
        for ($start = $fs_time; $start <= $fe_time; $start += 24 * 3600) {
            $date = date('Y-m-d', $start);
            if(empty($effect[$date])) {
                $calendar[$date]['issue_date'] = $date;
                $calendar[$date]['subject_nums'] = 0;
            }else{
                $calendar[$date] = $effect[$date];
            }
            if($date == $today) {
                $calendar[$date]['is_today'] = 1;
            }else {
                $calendar[$date]['is_today'] = 0;
            }
        }
        return $this->succ(array_values($calendar));
    }

    /*
     * 消消乐活动用户发帖排行
     * 获取前20名发帖最多的用户
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

    /*
     * 消消乐活动配置
     * */
    private function getActiveConfig($configure, $type = '')
    {
        if(empty($configure)) {
            return false;
        }
        $config = F_Ice::$ins->workApp->config->get('busconf.active')[$configure];
        if(empty($config[$type])) {
            return false;
        }
        return $config[$type];
    }
    
}

