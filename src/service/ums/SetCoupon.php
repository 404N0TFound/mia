<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\SetCoupon as SetCouponModel;

class SetCoupon extends \mia\miagroup\Lib\Service {

    private $setCouponModel;

    public function __construct() {
        parent::__construct();
        $this->setCouponModel = new SetCouponModel();
    }

    /*
     * 代金券奖励规则列表
     * */
    public function getCouponRule($page=1, $count=1, $condition = array())
    {
        $offset = intval($page) > 1 ? (intval($page) - 1) * $count : 0;
        $coupons = $this->setCouponModel->getCouponInfo($count, $offset, $condition);
        return $this->succ($coupons);
    }

    /*
     * 代金券规则添加
     * */
    public function add($data = array())
    {
        if(empty($data)) {
            return $this->succ(0);
        }
        $res = $this->setCouponModel->addCoupon($data);
        if(empty($res)) {
            return $this->succ(0);
        }
        return $this->succ(1);
    }

    /*
     * 代金券规则删除
     * */
    public function delete($id = 0)
    {
        if(empty($id)) {
            return $this->succ(0);
        }
        $res = $this->setCouponModel->deleteCoupon($id);
        if(empty($res)) {
            return $this->succ(0);
        }
        return $this->succ(1);
    }
}