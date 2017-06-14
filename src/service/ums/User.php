<?php
namespace mia\miagroup\Service\Ums;

use \F_Ice;
use mia\miagroup\Lib\Service;
use mia\miagroup\Model\Ums\User as UserModel;
use mia\miagroup\Service\User as UserService;
use mia\miagroup\Service\Label;
use mia\miagroup\Ums\Service\Praise;
use mia\miagroup\Ums\Service\Comment;

class User extends Service{
    
    private $userModel;
    
    public function __construct(){
        parent::__construct();
        $this->userModel = new UserModel();
    }
    
    /**
     * 获取用户列表（筛选项为用户id，昵称，手机号，推荐状态，屏蔽状态，发布时间）
     * 用户id,发布时间：直接查user表
     * 昵称，手机号：通过昵称，手机号在user表查user_id
     * 用户状态：已推荐的查推荐的user_id,已屏蔽的查屏蔽表中的user_id,全部的直接查user表
     * 用户权限：视频权限的查有视频权限的user_id,专栏权限的查有专栏权限的user_id,
     */
    
    /**
     * ums获取用户列表
     */
    public function getUserList($params) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        $userService = new UserService();
        $labelService = new Label();
        //初始化入参
        $orderBy = 'user_id DESC';
        $limit = intval($params['limit']) > 0 && intval($params['limit']) < 100 ? $params['limit'] : 20;
        $offset = intval($params['page']) > 1 ? ($params['page'] - 1) * $limit : 0;
        $userIds = array();
        
        //没有任何查询的时候，默认查用户
        if(empty($params['user_id']) && empty($params['nick_name'])
            && empty($params['user_name']) && empty($params['phone']) && empty($params['category'])
            && empty($params['permission'])) {
                $users = $this->userModel->getUserIdList($offset,$limit);
                if(!empty($users) && !empty($users['uids'])){
                    $userIds = $users['uids'];
                    $count = $users['count']['nums'];
                }
        }
        
        //用户信息的查询（用户id、用户名、昵称、手机号，这些都为单条）
        if (intval($params['user_id']) > 0) {
            //用户ID
            $userId= $params['user_id'];
        }

        if(!empty($params['nick_name']) && !isset($userId)){
            $userId = $this->userModel->getUidByNickName($params['nick_name']);
        }
        
        if(!empty($params['user_name']) && !isset($userId)){
            $userId = $this->userModel->getUidByUserName($params['user_name']);
        }
        if(!empty($params['phone'])  && !isset($userId)){
            $userId = $this->userModel->getUidByPhone($params['phone']);
        }
        if(isset($userId)){
            $userIds = array($userId);
            $count = 1;
        }
        //用户类型查询（全部、屏蔽）
        if($params['status'] > 0 && in_array($params['status'],array(1))  && !isset($userId)){
            if($params['status'] == 1){
                $userArr = $this->userModel->getShieldUserIdList(array(),$offset,$limit);
            }
        }
        //用户分类查询（全部、达人、官方认证、商家/店铺）
        if(!empty($params['category']) && in_array($params['category'],array("doozer","official_cert","company"))  && !isset($userId)){
            //如果选择了权限筛选项，则查询这些类别的权限
            if(!empty($params['permission']) && in_array($params['permission'],array("video","album","live"))){
                if($params['permission'] == "live"){
                    $userArr = $this->userModel->getLiveUserIdList($offset,$limit,$params['category'],$params['sub_category']);
                }else{
                    $userArr = $this->userModel->getPermissionUserIdList($params['permission'],$offset,$limit,$params['category'],$params['sub_category']);
                }
            }else{
                //如果有二级分类，则查二级分类（达人分类下目前只有专家）
                if(!empty($params['sub_category'])){
                    $userArr = $this->userModel->getGroupUserIdList($params['category'],$params['sub_category'],$offset,$limit);
                }else{
                    $userArr = $this->userModel->getGroupUserIdList($params['category'],"",$offset,$limit);
                }
            }
        }
        //用户类型查询（全部用户、视频、专栏、直播）
        if(!empty($params['permission']) && in_array($params['permission'],array("video","album","live"))  && !isset($userId) && !isset($userArr)){
            if($params['permission'] == "live"){
                $userArr = $this->userModel->getLiveUserIdList($offset,$limit);
            }else{
                $userArr = $this->userModel->getPermissionUserIdList($params['permission'],$offset,$limit);
            }
        }
        if(isset($userArr) and !empty($userArr['uids'])){
            $userIds = $userArr['uids'];
            $count = $userArr['count']['nums'];
        }
        
