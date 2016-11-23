<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Comment as CommentModel;
use mia\miagroup\Model\Ums\User as UserModel;
use mia\miagroup\Service\Comment as CommentService;
use mia\miagroup\Service\Item as ItemService;

class Comment extends \mia\miagroup\Lib\Service {
    
    public $commentModel;
    public $userModel;
    
    public function __construct() {
        $this->commentModel = new CommentModel();
        $this->userModel = new UserModel();
    }
    
    public function getCommentList($params) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        $koubeiCondtion = array();
        //初始化入参
        $orderBy = 'id desc'; //默认排序
        $limit = intval($params['limit']) > 0 && intval($params['limit']) < 100 ? $params['limit'] : 20;
        $offset = intval($params['page']) > 1 ? ($params['page'] - 1) * $limit : 0;
        if (intval($params['id']) > 0) {
            //评论ID
            $condition['id'] = $params['id'];
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
            $condition['start_time'] = $koubeiCondtion['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0 && intval($condition['id']) <= 0) {
            //结束时间
            $condition['end_time'] = $koubeiCondtion['end_time'] = $params['end_time'];
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
        $commentInfos = $commentService->getBatchComments($commentIds, array('user_info', 'parent_comment'), array())['data'];
        foreach ($data['list'] as $v) {
            $tmp = $commentInfos[$v['id']];
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }
    
        
}