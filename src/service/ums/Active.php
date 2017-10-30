<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Active as ActiveModel;

class Active extends \mia\miagroup\Lib\Service {

    private $activeModel;
    private $userModel;
    private $subjectModel;
    private $activeService;

    public function __construct() {
        parent::__construct();
        $this->activeModel = new ActiveModel();
        $this->userModel = new \mia\miagroup\Model\Ums\User();
        $this->activeService = new \mia\miagroup\Service\Active();
    }

    /*
     * 蜜芽圈帖子综合搜索
     * 用户活动(1:正常上线活动，：上线+下线)
     * */
    public function group_user_active($month = 1, $status = NULL)
    {
        $group_active = $this->activeModel->getGroupActiveData($month, $status);
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
    
    /**
     * 获取用户参加活动关联的商品
     */
    public function getRelationItemsByActiveId($params) {
        $result = array('list' => array(), 'count' => 0);
        if (empty($params['active_id'])) {
            return $this->succ($result);
        }
        $params['offset'] = 0;
        $params['limit'] = false;
        $data = $this->getActiveSubjectList($params)['data'];
        if (empty($data)) {
            return $this->succ($result);
        }
        $items = [];
        foreach ($data['list'] as $v) {
            if (empty($v['subject']['items'])) {
                continue;
            }
            foreach ($v['subject']['items'] as $item) {
                $items[$item['item_id']] = $item;
            }
        }
        $result['list'] = $items;
        $result['count'] = count($items);
        return $this->succ($result);
    }
    
    /**
     * 获取活动中关联某些商品的帖子
     */
    public function getActiveSubjectCountByItems($params) {
        $result = array();
        if (empty($params['item_id']) || empty($params['active_id'])) {
            return $this->succ($result);
        }
        
        if (!isset($params['limit'])) {
            $limit = false;
        } else {
            $limit = intval($params['limit']) > 0 && intval($params['limit']) < 100 ? $params['limit'] : 20;
        }
        $offset = intval($params['page']) > 1 ? ($params['page'] - 1) * $limit : 0;
        
        $condition = array();
        $condition['active_id'] = $params['active_id'];
        $condition['item_id'] = $params['item_id'];
        if (intval($params['user_id']) > 0) {
            $condition['user_id'] = $params['user_id'];
        }
        if (strtotime($params['start_time']) > 0) {
            //起始时间
            $condition['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0) {
            //结束时间
            $condition['end_time'] = $params['end_time'];
        }
        
        $result = $this->activeModel->getActiveSubjectByItem($condition, $offset, $limit);
        return $this->succ($result);
    }
    
    /**
     * 查询消消乐活动商品帖子情况
     */
    public function getXiaoxiaoleItem($params, $page = 1, $limit = false) {
        $result = array();
        $item_tab_params = ['item_tab' => $params['item_tab'], 'is_pre_set' => $params['is_pre_set']];
        $item_tabs = $this->activeModel->getActiveTabItemInfos($params['active_id'], $item_tab_params, false);
        if (empty($item_tabs)) {
            return $this->succ($result);
        }
        $item_ids = [];
        foreach ($item_tabs as $v) {
            $item_ids[] = $v['item_id'];
        }
        //获取商品帖子数、口碑数
        $itemSubjectCounts = $this->activeModel->getXiaoxiaoleItemSubjectCount($params);
        $item_service = new \mia\miagroup\Service\Item();
        $items = $item_service->getBatchItemBrandByIds($item_ids, false, [])['data'];
        foreach ($item_tabs as $v) {
            $v['subject_count'] = intval($itemSubjectCounts[$v['item_id']]['subject_count']);
            $v['koubei_count'] = intval($itemSubjectCounts[$v['item_id']]['koubei_count']);
            $v['item_info'] = !empty($items[$v['item_id']]) ? $items[$v['item_id']] : [];
            $result[] = $v;
        }
        return $this->succ($result);
    }
    
    /**
     * 查询消消乐所有活动商品
     */
    public function getAllXiaoxiaoleItems($active_id) {
        $result = array();
        if (intval($active_id) <= 0) {
            return $this->succ($result);
        }
        $item_tabs = $this->activeModel->getActiveTabItemInfos($active_id, ['status' => 1]);
        $item_ids = [];
        if (empty($item_tabs)) {
            return $this->succ($result);
        }
        foreach ($item_tabs as $v) {
            $item_ids[] = $v['item_id'];
        }
        $item_service = new \mia\miagroup\Service\Item();
        $items = $item_service->getBatchItemBrandByIds($item_ids, false, [])['data'];
        foreach ($item_tabs as $v) {
            $v['item_info'] = !empty($items[$v['item_id']]) ? $items[$v['item_id']] : [];
            $result[] = $v;
        }
        return $this->succ($result);
    }
}