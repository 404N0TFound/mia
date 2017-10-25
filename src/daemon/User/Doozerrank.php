<?php
namespace mia\miagroup\Daemon\User;

/**
 * 达人帖子发布排行
 */
class Doozerrank extends \FD_Daemon {
    
    private $subjectData;
    private $userCategoryData;
    private $subjectPraiseData;
    private $userModel;
    private $feedModel;
    private $praiseData;
    private $subjectService;

    public function __construct() {
        $this->subjectData = new \mia\miagroup\Data\Subject\Subject();
        $this->userCategoryData = new \mia\miagroup\Data\User\GroupUserCategory();
        $this->subjectPraiseData = new \mia\miagroup\Data\Praise\SubjectPraise();
        $this->userModel = new \mia\miagroup\Model\User();
        $this->feedModel = new \mia\miagroup\Model\Feed();
        $this->subjectService = new \mia\miagroup\Service\Subject();
    }

    public function execute() {
        $mode = $this->request->argv[0];
        if (!in_array($mode, array('pub_day', 'pub_month'))) {
            return ;
        }
        switch ($mode) {
            case 'pub_day':
                $start_time = date('Y-m-d H:i:s', time() - 86400);
                $end_time = date('Y-m-d H:i:s');
                break;
            case 'pub_month':
                $start_time = date('Y-m-d H:i:s', time() - 86400 * 30);
                $end_time = date('Y-m-d H:i:s');
                break;
        }
        //获取所有达人
        $daren_list = $this->userCategoryData->getGroupUserIdList('doozer', 0, false);
        //获取排行榜
        $rank_list = $this->subjectData->getPushlishRankByTime($daren_list, $start_time, $end_time, array('source' => 1), 0, 50);
        //更新排行榜
        $this->userModel->updateDoozerRank($mode, $rank_list);
        //更新用户热门帖子
        foreach ($rank_list as $user_id => $pub_count) {
            $hot_num = 10;
            $condition = ['user_id' => $user_id, 'is_fine' => 1];
            $subject_ids = $this->subjectData->getSubjectList($condition, 0, $hot_num);
            if ($hot_num - count($subject_ids) > 0) {
                //获取我发布的帖子列表
                $subjectIds = $this->feedModel->getSubjectListByUids([$user_id], 1, 50);
                $data = $this->subjectService->getBatchSubjectInfos($subjectIds, 0, array())['data'];
                $i = 0;
                $j = $hot_num - count($subject_ids);
                while ($i <= $j && !empty($data)) {
                    $subject_info = array_shift($data);
                    if (!in_array($subject_info['id'], $subject_ids) && !empty($subject_info['image_infos'])) {
                        $subject_ids[] = $subject_info['id'];
                        $i ++;
                    }
                }
            }
            $data = [];
            foreach ($subject_ids as $id) {
                $data[$id] = 0;
            }
            $subject_ids = $data;
//             $subject_ids = $this->subjectPraiseData->getUserMostPraisedSubjects($user_id, $start_time, $end_time, [], 0, $hot_num);
//             //如果被赞过的帖子不足10条，补充带图的
//             if ($hot_num - count($subject_ids) > 0) {
//                 //获取我发布的帖子列表
//                 $subjectIds = $this->feedModel->getSubjectListByUids([$user_id], 1, 50);
//                 $data = $this->subjectService->getBatchSubjectInfos($subjectIds, 0, array())['data'];
//                 $i = 0;
//                 $j = $hot_num - count($subject_ids);
//                 while ($i <= $j && !empty($data)) {
//                     $subject_info = array_shift($data);
//                     if (!isset($subject_ids[$subject_info['id']]) && !empty($subject_info['image_infos'])) {
//                         $subject_ids[$subject_info['id']] = 0;
//                         $i ++;
//                     }
//                 }
//             }
            $this->userModel->updateUserHotSubject($user_id, $subject_ids);
        }
    }
}