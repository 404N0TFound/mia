<?php
namespace mia\miagroup\Service;

use \F_Ice;
use mia\miagroup\Model\User as UserModel;
use mia\miagroup\Service\UserRelation;
use mia\miagroup\Service\Subject;
use mia\miagroup\Service\Album;
use mia\miagroup\Service\Live;
use mia\miagroup\Util\NormalUtil;
use mia\miagroup\Service\Label as labelService;

class User extends \mia\miagroup\Lib\Service {

    public $userModel = null;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    /**
     * 通过用户id批量获取用户信息
     *
     * @param array $userIds     
     * @param array $fields,
     * 包括count、relation、cell_phone等
     * @return array
     */
    public function getUserInfoByUids(array $userIds, $currentUid = 0, array $fields = array()) {
        $userArr = array();
        if (empty($userIds)) {
            return array();
        }
        $userInfos = $this->userModel->getUserInfoByIds($userIds);

        if (empty($userInfos)) {
            return array();
        }
        // 如果是登陆用户，获取登录用户和发帖子用户关注的关系
        if (intval($currentUid) > 0) {
            $userRelation = new UserRelation();
            $relationWithMe = $userRelation->getUserRelationWithMe($currentUid, $userIds)['data'];
            $relationWithHim = $userRelation->getMeRelationWithUser($currentUid, $userIds)['data'];
        }
        // 批量获取用户的关注数和粉丝数
        if (in_array('count', $fields)) {
            if (!isset($userRelation)) {
                $userRelation = new UserRelation();
            }
            $subjectService = new Subject();
            $albumService = new Album();
            $userFansCount = $userRelation->countBatchUserFanS($userIds)['data']; // 用户粉丝数
            $userAttenCount = $userRelation->countBatchUserAtten($userIds)['data']; // 用户关注数
            $userSubjectsCount = $subjectService->getBatchUserSubjectCounts($userIds); // 用户发布数
            $userArticleCount = $albumService->getArticleNum($userIds)['data'];//用户文章数
            $userSubjectsCount = $userSubjectsCount['data'];
        }
        // 批量获取专家信息
        $expertInfos = $this->getBatchExpertInfoByUids($userIds)['data'];
        // 批量获取直播权限
        $liveService = new Live();
        $liveAuths = $liveService->checkLiveAuthByUserIds($userIds)['data'];
        
        $labelService = new labelService();
        foreach ($userInfos as $userInfo) {
            $userInfo['icon'] = $userInfo['icon'] ? $userInfo['icon'] : F_Ice::$ins->workApp->config->get('busconf.user.defaultIcon');
            $userInfo['is_have_live_permission'] = $liveAuths[$userInfo['id']];
            $userInfo['is_experts'] = !empty($expertInfos[$userInfo['id']]) ? 1 : 0; // 用户是否是专家
            if ($expertInfos[$userInfo['id']]) {
                $expertInfos[$userInfo['id']]['desc'] = !empty(trim($expertInfos[$userInfo['id']]['desc'])) ? explode('#', trim($expertInfos[$userInfo['id']]['desc'], "#")) : array();
                if (!empty(trim($expertInfos[$userInfo['id']]['label'], "#"))) {
                    $expert_label_ids = explode('#', trim($expertInfos[$userInfo['id']]['label'], "#"));
                    $expertInfos[$userInfo['id']]['label'] = array_values($labelService->getBatchLabelInfos($expert_label_ids)['data']);
                } else {
                    $expertInfos[$userInfo['id']]['label'] = [];
                }
                $userInfo['experts_info'] = $expertInfos[$userInfo['id']];
            }
            
            if (intval($currentUid) > 0) {
                if (!empty($relationWithMe) && $relationWithMe[$userInfo['id']] > 0) {
                    $userInfo['relation_with_me'] = $relationWithMe[$userInfo['id']]['relation_with_me'];
                } else {
                    $userInfo['relation_with_me'] = 0;
                }
                if (!empty($relationWithHim) && $relationWithHim[$userInfo['id']] > 0) {
                    $userInfo['relation_with_him'] = $relationWithHim[$userInfo['id']]['relation_with_him'];
                } else {
                    $userInfo['relation_with_him'] = 0;
                }
            }
            
            if (in_array('count', $fields)) {
                $userInfo['fans_count'] = intval($userFansCount[$userInfo['id']]); // 用户粉丝数
                $userInfo['focus_count'] = intval($userAttenCount[$userInfo['id']]); // 用户关注数
                $userInfo['pic_count'] = intval($userSubjectsCount[$userInfo['id']]); // 用户发布数
                $userInfo['article_count'] = intval($userArticleCount[$userInfo['id']]); // 用户文章数
            }
            if (!in_array('cell_phone', $fields)) {
                unset($userInfo['cell_phone']);
            }
            $userArr[$userInfo['id']] = $this->_optimizeUserInfo($userInfo, $currentUid)['data'];
        }
        
        return $this->succ($userArr);
    }
    
    // 批量获取专家信息
    public function getBatchExpertInfoByUids($userIds) {
        if (empty($userIds)) {
            return array();
        }
        
        $userModel = new UserModel();
        $data = $userModel->getBatchExpertInfoByUids($userIds);
        return $this->succ($data);
    }

