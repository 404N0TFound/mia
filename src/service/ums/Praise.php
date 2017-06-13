<?php
namespace mia\miagroup\Service\Ums;

use \F_Ice;
use mia\miagroup\Model\Ums\Praise as PraiseModel;

class Praise extends \mia\miagroup\Lib\Service {
    
    public $praiseModel;
    
    public function __construct() {
        parent::__construct();
        $this->praiseModel = new PraiseModel();
    }
    
    /**
     * 获取用户被点赞数
     */
    public function getPraiseCount($params) {
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
        $userPraises = $this->praiseModel->getPraiseCount($condition);
        if(!empty($userPraises)){
            foreach($userPraises as $userPraise){
                $result[$userPraise['subject_uid']] = $userPraise['nums'];
            }
        }
        
        return $this->succ($result);
    }
    
}