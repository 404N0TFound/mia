<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Active as ActiveModel;

class Active extends \mia\miagroup\Lib\Service {

    private $activeModel;
    private $userModel;
    private $subjectModel;

    public function __construct() {
        parent::__construct();
        $this->activeModel = new ActiveModel();
        $this->userModel = new \mia\miagroup\Model\Ums\User();
    }

    /*
     * 蜜芽圈帖子综合搜索
     * 用户活动
     * */
    public function group_user_active($month = 1)
    {
        $group_active = $this->activeModel->getGroupActiveData($month);
        return $this->succ($group_active);
    }
    
    /**
     * 获取活动图片列表
     */
    public function getActiveSubjectList($params) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        //初始化入参
        $orderBy = 'id desc'; //默认排序
        if ($params['limit'] === false) {
            $limit = false;
        } else {
            $limit = intval($params['limit']) > 0 && intval($params['limit']) < 100 ? $params['limit'] : 20;
        }
        $offset = intval($params['page']) > 1 ? ($params['page'] - 1) * $limit : 0;
        if (intval($params['active_id']) > 0) {
            //活动id
            $condition['active_id'] = $params['active_id'];
        }
        if (intval($params['user_id']) > 0) {
            //用户id
            $condition['user_id'] = $params['user_id'];
        }
        if (!empty($params['user_name']) && intval($condition['user_id']) <= 0) {
            //用户名
            $condition['user_id'] = intval($this->userModel->getUidByUserName($params['user_name']));
        }
        if (!empty($params['nick_name']) && intval($condition['user_id']) <= 0) {
            //用户昵称
            $condition['user_id'] = intval($this->userModel->getUidByNickName($params['nick_name']));
        }
        if ($params['is_fine'] !== null && $params['is_fine'] !== '' && in_array($params['is_fine'], array(0, 1))) {
            //是否是推荐
            $condition['is_fine'] = $params['is_fine'];
        }
        if (strtotime($params['start_time']) > 0) {
            //起始时间
            $condition['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0) {
            //结束时间
            $condition['end_time'] = $params['end_time'];
        }
        $data = $this->activeModel->getActiveSubjectList($condition, $offset, $limit, $orderBy);
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $subjectIds = array();
        foreach ($data['list'] as $v) {
            if(empty($v['subject_id'])){
                continue;
            }
            $subjectIds[] = $v['subject_id'];
        }
        $subjectService = new \mia\miagroup\Service\Subject();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array('user_info', 'item', 'album','group_labels','count','content_format', 'share_info'), array())['data'];
        foreach ($data['list'] as $v) {
            $tmp = $v;
            $tmp['subject'] = $subjectInfos[$v['subject_id']];
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }
}