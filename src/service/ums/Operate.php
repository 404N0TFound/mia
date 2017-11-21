<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Remote\MiBean as miBeanRemote;
use mia\miagroup\Model\Ums\OperateIssueRecord as OperateIssueModel;

class Operate extends \mia\miagroup\Lib\Service {

    private  $beanRemote;

    public function __construct() {
        parent::__construct();
        $this->beanRemote = new miBeanRemote();
    }

    /*
     * 蜜芽圈奖励下发列表
     * */
    public function getOperIssueList($params)
    {
        $conditions = array();
        if(empty($params)) {
            return $this->succ([]);
        }
        // 查询用户
        $user_id = $op_admin = 0;
        if(!empty($params['user_id'])) {
            $user_id = $params['user_id'];
        }
        // 下发操作人
        if(!empty($params['op_admin'])) {
            $op_admin = $params['op_admin'];
        }
        // 查询状态
        if(!empty($params['issue_type'])) {
            $conditions['issue_type'] = $params['issue_type'];
        }
        // 查询类型
        if(!empty($params['type'])) {
            $conditions['type'] = $params['type'];
        }
        // 开始时间
        if(!empty($params['start_time'])) {
            $conditions['start_time'] = $params['start_time'];
        }
        // 结束时间
        if(!empty($params['end_time'])) {
            $conditions['end_time'] = $params['end_time'];
        }

        $page = $params['page'] ? : 1;
        $limit = $params['limit'] ? : 50;

        $offset = $page > 1 ? ($page -1) * $limit : 0;
        $operateIssueModel = new OperateIssueModel();
        $res = $operateIssueModel->getOperIssueList($user_id, $op_admin, $limit, $offset, $conditions);
        if(empty($res['list']) || empty($res['total'])) {
            return $this->succ([]);
        }
        $userIds = array_column($res['list'], 'user_id');
        $userService = new \mia\miagroup\Service\User();
        $userInfos = $userService->getUserInfoByUids($userIds, 0, ['cell_phone'])['data'];
        foreach($res['list'] as $k => $oper) {
            if(!empty($oper['user_id'])) {
                $res['list'][$k]['user_info'] = $userInfos[$oper['user_id']];
            }
        }
        return $this->succ($res);
    }

    /*
     * 下发蜜豆
     * op_admin:下发操作人
     * type:下发类型（add,sub）
     * */
    public function setOperMibean($params, $op_admin = '', $type = 'add')
    {
        $return = ['status' => false];
        if(empty($params)) {
            return $this->succ($return);
        }
        $userList = $params['issue_list'];
        if(empty($userList)) {
            return $this->succ($return);
        }
        // 默认奖励类型：下发
        $issue_type = 1;
        if($type == 'sub') {
            $issue_type = 2;
        }
        $operateIssueModel = new OperateIssueModel();
        foreach($userList as $info) {
            $param = [];
            $user_id = trim($info['user_id']);
            $count = intval($info['count']);
            if(empty($user_id) || empty($count)) {
                continue;
            }
            // 蜜芽兔官方账号下发
            $param['user_id'] = 3782852;
            $param['relation_type'] = "group_operator";
            $param['to_user_id'] = $user_id;
            $param['relation_id'] = $user_id;
            $param['mibean'] = $count;
            if($type == 'sub') {
                $param['mibean'] = '-'.$count;
            }
            $res = $this->beanRemote->agent($type, $param);
            if($res['code'] != 200) {
                // 重发
                $this->beanRemote->agent($type, $param);
            }
            if($res['code'] == 200) {
                $group_issue_info['type'] = 1;
                $group_issue_info['issue_type'] = $issue_type;
                $group_issue_info['op_admin'] = $op_admin;
                $group_issue_info['user_id'] = $user_id;
                $group_issue_info['mibean'] = $param['mibean'];
                $group_issue_info['create_time'] = date('Y-m-d H:i:s', time());
                $result = $operateIssueModel->addGroupIssue($group_issue_info);
            }
        }
        if(!empty($result)) {
            $return['status'] = true;
        }
        return $this->succ($return);
    }

    /*
     * 下发代金券
     * */
    public function setOperCoupon($params, $op_admin = '', $coupon_code = '', $type = 'add')
    {
        $return = ['status' => false];
        if(empty($params) || empty($coupon_code)) {
            return $this->succ($return);
        }
        $userList = $params['issue_list'];
        if(empty($userList)) {
            return $this->succ($return);
        }
        // 默认奖励类型：下发
        $issue_type = 1;
        if($type == 'sub') {
            $issue_type = 2;
        }
        $couponRemote = new \mia\miagroup\Remote\Coupon();
        $operateIssueModel = new OperateIssueModel();
        foreach($userList as $user_id) {
            $user_id = trim($user_id['user_id']);
            if(empty($user_id)) {
                continue;
            }
            $bindCouponRes = $couponRemote->bindCouponByBatchCode($user_id, $coupon_code);
            if ($bindCouponRes !== true) {
                // 重发
                $bindCouponRes = $couponRemote->bindCouponByBatchCode($user_id, $coupon_code);
            }
            if($bindCouponRes === true) {
                $group_issue_info['type'] = 2;
                $group_issue_info['issue_type'] = $issue_type;
                $group_issue_info['op_admin'] = $op_admin;
                $group_issue_info['user_id'] = $user_id;
                $group_issue_info['coupon_code'] = $coupon_code;
                $group_issue_info['create_time'] = date('Y-m-d H:i:s', time());
                $result = $operateIssueModel->addGroupIssue($group_issue_info);
            }
        }
        if(!empty($result)) {
            $return['status'] = true;
        }
        return $this->succ($return);
    }
}