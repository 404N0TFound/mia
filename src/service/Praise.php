<?php
namespace mia\miagroup\Service;

use mia\miagroup\Service\User as UserService;
use mia\miagroup\Model\Praise as PraiseModel;

class Praise extends \FS_Service {
    
    private $userService;
    private $praiseModel;

    public function __construct() {
        $this->userService = new UserService();
        $this->praiseModel = new PraiseModel();
    }
    
    /**
     * 根据subjectIds批量分组获取选题的赞用户
     */
    public function getBatchSubjectPraiseUsers($subjectIds, $count = 20) {
        if (empty($subjectIds)) {
            return $this->succ();
        }
        $subPraises = $this->praiseModel->getBatchSubjectPraiseUsers($subjectIds, $count);
        $userIds = array();
        if(!empty($subPraises)){
            foreach ($subPraises as $ids) {
                $userIds = array_merge($userIds, $ids);
            }
            $userInfos = $this->userService->getUserInfoByUids($userIds)['data'];
            foreach ($subPraises as $subjectId => $uids) {
                foreach ($uids as $uid) {
                    if (isset($userInfos[$uid]) && !empty($userInfos[$uid]['icon'])) {
                        $tmpInfo['user_id'] = $userInfos[$uid]['user_id'];
                        $tmpInfo['username'] = $userInfos[$uid]['username'];
                        $tmpInfo['nickname'] = $userInfos[$uid]['nickname'];
                        $tmpInfo['icon'] = $userInfos[$uid]['icon'];
                        $subPraisesList[$subjectId][$uid] = $tmpInfo;
                    } else {
                        unset($subPraisesList[$subjectId][$uid]);
                    }
                }
            }
        }
        return $this->succ($subPraisesList);
    }
    
    /**
     * 根据subjectIds批量分组查贊数
     * @params array() 图片ids
     * @return array() 图片赞数列表
     */
    public function getBatchSubjectPraises($subjectIds) {
        $subjectPraises = $this->praiseModel->getBatchSubjectPraises($subjectIds);
        return $this->succ($subjectPraises);
    }
    
    /**
     * 批量查贊信息，用于查某用户的赞状态
     * @params array $subjectIds 图片ids
     * @param int $userId  登录用户id
     * @return array 某个用户的赞信息
     */
    public function getBatchSubjectIsPraised($subjectIds, $userId) {
        $isPraised = $this->praiseModel->getBatchSubjectIsPraised($subjectIds, $userId);
        return $this->succ($isPraised);
    }
}