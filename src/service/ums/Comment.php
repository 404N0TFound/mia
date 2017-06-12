<?php
namespace mia\miagroup\Service\Ums;

use \F_Ice;
use mia\miagroup\Model\Ums\Comment as CommentModel;
use mia\miagroup\Model\Ums\User as UserModel;
use mia\miagroup\Service\Comment as CommentService;

class Comment extends \mia\miagroup\Lib\Service {
    
    public $commentModel;
    public $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->commentModel = new CommentModel();
        $this->userModel = new UserModel();
    }
    
    /**
     * 获取评论列表
     */
    public function getCommentList($params) {
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
        if (intval($params['id']) > 0) {
            //评论ID
            $condition['id'] = $params['id'];
        }
        if (intval($params['supplier_id']) > 0 && intval($condition['id']) <= 0) {
            //供应商id
            $itemService = new \mia\miagroup\Service\Item();
            $userInfo = $itemService->getMappingBySupplierId($params['supplier_id'])['data'];
            $condition['user_id'] = $userInfo['user_id'];
        }
        if (intval($params['user_id']) > 0 && intval($condition['id']) <= 0) {
            //用户id
            $condition['user_id'] = $params['user_id'];
        }
        if (!empty($params['user_name']) && intval($condition['user_id']) <= 0 && intval($condition['id']) <= 0) {
            //用户名
            $condition['user_id'] = intval($this->userModel->getUidByUserName($params['user_name']));
        }
        if (!empty($params['nick_name']) && intval($condition['user_id']) <= 0 && intval($condition['id']) <= 0) {
            //用户昵称
            $condition['user_id'] = intval($this->userModel->getUidByNickName($params['nick_name']));
        }
        if (intval($params['subject_id']) > 0 && intval($condition['id']) <= 0) {
            //帖子id
            $condition['subject_id'] = $params['subject_id'];
        }
        if ($params['status'] !== null && $params['status'] !== '' && in_array($params['status'], array(0, 1, -1)) && intval($condition['id']) <= 0) {
            //评论状态
            $condition['status'] = $params['status'];
        }
        if ($params['is_expert'] !== null && $params['is_expert'] !== '' && in_array($params['is_expert'], array(0, 1)) && intval($condition['id']) <= 0) {
            //评论状态
            $condition['is_expert'] = $params['is_expert'];
        }
        if (strtotime($params['start_time']) > 0 && intval($condition['id']) <= 0) {
            //起始时间
            $condition['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0 && intval($condition['id']) <= 0) {
            //结束时间
            $condition['end_time'] = $params['end_time'];
        }
        $data = $this->commentModel->getCommentList($condition, $offset, $limit, $orderBy);
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $commentIds = array();
        foreach ($data['list'] as $v) {
            if(empty($v['id'])){
                continue;
            }
            $commentIds[] = $v['id'];
        }
        $commentService = new CommentService();
        //获取评论信息
        $commentInfos = $commentService->getBatchComments($commentIds, array('user_info', 'parent_comment'), array())['data'];
        //获取评论申诉信息
        $koubeiModel = new \mia\miagroup\Model\Ums\Koubei();
        $koubeiAppealInfos = $koubeiModel->getKoubeiAppealData(array('koubei_comment_id' => $commentIds), 0, false)['list'];
        $appealStatus = array();
        if (!empty($koubeiAppealInfos)) {
            foreach ($koubeiAppealInfos as $appeal) {
                $appealStatus[$appeal['koubei_comment_id']] = array('appeal_id' => $appeal['id'], 'status' => $appeal['status']);
            }
        }
        foreach ($data['list'] as $v) {
            $tmp = $commentInfos[$v['id']];
            if (isset($appealStatus[$v['id']])) {
                $tmp['appeal_id'] = $appealStatus[$v['id']]['appeal_id'];
                $tmp['appeal_status'] = $appealStatus[$v['id']]['status'];
            }
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }
    
    /**
     * 商家口碑回复列表
     */
    public function getSupplierReplyList($params) {
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
        if (intval($params['koubei_id']) > 0) {
            //口碑ID
            $condition['koubei_id'] = $params['koubei_id'];
        }
        if (intval($params['supplier_id']) > 0 && intval($condition['koubei_id']) <= 0) {
            //供应商id
            $itemService = new \mia\miagroup\Service\Item();
            $userInfo = $itemService->getMappingBySupplierId($params['supplier_id'])['data'];
            $condition['user_id'] = $userInfo['user_id'];
        }
        if (!empty($params['nick_name']) && intval($condition['user_id']) <= 0 && intval($condition['koubei_id']) <= 0) {
            //用户昵称
            $condition['user_id'] = intval($this->userModel->getUidByNickName($params['nick_name']));
        }
        if ($params['supplier_type'] !== null && $params['supplier_type'] !== '' && in_array($params['supplier_type'], array('客服', '商家')) && intval($condition['user_id']) <= 0 && intval($condition['koubei_id']) <= 0) {
            //回复者类型
            if ($params['supplier_type'] == '客服') {
                $condition['user_id'] = F_Ice::$ins->workApp->config->get('busconf.user.miaTuUid');
            } else {
                $condition['user_id'] = $this->userModel->getAllKoubeiSupplier();
            }
        }
        if (intval($params['item_id']) > 0 && intval($condition['koubei_id']) <= 0) {
            //商品ID
            $condition['item_id'] = $params['item_id'];
        }
        if (strtotime($params['koubei_start_time']) > 0 && intval($condition['koubei_id']) <= 0) {
            //起始时间
            $condition['koubei_start_time'] = $params['koubei_start_time'];
        }
        if (strtotime($params['koubei_end_time']) > 0 && intval($condition['koubei_id']) <= 0) {
            //结束时间
            $condition['koubei_end_time'] = $params['koubei_end_time'];
        }
        if (empty($condition['user_id'])) { //只查商家
            $condition['user_id'] = $this->userModel->getAllKoubeiSupplier();
            $condition['user_id'][] = F_Ice::$ins->workApp->config->get('busconf.user.miaTuUid');
        }
        $data = $this->commentModel->getKoubeiCommentList($condition, $offset, $limit, $orderBy);
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $commentIds = array();
        $koubeiIds = array();
        foreach ($data['list'] as $v) {
            if(empty($v['id'])){
                continue;
            }
            $commentIds[] = $v['id'];
            $koubeiIds[] = $v['koubei_id'];
        }
        $commentService = new CommentService();
        $koubeiService = new \mia\miagroup\Service\Koubei();
        //获取评论信息
        $commentInfos = $commentService->getBatchComments($commentIds, array('user_info'), array())['data'];
        $koubeiInfos = $koubeiService->getBatchKoubeiByIds($koubeiIds)['data'];
        foreach ($data['list'] as $v) {
            $tmp = $commentInfos[$v['id']];
            $tmp['koubei'] = $koubeiInfos[$v['koubei_id']];
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }
    
    /**
     * 获取用户被评论数
     */
    public function getCommentCount($params) {
        $result = array();
        $condition = array();
        //初始化入参
        if (empty($params['user_id'])) {
            return $result;
        }
        $condition['subject_uid'] = $params['user_id'];
    
        if (isset($params['status'])) {
            //评论状态
            $condition['status'] = $params['status'];
        }
        if (strtotime($params['start_time']) > 0) {
            //起始时间
            $condition['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0) {
            //结束时间
            $condition['end_time'] = $params['end_time'];
        }
        $userComments = $this->commentModel->getCommentCount($condition);
        if(!empty($userComments)){
            foreach($userComments as $userComment){
                $result[$userComment['subject_uid']] = $userComment['nums'];
            }
        }
    
        return $this->succ($result);
    }
}