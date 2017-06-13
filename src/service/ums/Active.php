<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Active as ActiveModel;

class Active extends \mia\miagroup\Lib\Service {

    public $activeModel;

    public function __construct() {
        parent::__construct();
        $this->activeModel = new ActiveModel();
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
}