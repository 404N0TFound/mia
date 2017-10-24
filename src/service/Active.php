<?php
namespace mia\miagroup\Service;

use \F_Ice;
use mia\miagroup\Model\Active as ActiveModel;
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
        if(!empty($activeInfo['prize_list'])) {
            $extInfo['prize_list'] = $activeInfo['prize_list'];
            unset($activeInfo['prize_list']);
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
        if(!empty($activeInfo['prize_list'])) {
            $extInfo['prize_list'] = $activeInfo['prize_list'];
            unset($activeInfo['prize_list']);
        }
        $activeInfo['ext_info'] = $extInfo;
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
        $conditions = ['active_status' => 2];
        $activeInfos = $this->activeModel->getActiveList(0, false, [1], $conditions);
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
        if(empty($xiaoxiaole)) {
            return $this->succ($xiaoxiaole);
        }
        $active_guide = $this->getActiveConfig('xiaoxiaole', 'guide_init');
        $xiaoxiaole['date_color'] = $active_guide['date_color'];
        $xiaoxiaole['active_regular_link'] = $active_guide['active_regular_link'];
        $xiaoxiaole['active_award'] = $xiaoxiaole['prize_list'];
        $xiaoxiaole['back_img'] = $active_guide['back_img'];
        $xiaoxiaole['back_color'] = $xiaoxiaole['xiaoxiaole_setting']['backgroundcolor'];
        $xiaoxiaole['active_regular'] = $xiaoxiaole['xiaoxiaole_setting']['xiaoxiaole_rule'];

        // 活动起止时间
        $xiaoxiaole['start_time'] = date('m.d', strtotime($xiaoxiaole['start_time']));
        $xiaoxiaole['end_time'] = date('m.d', strtotime($xiaoxiaole['end_time']));

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
        $activeInfo = $this->activeModel->getActiveList(1, 1, [1], ['active_ids' => [$active_id]]);
        if(empty($activeInfo)) {
            return $this->succ([]);
        }

        // 获取用户活动期间发帖数(有效发帖数)
        $conditions['s_time'] = $activeInfo[$active_id]['start_time'];
        $conditions['e_time'] = $activeInfo[$active_id]['end_time'];
        // 帖子审核状态
        $conditions['is_qualified'] = [0,1];
        $res = $this->activeModel->getActiveUserSubjectList($active_id, $user_id, FALSE, 0, [1], $conditions);
        $return['koubei_num'] = $res['count'];

        // 用户打卡配置
        $active_config = $this->getActiveConfig('xiaoxiaole', 'user_show_init');

        // 活动用户是否是首贴标识(用于客户端区分打卡文案)
        if(empty($return['koubei_num'])) {
            $return['is_first_pub'] = $active_config['no_first_pub'];
        }else {
            $return['is_first_pub'] = $active_config['is_first_pub'];
        }

        // 获取用户蜜豆数
        $res = $this->getActiveWinPrizeRecord($active_id, $user_id, $conditions)['data'];
        $return['mibean_num'] = $res['prize_num'] ? $res['prize_num'] : 0;

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

        // 用户活动连续打卡标识
        $calendarNum = 0;
        // 用户打卡天数记录
        $continue_day_count = [];

        // 日历背景图
        $return['back_img'] = $active_config['calendar_image'];

        // 获取活动奖励列表
        $activePrizeList = $activeInfo[$active_id]['prize_list'];

        // 奖品分类（获取打卡奖励配置）
        $prize_config = $this->getActiveConfig('xiaoxiaole', 'active_issue_prize');
        $sign_config = $prize_config['prize_type']['sign'];

        // 获取活动奖励对应关系
        $prize_sign_list = [];
        foreach($activePrizeList as $prize) {
            if($prize['prize_type'] == $sign_config) {
                $prize_sign_list[$prize['sign_day']] = $prize;
                // 获取打卡配置奖励天数
                $signDays[] = $prize['sign_day'];
            }
        }
        sort($signDays);

        // 获取用户打卡的最大连续天数
        foreach($calendarList as $k => $value) {
            if (!empty($value['subject_nums'])) {
                $calendarNum += 1;
                $continue_day_count[$k] = $calendarNum;
            } else {
                if(!empty($calendarNum)) {
                    $calendarNum -= 1;
                }
                $continue_day_count[$k] = $calendarNum;
                $calendarNum = 0;
            }
        }
        $maxClockDay = max($continue_day_count);

        // 奖励文案配置参数
        $awards_num = $apart_days = $sign_day = 0;

        // 根据设置奖项获取用户打卡提示及相差天数文案
        for($i = 0; $i < count($signDays) ; $i++) {
            if($maxClockDay < $signDays[$i]) {
                // 用户打卡最大天数比设置的最小天数还要小
                $return['apart_days'] = $signDays[$i] - max($continue_day_count);
                if(!empty($prize_sign_list[$signDays[$i]])) {
                    $apart_days = $prize_sign_list[$signDays[$i]]['sign_day'] - max($continue_day_count);
                    $sign_day = $prize_sign_list[$signDays[$i]]['sign_day'];
                    $awards_num = $prize_sign_list[$signDays[$i]]['awards_num'];
                }
                break;
            }
            if($maxClockDay >= $signDays[$i] && $maxClockDay < $signDays[$i+1]) {
                foreach($prize_sign_list as $prize) {
                    if($prize['sign_day'] == $signDays[$i+1]) {
                        $apart_days = $signDays[$i+1] - max($continue_day_count);
                        $sign_day = $prize['sign_day'];
                        $awards_num = $prize['awards_num'];
                    }
                    // 满足条件，直接跳出最外层循环
                    break 2;
                }
            }
        }
        if(!empty($apart_days)) {
            // 打卡相隔天数
            $return['apart_days'] = $apart_days;
            // 打卡提示
            $return['mark_notice'] = sprintf($active_config['mark_notice'], $return['mibean_num'], $sign_day, $awards_num);
        }

        return $this->succ($return);
    }

    /*
     * 获取活动tab商品展示
     * 分页
     * */
    public function getActiveTabItemList($active_id, $tab_title, $page = 1, $limit = 20)
    {
        $tab_items = ['items' => []];
        if (empty($active_id) || empty($tab_title)) {
            return $this->succ($tab_items);
        }
        // 获取所有活动基本信息
        $activeInfo = $this->activeModel->getActiveList(1, 1, [1], ['active_ids' => [$active_id]]);
        if (empty($activeInfo)) {
            return $this->succ($tab_items);
        }

        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        // 获取tab对应的item_id
        $tabItemList = $this->activeModel->getActiveTabItems($active_id, $tab_title, $limit, $offset);
        if (empty($tabItemList)) {
            return $this->succ($tab_items);
        }
        $item_ids = array_column($tabItemList, 'item_id');
        $itemService = new ItemService();
        // 获取商品列表信息
        $itemInfos = $itemService->getBatchItemBrandByIds($item_ids)['data'];

        $activeUmsService = new UmsActiveService();
        $params = [];
        $params['item_id'] = $item_ids;
        $params['active_id'] = $active_id;
        // 活动起止时间
        $params['start_time'] = $activeInfo[$active_id]['start_time'];
        $params['end_time'] = $activeInfo[$active_id]['end_time'];
        // 获取活动下商品发帖数
        $itemSubjectCounts = $activeUmsService->getActiveSubjectCountByItems($params)['data'];

        // 获取活动奖励（针对0口碑的蜜豆奖励）
        $zero_prize = [];
        $prize_list = $activeInfo[$active_id]['prize_list'];
        $prize_config = $this->getActiveConfig('xiaoxiaole', 'active_issue_prize');
        $zero_config = $prize_config['prize_type']['zero'];
        foreach($prize_list as $prize) {
            if($prize['prize_type'] == $zero_config) {
                $zero_prize = $prize;
            }
        }

        $active_item_prize = $this->getActiveConfig('xiaoxiaole', 'active_item_prize');
        foreach ($itemInfos as $item_id => &$item) {
            if (empty($itemSubjectCounts[$item_id])) {
                // 奖励类型
                $item['prize_type'] = $zero_prize['prize_type'];
                // 奖励文案
                $item['prize_desc'] = sprintf($active_item_prize['prize_word'], $zero_prize['awards_num']);
            }
            // 发帖数
            $item['koubei_num'] = $itemSubjectCounts[$item_id] ? $itemSubjectCounts[$item_id] : 0;
        }
        $tab_items['items'] = array_values($itemInfos);

        return $this->succ($tab_items);
    }

    /*
     * 获取活动奖品列表
     * */
    public function getActivePrizeList($active_id, $user_id, $page = 1, $limit = 8)
    {
        $active_prize = [];
        if(empty($active_id) || empty($user_id)) {
            return $this->succ($active_prize);
        }
        // 获取活动信息
        $activeInfo = $this->activeModel->getActiveList(1, 1, [1], ['active_ids' => [$active_id]]);
        if(empty($activeInfo)) {
            return $this->succ($active_prize);
        }
        // 奖品页面设置
        $active_prize['prize_desc'] = '';
        // 奖品页面文字颜色设置
        $active_prize['prize_desc_color'] = '';

        // 获取活动列表
        $prizeList = [];
        $prizeInfos = $activeInfo[$active_id]['prize_list'];

        // 奖品列表分页
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $prizeInfos  = array_slice($prizeInfos, $offset, $limit);

        foreach($prizeInfos as $k => $info) {
            // 奖项名称
            $prizeList[$k]['prize_name'] = $info['prize_type_name'];
            // 奖励说明
            $prizeList[$k]['award_desc'] = $info['prize_desc'];
            // 奖品名
            $prizeList[$k]['award_name'] = $info['prize_name'];
            // 奖品图片结构体
            $prizeList[$k]['award_img'] = $info['prize_img'];
        }
        $active_prize['prizes'] = $prizeList;

        // 消消乐活动收货地址
        $userService = new UserService();
        $groupUserInfo = $userService->getGroupUserInfo([$user_id])['data'];
        if(!empty($groupUserInfo)) {
            $active_prize['dst_address'] = $groupUserInfo[$user_id]['dst_address'];
        }

        return $this->succ($active_prize);
    }

    /*
     * 消消乐活动用户发帖排行
     * 获取前20发帖最多的用户（没有分页效果）
     * */
    public function getActiveUserRankList($active_id, $page = 1, $limit = 20)
    {
        if(empty($active_id)) {
            return $this->succ([]);
        }
        // 获取活动信息
        $activeInfo = $this->activeModel->getActiveList(1, 1, [1], ['active_ids' => [$active_id]]);
        if(empty($activeInfo)) {
            return $this->succ([]);
        }
        // 活动开始时间
        $start_time = $activeInfo[$active_id]['start_time'];
        $month = substr($start_time, 5, 2);
        $day = substr($start_time, 8, 2);

        // 获取活动的所有帖子
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $subjectRank = $this->activeModel->getActiveSubjectsRank($active_id, $limit, $offset);
        if(empty($subjectRank)) {
            return $this->succ([]);
        }
        // 批量获取用户信息
        $userIds = array_column($subjectRank, 'user_id');
        $userService = new UserService();
        $userInfos = $userService->getUserInfoByUids($userIds)['data'];

        // 获取排行榜文案
        $rank_config = $this->getActiveConfig('xiaoxiaole', 'active_user_rank');

        // 排行页面文案
        $rankLists = [];
        $rankLists['rank_desc'] = sprintf($rank_config['rank_desc'], $month.'.'.$day);
        // 排行页面文字颜色
        $rankLists['rank_desc_color'] = '';
        // 排行用户列表
        foreach($subjectRank as $k => $v) {
            if(empty($userInfos[$v['user_id']])) {
                continue;
            }
            $rankLists['rank_users'][$k]['user_info'] = $userInfos[$v['user_id']];
            $rankLists['rank_users'][$k]['user_achievement_desc'] = sprintf($rank_config['achievement_desc'], $month.'.'.$day, $v['subject_count']);
            $rankLists['rank_users'][$k]['koubei_num'] = $v['subject_count'];
        }

        return $this->succ($rankLists);
    }

    /*
     * 获取活动用户发帖列表
     * */
    public function getActiveUserSubjectList($active_id, $user_id, $issue_date = '', $page = 1, $limit = 20)
    {
        $return = ['subject_lists' => []];
        if(empty($active_id) || empty($user_id)) {
            return $this->succ($return);
        }
        // 获取活动信息
        $activeInfo = $this->activeModel->getActiveList(1, 1, [1], ['active_ids' => [$active_id]]);
        if(empty($activeInfo)) {
            return $this->succ($return);
        }

        // 获取奖励列表
        $prizeInfos = $activeInfo[$active_id]['prize_list'];
        $prize_config = $this->getActiveConfig('xiaoxiaole', 'active_issue_prize');

        // 活动开始时间
        $conditions = [];
        if(empty($issue_date)) {
            // 查询活动下的用户发帖列表
            $conditions['s_time'] = $activeInfo[$active_id]['start_time'];
            $conditions['e_time'] = $activeInfo[$active_id]['end_time'];
        }else {
            // 查询活动下的用户日发帖列表
            $conditions['s_time'] = $issue_date;
            $conditions['e_time'] = date('Y-m-d', strtotime('+1 day'));
        }
        $offset = $page > 1 ? ($page -1) * $limit : 0;
        $conditions['is_qualified'] = [0,1];
        $res = $this->activeModel->getActiveUserSubjectList($active_id, $user_id, $limit, $offset, [1], $conditions);
        $subjectList = $res['list'];

        if(empty($subjectList)) {
            return $this->succ($return);
        }
        $subjectIds = array_column($subjectList, 'subject_id');
        $handleSubjects = [];
        foreach($subjectList as $info) {
            $handleSubjects[$info['subject_id']] = $info;
        }
        // 获取帖子信息(查询所有状态的帖子信息)
        $subjectService = new SubjectService();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, ['user_info', 'count', 'content_format', 'album'], [])['data'];

        // 帖子配置
        $subject_config = \F_Ice::$ins->workApp->config->get('busconf.subject.status');

        // 活动帖子状态设置
        $active_subject_status = $this->getActiveConfig('xiaoxiaole', 'active_subject_detail');

        // 帖子新增状态及是否为0口碑
        foreach($subjectInfos as $subject_id => &$subject) {
            $status = $subject['status'];
            // 帖子状态为1，查看活动下帖子的审核状态
            if($status == $subject_config['normal']) {
                if(!empty($subject['is_fine'])) {
                    $subject['subject_status'] = $active_subject_status['status']['is_fine'];
                }else{
                    $subject['subject_status'] = $active_subject_status['status']['normal'];
                }
            }else{
                if($status == $subject_config['audit_failed']) {
                    $subject['subject_status'] = $active_subject_status['status']['audit_failed'];
                }
                if($status == $subject_config['shield']) {
                    $subject['subject_status'] = $active_subject_status['status']['shield'];
                }
            }
            // 得奖明细
            $conditions['prize_type'] = $prize_config['prize_type']['zero'];
            $conditions['subject_id'] = $subject_id;
            $res = $this->activeModel->getActiveWinPrizeRecord($active_id, $user_id, FALSE, 0, $conditions);
            if(!empty($res['list'])) {
                // 说明有符合0口碑逻辑

            }

        }
        $return['subject_lists'] = array_values($res);

        return $this->succ($return);
    }

    /*
     * 活动关联帖奖励审核，是否中奖，蜜豆下发
     * */
    public function activeSubjectVerify($ids, $status = 1)
    {
        $setData = $insertData = [];
        $ids = is_array($ids) ? $ids : [$ids];
        if(empty($ids)) {
            return $this->succ([]);
        }

        // 获取活动审核帖子状态配置
        $active_qualified = $this->getActiveConfig('xiaoxiaole', 'active_subject_qualified');

        // 更新帖子审核状态
        $setData['is_qualified'] = intval($status);
        $res = $this->activeModel->updateActiveSubjectVerify($setData, $ids);
        if(empty($res)) {
            return $this->succ([]);
        }
        $return = ['status' => false];
        if($status == $active_qualified['audit_pass']) {
            // 审核通过
            $return['status'] = true;
            return $this->succ($return);
        }else{
            // 审核不通过状态(扣除下发蜜豆)
            $res = $this->checkActiveSubjectPrize($ids, $status);
        }
    }

    /*
     * 判断活动用户是否中奖及奖品下发
     * return：中奖类型（prize_type）
     * */
    public function checkActiveSubjectPrize($ids, $status = 1)
    {
        if(empty($ids)) {
            return $this->succ([]);
        }
        // 获取活动对应的帖子id
        $mibean = new \mia\miagroup\Remote\MiBean();
        $activeRelation = $this->activeModel->getActiveSubjectRelation($ids);
        if(empty($activeRelation)) {
            return $this->succ([]);
        }
        foreach($activeRelation as $relation) {
            $active_id = $relation['active_id'];
            $user_id = $relation['user_id'];
            $subject_id = $relation['subject_id'];
            $create_time = $relation['create_time'];
            // 获取活动信息
            $activeInfo = $this->activeModel->getActiveList(1, 1, [1], ['active_ids' => [$active_id]]);
            if($active_id != $activeInfo['id']) {
                // 不是消消乐活动
                return $this->succ([]);
            }

            // 活动起止时间
            $conditions['s_time'] = $activeInfo[$active_id]['start_time'];
            $conditions['e_time'] = $activeInfo[$active_id]['end_time'];

            // 奖励发放记录
            $insertData = [];
            $insertData['active_id'] = $active_id;
            $insertData['user_id'] = $user_id;
            $insertData['subject_id'] = $subject_id;

            // 下发蜜豆记录
            $param = [];
            $param['user_id'] = $user_id;
            // 蜜豆类型
            $beanType = $this->getActiveConfig('activeBeanType');
            $param['relation_type'] = $beanType;
            $param['relation_id'] = $subject_id;
            $param['to_user_id'] = $user_id;

            // 获取活动奖励列表
            $prizeList = $activeInfo[$active_id]['prize_list'];

            // 获取奖品配置
            $prize_config = $this->getActiveConfig('xiaoxiaole', 'active_issue_prize');

            $signDayPrize = $zeroKoubeiPrize = $everyPubPrize = [];

            // 奖品类型
            foreach($prizeList as $prize) {
                if($prize['prize_type'] == $prize_config['prize_type']['sign']) {
                    $signDayPrize[] = $prize;
                }
                if($prize['prize_type'] == $prize_config['prize_type']['zero']) {
                    $zeroKoubeiPrize = $prize;
                }
                if($prize['prize_type'] == $prize_config['prize_type']['every']) {
                    $everyPubPrize = $prize;
                }
            }

            // 审核状态配置
            $active_qualified = $this->getActiveConfig('xiaoxiaole', 'active_subject_qualified');

            // 记录奖品下发类型及下发蜜豆种类
            $prizeSetMiBean = $prizeDelMiBean = [];

            // 0口碑奖励类型
            if(!empty($zeroKoubeiPrize)) {
                $pointTagService = new PointTags();
                $res = $pointTagService->getBatchSubjectItmeIds([$subject_id])['data'];
                // 获取当前贴对应的商品id
                $item_id = $res[$subject_id][0];
                // 获取商品下的首贴
                $res = $this->activeModel->getFirstItemSubject($item_id);
                $item_first_subject = $res[$item_id];
                if($item_first_subject == $subject_id) {
                    // 符合0口碑下发规则
                    $prizeSetMiBean[$zeroKoubeiPrize['prize_type']]['awards_num'] = $zeroKoubeiPrize['awards_num'];
                }
            }

            // 用户打卡奖励类型
            if(!empty($signDayPrize)) {

                // 打卡参数
                $continue_day_count = $currentCalendarList = [];
                $calendarNum = $oneClock = $threeClock = $sevenClock = 0;

                // 用户活动周期打卡日历
                $calendarList = $this->getActiveUserClockCalendar($active_id, $user_id, $conditions)['data'];

                // 用户当前发帖时间连续发帖的天数
                foreach($calendarList as $k => $value) {
                    if (!empty($value['subject_nums'])) {
                        $calendarNum += 1;
                        $continue_day_count[$k] = $calendarNum;
                    } else {
                        if(!empty($calendarNum)) {
                            $calendarNum -= 1;
                        }
                        $continue_day_count[$k] = $calendarNum;
                        $calendarNum = 0;
                    }
                }
                // 当前发帖时间连续打卡最大天数
                $maxClockDay = max($continue_day_count);

                // 获取奖项设置天数
                $signDays = array_column($signDayPrize, 'sign_day');
                sort($signDays);

                // 判断当前贴是否为每日首贴,打卡奖根据每天的首贴来判断，其余情况不下发打卡奖励蜜豆
                $conditions['type'] = 'user_first';
                $conditions['s_time'] = explode(' ', $create_time)[0];
                $firstSubjectByday = $this->activeModel->getActiveUserSubjectList($active_id, $user_id, FALSE, 0, $conditions);
                if($subject_id == $firstSubjectByday[0]['subject_id']) {
                    // 当前贴为每日首贴
                    for($i = 0; $i < count($signDays) ; $i++) {
                        if($maxClockDay == $signDays[$i]) {
                            foreach($signDayPrize as $prize) {
                                if($prize['sign_day'] == $signDays[$i]) {
                                    $prizeSetMiBean[$prize['prize_type']]['awards_num'] = $prize['awards_num'];
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            // 判断当前贴是否为用户首贴
            $conditions['type'] = 'user_first';
            $res = $this->activeModel->getSubjectCountsByActive($active_id, $user_id, 1, 0, $conditions);
            $first_subject = $res['list'][0];
            if($first_subject == $subject_id) {
                // 符合首贴奖励规则

            }
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
     * 活动用户商品展示
     * 已发帖则下沉
     * */
    public function getActiveUserItemsSortList($active_id, $user_id)
    {

    }

    /*
     * 获取活动对应商品首贴
     * */
    public function getActiveItemFirstSubject($active_id, $item_id = 0, $status = [1], $conditions = [])
    {
        if(empty($active_id)) {
            return $this->succ([]);
        }
        $res = $this->activeModel->getFirstItemSubject($active_id, $item_id, $status, $conditions);
        return $this->succ($res);
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
        $return['prize_num'] = $res['prize_num'];

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
            $offset = $page > 1 ? ($page - 1) * $limit : 0;
            $list = $this->activeModel->getActiveUserSubjectList($active_id, $user_id, $limit, $offset, [1], $conditions);
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
     * 消消乐活动配置
     * */
    private function getActiveConfig($configure, $type = '')
    {
        if(empty($configure)) {
            return false;
        }
        $config = F_Ice::$ins->workApp->config->get('busconf.active')[$configure];
        if(!empty($type)) {
            if(empty($config[$type])) {
                return false;
            }
            return $config[$type];
        }
        return $config;
    }
    
}