    /**
     *
     * @param array $userInfo            
     * @return array
     */
    private function _optimizeUserInfo($userInfo, $currentUid = 0) {
        $userInfo['user_id'] = $userInfo['id'];
        unset($userInfo['id']);
        // unset($userInfo['id']);
        foreach ($userInfo as $key => $value) {
            if (is_null($value)) {
                $userInfo[$key] = '';
            }
        }
        if ($userInfo['icon'] != '' && !preg_match("/^(http|https):\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"])*$/", $userInfo['icon'])) {
            $userInfo['icon'] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $userInfo['icon'];
        }
        $userInfo['username'] = preg_replace('/(miya[\d]{3}|mobile_[\d]{3})([\d]{4})([\d]{4})/', "$1****$3", $userInfo['username']);
        if (!$userInfo['nickname']) {
            $userInfo['nickname'] = $userInfo['username'];
        }
        
        if (in_array($userInfo['user_status'], array(1, 2))) {
            
            $userInfo['child_age'] = NormalUtil::birth_day_change($userInfo['child_birth_day']);
            $childAgeInfo = NormalUtil::getAgeByBirthday($userInfo['child_birth_day']);
            if ($childAgeInfo) {
                $userInfo['child_age_info'] = $childAgeInfo;
            }
        } else {
            unset($userInfo['child_sex']);
        }
        $userInfo['level'] = intval($userInfo['level']);
        $userInfo['level_id'] = NormalUtil::getConfig('busconf.member.level_info')[$userInfo['level']]['level_id']; // 用户等级ID
        if(substr($this->ext_params['version'],-5,3) == '4_6'){
            $userInfo['level_number'] = NormalUtil::getConfig('busconf.member.level_info')[$userInfo['level']]['level']; // 用户等级
            $userInfo['level'] = NormalUtil::getConfig('busconf.member.level_info')[$userInfo['level']]['level_name']; // 用户等级名称
        }else{
            $userInfo['level'] = '';
            $userInfo['level_number'] = 0;
        }
        $userInfo['status'] = $userInfo['status'];
        
        return $this->succ($userInfo);
    }

    /**
     * 获取单个用户的信息
     *
     * @param unknown $userId            
     * @param unknown $field
     *            包括'push_switch', 'mibean', 'count', 'cell_phone', 'jifen'等
     * @param number $currentUid
     *            当需要获取关注关系时传入
     */
    public function getUserInfoByUserId($userId, $field = array(), $currentUid = 0) {
        if (!$userId || intval($userId) <= 0) {
            return false;
        }
        $userInfo = $this->getUserInfoByUids(array($userId), $currentUid, $field)['data'];
        $userInfo = isset($userInfo[$userId]) ? $userInfo[$userId] : array();
        
        if (in_array('push_switch', $field)) {
            $pushSwitch = $this->userModel->getPushSwitchByUserIds($userInfo['id']);
            $userInfo['push_switch'] = 0;
            if (!empty($pushSwitch)) {
                $userInfo['push_switch'] = $pushSwitch['push_switch'];
            }
        }
        if (in_array('mibean', $field)) {
            // $this->load->model('mibean_model', 'mBean');
            // $miBeanInfo = $this->mBean->currentLevelToNextLevelInfo($userInfo['user_id']);
            // $userInfo['mibean'] = $miBeanInfo['count'] > 0 ? $miBeanInfo['count'] : 0;
        }
        if (in_array('jifen', $field)) {
            // $userInfo['score'] = $this->getJifenBalance($userInfo['user_id']);
        }
        
        return $this->succ($userInfo);
    }


    /**
     * 头条导入用户
     */
    public function syncHeadLineUser($userinfo) {
        $username = mb_strlen($userinfo['username'], 'utf8') > 18 ? mb_substr($userinfo['username'], 0, 18) : $userinfo['username'];
        $nickname = mb_strlen($userinfo['nickname'], 'utf8') > 16 ? mb_substr($userinfo['nickname'], 0, 16) : $userinfo['nickname'];
        $avatar = $userinfo['avatar'];
        $category = $userinfo['category'];
        $checkExist = $userinfo['checkExist'];
        $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);
        //如果checkExist==1，nickname重复不再生成新用户
        if ($checkExist == 1) {
            $userId = $this->userModel->getUidByNickName($nickname);
            if (intval($userId) > 0) {
                //用户归类
                $this->userModel->setHeadlineUserCategory($userId, $category);
                return $this->succ(array('uid' => $userId, 'is_exist' => 1));
            }
        }
        //校验userName是否已存在
        $userId = $this->userModel->getUidByUserName($username);
        if (intval($userId) > 0) {
            //更新用户信息
            $setData[] = array('nickname', $nickname);
            $setData[] = array('icon', $avatar);
            $this->userModel->updateUserById($userId, $setData);
            //用户归类
            $this->userModel->setHeadlineUserCategory($userId, $category);
            return $this->succ(array('uid' => $userId, 'is_exist' => 1));
        }
        //主表插入
        $userInfo['username'] = $username;
        $userInfo['nickname'] = $nickname;
        $userInfo['icon'] = $avatar;
        $userInfo['password'] = 'a255220a91378ba2f4aad17300ed8ab7';
        $userInfo['group_id'] = 10;
        $userInfo['relation'] = 3;
        $userInfo['create_date'] = date('Y-m-d H:i:s');
        $userId = $this->userModel->addUser($userInfo);
        \DB_Query::switchCluster($preNode);
        if (intval($userId) > 0) {
            //用户归类
            $this->userModel->setHeadlineUserCategory($userId, $category);
            return $this->succ(array('uid' => $userId, 'is_exist' => 0));
        } else {
            return $this->error(500);
        }
    }
    
}