        //查询用户是否是屏蔽用户
        $shieldUsers = $this->userModel->getShieldUserIdList($userIds);
        $shieldArr = array();
        if(!empty($shieldUsers['uids'])){
            foreach($shieldUsers['uids'] as $shieldUser){
                $shieldArr[$shieldUser] = $shieldUser;
            }
        }

        $userInfos = $userService->getUserInfoByUids($userIds,0,array('count'))['data'];
        $userCate = $userService->getBatchCategoryUserInfo($userIds)['data'];
        $userArr = array();
        //拼接用户的屏蔽状态
        if(!empty($userInfos)){
            foreach($userInfos as $key=>$userInfo){
                //拼接用户分类类型
                if(!empty($userCate[$key])){
                    switch ($userCate[$key]['type']) {
                        case 'doozer':
                            $userInfo['category'] = "达人";
                            break;
                        case 'official_cert':
                            $userInfo['category'] = "官方认证";
                            break;
                        case 'company':
                            $userInfo['category'] = "商家/店铺";
                            break;
                    }
                    $userInfo['en_category'] = $userCate[$key]['type'];
                    $userInfo['rec_desc'] = $userCate[$key]['desc'] ? explode('#', trim($userCate['doozer'][$key]['desc'],'#')) : '';
                    $userInfo['rec_label'] = $userCate[$key]['label'] ? trim($userCate['doozer'][$key]['label'],'#') : '';
                    if(!empty($userCate[$key]['category'])){
                        $userInfo['category'] .= "/".$userCate[$key]['category'];
                        $userInfo['sub_category'] = $userCate[$key]['category'];
                    }
                    if(!empty($userInfo['rec_label'])){
                        $label_ids = explode('#', $userInfo['rec_label']);
                        $userInfo['labels'] = array_values($labelService->getBatchLabelInfos($label_ids)['data']);
                    }
                }
                $temp = $userInfo;
                if(isset($shieldArr[$key])){
                    $temp['is_shield'] = 1;
                }else{
                    $temp['is_shield'] = 0;
                }
                $userArr[] = $temp;
            }
        }
        $result['list'] = $userArr;
        $result['count'] = $count;
        return $this->succ($result);
    }

    /**
     * 获取用户的一级类目和二级类目
     */
    public function getUserCategory() {
        $userCategory = F_Ice::$ins->workApp->config->get('busconf.user.userCategory');
        return $this->succ($userCategory);
    }

    /**
     * 格式化用户分段下拉列表
     */
    public function formatUserPeriodList() {
        $result = ['user_status' => [], 'pregnancy' => [], 'child' => []];
        $config_key = ['pregnancy', 'child'];
        $result['user_status'] = F_Ice::$ins->workApp->config->get('busconf.user.user_status');
        foreach ($config_key as $period) {
            $config_path = 'busconf.user.' . $period . '_period';
            $user_period = F_Ice::$ins->workApp->config->get($config_path);
            foreach ($user_period as $key1 => $period_list) {
                $result[$period]['first'][] = $key1;
                foreach ($period_list as $key2 => $value) {
                    $start = date('Y-m-d', strtotime($value['start']));
                    $end = date('Y-m-d', strtotime($value['end']));
                    $result[$period]['second'][$key1][$key2] = "{$start}~{$end}";
                }
            }
        }
        return $result;
    }
 
    /**
     * ums获取用户分组列表
     */
    public function getUserGroupList($params=null) {
        $conditon = array();
        if($params['role_id']){
            $conditon['role_id'] = $params['role_id'];
        }
        if($params['status']){
            $conditon['status'] = $params['status'];
        }
        
        $groupArr = $this->userModel->getUserGroup($conditon);
        return $this->succ($groupArr);
    }
    
    
    /**
     * ums用户分组数据统计
     */
    public function getUserGroupStatistics($params) {
        //print_r($params);exit;
        $result = array('list' => array(), 'count' => 0);
        $solrCond = array();
        $cond = array();
        //初始化入参
        $orderBy = array(); //默认排序
        $GroupBy = 'user_id'; //默认分组
        
        $limit = intval($params['limit']) ;
        $offset = intval($params['page']) > 1 ? ($params['page'] - 1) * $limit : 0;
        //用户分组
        if (!empty($params['role_id'])) {
            $solrCond['role_id'] = $params['role_id'];
        }
        
        //用户类型
        if (!empty($params['category'])) {
            $solrCond['c_type'] = $params['category'];
        }
        
        //活动
        if (!empty($params['active_id'])) {
            $solrCond['active_id'] = $params['active_id'];
        }
        
        //标签
        if (!empty($params['label'])) {
            $solrCond['label'] = $params['label'];
        }
        
        if (strtotime($params['start_time']) > 0) {
            //起始时间
            $solrCond['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0) {
            //结束时间
            $solrCond['end_time'] = $params['end_time'];
            $cond['end_time'] = $params['end_time'];
        }
        
        if(strtotime($params['start_date']) > 0){
            $cond['start_time'] = $params['start_date'];
        }
        
        if(empty($solrCond)){
            return $this->succ($result);
        }
        
        $solrData = $this->_getSolrGroupCount($solrCond, '*',$limit,$offset,$orderBy,$GroupBy);
        //根据用户id获取用户赞数和评论数，通过数据库统计
        //（这两个字段只能通过时间筛选，默认是前一天之前用户所有的赞数和评论数）
        //评论和赞的日期
        $cond['user_id'] = $solrData['user_id'];
        $cond['status'] = array(1);
        //##########start
        //批量获取用户赞数
        $praiseService = new \mia\miagroup\Service\Ums\Praise();
        $praiseInfos = $praiseService->getPraiseCount($cond)['data'];
        
        //批量获取用户评论数
        $commentService = new \mia\miagroup\Service\Ums\Comment();
        $commentInfos = $commentService->getCommentCount($cond)['data'];
        //##########end
        
        if(!empty($solrData['user_id'])){
            foreach ($solrData['user_id'] as $userId) {
                $tmp['user_id'] = $solrData['all'][$userId]['user_id'];
                $tmp['username'] = $solrData['all'][$userId]['username'];
                $tmp['nickname'] = $solrData['all'][$userId]['nickname'];
                $tmp['issue_num'] = isset($solrData['all'][$userId]['count']) ? $solrData['all'][$userId]['count'] : 0;//用户发帖数量
                $tmp['shield_num'] = isset($solrData['shield'][$userId]['count']) ? $solrData['shield'][$userId]['count'] : 0;
                $tmp['fine_num'] = isset($solrData['fine'][$userId]['count']) ? $solrData['fine'][$userId]['count'] : 0;
                $tmp['praise_num'] = isset($praiseInfos[$userId]) ? $praiseInfos[$userId] : 0;
                $tmp['comment_num'] = isset($commentInfos[$userId]) ? $commentInfos[$userId] : 0;
                
                $result['list'][$userId] = $tmp;
            }
        }
        if(empty($result['list'])){
            return $this->succ($result);
        }
        
        $result['count'] = $solrData['count'];
        return $this->succ($result);
        
    }
    
    //获取solr统计的分组数据(发帖，精华帖，屏蔽贴)
    private function _getSolrGroupCount($solrCond,$fileds,$limit,$offset,$orderBy,$GroupBy){
        $result = array();
        $countType = array("all","fine","shield");
        //$solrCond['group'] = 'user_id';
        $solr = new \mia\miagroup\Remote\Solr('pic_search', 'group_search_solr');
        
        foreach($countType as $type){
            $solrWhere = $solrCond;
            $groupBy = array();
            $groupBy['group']['field'] = 'user_id';
            
            if($type == 'all'){
                $solrData[$type] = $solr->getSeniorSolrSearch($solrWhere, $fileds, $offset, $limit, $orderBy,$groupBy);
                $userIds = array_column($solrData['all']['data']['grouped']['user_id']['groups'], 'groupValue');
            }else{
                $solrWhere['user_id'] = $userIds;
                if($type == 'shield'){
                    $solrWhere['status'] = "[* TO -1]";
                }
                if($type == 'fine'){
                    $solrWhere['is_fine'] = 1;
                }
                $solrData[$type] = $solr->getSeniorSolrSearch($solrWhere,$fileds, $offset, $limit, $orderBy,$groupBy);
            }
            unset($solrWhere);
            
        }
        $count = $solr->getSeniorSolrSearch($solrCond, $fileds, $offset, $limit, $orderBy, array('count'=>'user_id'));
        $result['count'] = $count['data']['facets']['count'];
        //获取用户附加信息
        $userService = new UserService();
        $userInfos = $userService->getUserInfoByUids($userIds,0,array('count'))['data'];
        foreach($solrData as $key=>$solr){
            foreach($solr['data']['grouped']['user_id']['groups'] as $groups){
                $result[$key][$groups['groupValue']]['user_id'] = $groups['groupValue'];
                $result[$key][$groups['groupValue']]['username'] = $userInfos[$groups['groupValue']]['username'];
                $result[$key][$groups['groupValue']]['nickname'] = $userInfos[$groups['groupValue']]['nickname'];
                $result[$key][$groups['groupValue']]['count'] = $groups['doclist']['numFound'];
            }
        }
        
        $result['user_id'] = $userIds;
        return $result;
    }
    
    /*
     * 蜜芽圈帖子综合搜索
     * 用户运营分组
     * */
    public function group_user_role()
    {
        $group_user_role = $this->userModel->getGroupUserRole();
        return $this->succ($group_user_role);
    }
}