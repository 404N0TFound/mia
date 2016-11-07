<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Koubei as KoubeiModel;
use mia\miagroup\Model\Ums\User as UserModel;
use mia\miagroup\Model\Ums\Subject as SubjectModel;
use mia\miagroup\Service\Subject as SubjectService;

class Subject extends \mia\miagroup\Lib\Service {
    
    public $koubeiModel;
    public $userModel;
    public $subjectModel;
    
    public function __construct() {
        $this->koubeiModel = new KoubeiModel();
        $this->userModel = new UserModel();
        $this->subjectModel = new SubjectModel();
    }
    
    public function getSubjectList($params) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        $koubeiCondtion = array();
        //初始化入参
        $orderBy = 'id desc'; //默认排序
        $limit = (intval($params['limit'] > 0) && intval($params['limit'] < 100)) ? $params['limit'] : 50;
        $offset = intval($params['page'] > 1) ? (($params['page'] - 1) * limit) : 0;
    
        if (intval($params['id']) > 0) {
            //帖子ID
            $condition['id'] = $params['id'];
        }
        if (intval($params['user_id']) > 0 && intval($condition['id']) <= 0) {
            //用户id
            $condition['user_id'] = $koubeiCondtion['user_id'] = $params['user_id'];
        }
        if (!empty($params['user_name']) && intval($condition['user_id']) <= 0 && intval($condition['id']) <= 0) {
            //用户名
            $condition['user_id'] = $koubeiCondtion['user_id'] = intval($this->userModel->getUidByUserName($params['user_name']));
        }
        if (!empty($params['nick_name']) && intval($condition['user_id']) <= 0 && intval($condition['id']) <= 0) {
            //用户昵称
            $condition['user_id'] = $koubeiCondtion['user_id'] = intval($this->userModel->getUidByNickName($params['nick_name']));
        }
        if ($params['status'] !== null && $params['status'] !== '' && in_array($params['status'], array(0, 1, -1)) && intval($condition['id']) <= 0) {
            //帖子状态
            $condition['status'] = $params['status'];
        }
        if ($params['is_fine'] !== null && $params['is_fine'] !== '' && in_array($params['is_fine'], array(0, 1)) && intval($condition['id']) <= 0) {
            //是否是推荐
            $condition['is_fine'] = $params['is_fine'];
        }
        if ($params['is_top'] !== null && $params['is_top'] !== '' && in_array($params['is_top'], array(0, 1)) && intval($condition['id']) <= 0) {
            //是否是置顶
            $condition['is_top'] = $params['is_top'];
        }
        if ($params['is_audited'] !== null && $params['is_audited'] !== '' && in_array($params['is_audited'], array(0, 1)) && intval($condition['id']) <= 0) {
            //是否已同步口碑
            $koubeiCondtion['is_audited'] = $params['is_audited'];
        }
        if (intval($params['item_id']) > 0 && intval($condition['id']) <= 0) {
            //商品ID
            $koubeiCondtion['item_id'] = $params['item_id'];
        }
        if (strtotime($params['start_time']) > 0 && intval($condition['id']) <= 0) {
            //起始时间
            $condition['start_time'] = $koubeiCondtion['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0 && intval($condition['id']) <= 0) {
            //结束时间
            $condition['end_time'] = $koubeiCondtion['end_time'] = $params['end_time'];
        }
        if (isset($koubeiCondtion['is_audited']) || isset($koubeiCondtion['item_id'])) {
            $orderBy = 'koubei_subjects.id desc';
            $data = $this->koubeiModel->getSubjectData($koubeiCondtion, $offset, $limit, $orderBy);
        } else {
            $data = $this->subjectModel->getSubjectData($condition, $offset, $limit, $orderBy);
        }
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
        $subjectService = new SubjectService();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array('user_info', 'item', 'album','group_labels','count'), array())['data'];
        foreach ($data['list'] as $v) {
            $tmp = $v;
            $tmp['subject'] = $subjectInfos[$v['subject_id']];
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }
    
        
}