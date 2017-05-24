<?php
namespace mia\miagroup\Service\Ums;

use \F_Ice;
use mia\miagroup\Lib\Service;
use mia\miagroup\Model\Ums\User as UserModel;
use mia\miagroup\Service\User as UserService;
use mia\miagroup\Service\Label;

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
                if(!empty($userCate)){
                    switch ($userCate[$userInfo['id']]['type']) {
                        case 'doozer':
                            $userInfo['category'] = "达人";
                            break;
                        case 'doozer':
                            $userInfo['category'] = "官方认证";
                            break;
                        case 'doozer':
                            $userInfo['category'] = "商家/店铺";
                            break;
                    }
                    $userInfo['en_category'] = $userCate[$userInfo['id']]['type'];
                    $userInfo['rec_desc'] = $userCate[$userInfo['id']]['desc'] ? explode('#', trim($userCate['doozer'][$userInfo['id']]['desc'],'#')) : '';
                    $userInfo['rec_label'] = $userCate[$userInfo['id']]['label'] ? trim($userCate['doozer'][$userInfo['id']]['label'],'#') : '';
                    if(!empty($userCate[$userInfo['id']]['category'])){
                        $userInfo['category'] .= "/".$userCate[$userInfo['id']]['category'];
                        $userInfo['sub_category'] = $userCate[$userInfo['id']]['category'];
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
}