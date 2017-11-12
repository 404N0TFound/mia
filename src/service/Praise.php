<?php
namespace mia\miagroup\Service;

use \F_Ice;
use mia\miagroup\Service\User as UserService;
use mia\miagroup\Model\Praise as PraiseModel;
use mia\miagroup\Service\Subject as SubjectService;

class Praise extends \mia\miagroup\Lib\Service {
    
    private $praiseModel;

    public function __construct() {
        parent::__construct();
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
            $userService = new UserService();
            $userInfos = $userService->getUserInfoByUids($userIds)['data'];
            foreach ($subPraises as $subjectId => $uids) {
                foreach ($uids as $uid) {
                    if (isset($userInfos[$uid]) && $userInfos[$uid]['icon'] != F_Ice::$ins->workApp->config->get('busconf.user.defaultIcon')) {
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
    
    /**
     * 赞
     * @param unknown $iSubjectId
     * @param unknown $userId
     */
    public function dotPraise($iSubjectId, $userId)
    {
        if (!is_numeric($iSubjectId) || intval($iSubjectId) <= 0 || !is_numeric($userId) || intval($userId) <= 0) {
            return $this->error(500);
        }
        $subject = new Subject();
        //获取图片信息（包括更新图片表之前获取赞数量）
        $subjectInfo = $subject->getBatchSubjectInfos(array($iSubjectId), 0, array('user_info','count'))['data'];
        $subjectInfo = $subjectInfo[$iSubjectId];
        //检查赞是否存在
        $praiseArr = $this->praiseModel->checkIsExistFanciedByUserId($userId,$iSubjectId);
        $praiseInfo = array(
            "fancied_by_me" => true,
            "fancied_count" => $subjectInfo['fancied_count']
        );
        if (!empty($praiseArr) && $praiseArr['status'] == '1') {
            return $this->succ($praiseInfo);
        }
        $praiseId = 0;
        if (!empty($praiseArr) && $praiseArr['status'] == '0') {
            $setInfo = array(
                ["subject_id", $iSubjectId],
                ["user_id", $userId],
                ["subject_uid", $subjectInfo['user_id']],
                ["status", '1'],
                ["created", date("Y-m-d H:i:s", time())],
            );
            //赞存在且为取消掉的状态，直接更新为赞状态
            $praiseId = $this->praiseModel->updatePraiseById($setInfo, $praiseArr['id']);
        }else{
            $setData = array(
                "subject_id" => $iSubjectId,
                "user_id"    => $userId,
                "subject_uid"=> $subjectInfo['user_id'],
                "status"     => '1',
                "created"    => date("Y-m-d H:i:s", time()),
            );
            $praiseId = $this->praiseModel->insertPraise($setData);
            #start赠送用户蜜豆
            //自己给自己点赞不送蜜豆
            if ($subjectInfo['user_id'] != $userId) {
                // 收到赞+1        （以天为周期，每天收到N个赞，最多可得3次蜜豆奖励）
                $mibean = new \mia\miagroup\Remote\MiBean();
                $param['relation_type'] = 'receive_praise';
                $param['user_id'] = $userId;
                $param['relation_id'] = $iSubjectId;
                $param['to_user_id'] = $subjectInfo['user_info']['user_id'];
                $mibean->add($param);

                //长文点赞，给点赞人送1蜜豆
                if ($subjectInfo['type'] === 'blog') {
                    //检查是否互动过
                    $param_2['user_id'] = $subjectInfo['user_info']['user_id'];//帖子作者
                    $param_2['relation_type'] = 'receive_praise';
                    $param_2['relation_id'] = $iSubjectId;
                    $param_2['to_user_id'] = $userId;//评论者
                    $res = $mibean->add($param_2);
                    //长文奖励成功提示
                    $blogPrise = 1;
                }
            }
            #end赠送用户蜜豆
        }

        //长文，检测任务
        if ($subjectInfo['type'] === 'blog') {
            $taskService = new GroupTask();
            $taskService->checkBlogTask($userId);
        }
        //更新图片表之后获取赞数量及是否赞过状态
        $subjectInfo['fancied_by_me'] = true;
        $subjectInfo['fancied_count'] = $subjectInfo['fancied_count'] + 1;

        if($userId != $subjectInfo['user_info']['user_id']) {
            $news = new \mia\miagroup\Service\News();
            $news->postMessage('img_like', $subjectInfo['user_info']['user_id'], $userId, $praiseId);
        }
        $praiseInfo['fancied_by_me'] = $subjectInfo['fancied_by_me'];
        $praiseInfo['fancied_count'] = $subjectInfo['fancied_count'];
        if (isset($blogPrise) && $blogPrise == 1) {
            return $this->succ($praiseInfo, "点赞成功+1蜜豆");
        } else {
            return $this->succ($praiseInfo, "点赞成功");
        }
    }
    
    /**
     * 取消赞
     */
    public function cancelPraise($iSubjectId, $userId)
    {
        $subject = new Subject();
        //获取图片信息（包括更新图片表之前获取赞数量）
        $subjectInfo = $subject->getBatchSubjectInfos(array($iSubjectId), 0, array('count'))['data'];
        $subjectInfo = $subjectInfo[$iSubjectId];
        //检查赞是否存在
        $praiseArr = $this->praiseModel->checkIsExistFanciedByUserId($userId,$iSubjectId);
        $praiseInfo = array(
            "fancied_by_me" => false,
            "fancied_count" => $subjectInfo['fancied_count']
        );
        if (empty($praiseArr) || $praiseArr['status'] == '0') {
            return $this->succ($praiseInfo);
        }
        
        //取消赞
        $setData[] = ['status','0'];
        $where[] = ['subject_id', $iSubjectId];
        $where[] = ['user_id', $userId];
        $where[] = ['status', '1'];
        $this->praiseModel->updatePraise($setData, $where);

        //更新图片表之后获取赞数量及是否赞过状态
        $subjectInfo['fancied_by_me'] = false;
        $subjectInfo['fancied_count'] = $subjectInfo['fancied_count'] - 1;
    
        $praiseInfo = array();
        $praiseInfo['fancied_by_me'] = $subjectInfo['fancied_by_me'];
        $praiseInfo['fancied_count'] = $subjectInfo['fancied_count'];
        return $this->succ($praiseInfo);
    }
    
    /**
     * 根据赞ID批量获取赞信息
     * @param $field user_info、subject
     */
    public function getPraisesByIds($praiseIds, $field = array()) {
        if (empty($praiseIds)) {
            return $this->succ(array());
        }
        $praiseData = $this->praiseModel->getPraisesByIds($praiseIds);
        if (empty($praiseData)) {
            return $this->succ(array());
        }
        //收集uid、subject_id
        $userIds = array();
        $subjectIds = array();
        foreach ($praiseData as $v) {
            $userIds[] = $v['user_id'];
            $subjectIds[] = $v['subject_id'];
        }
        if (in_array('user_info', $field)) {
            $userService = new UserService();
            $userInfos = $userService->getUserInfoByUids($userIds)['data'];
        }
        if (in_array('subject', $field)) {
            $subjectService = new SubjectService();
            $subjects = $subjectService->getBatchSubjectInfos($subjectIds, 0, array(), array())['data'];
        }
        foreach ($praiseData as $k => $v) {
            if (in_array('user_info', $field)) {
                $praiseData[$k]['user_info'] = $userInfos[$v['user_id']];
            }
            if (in_array('subject', $field)) {
                $praiseData[$k]['subject'] = $subjects[$v['subject_id']];
            }
        }
        return $this->succ($praiseData);;
    }
    
}