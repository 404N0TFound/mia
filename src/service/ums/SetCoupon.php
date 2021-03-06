<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Remote\Coupon as CouponRemote;
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
        if(empty($data) || empty($data['image'])) {
            return $this->succ(0);
        }
        $params = array();
        if (!empty($data['brand_id'])) {
            $params['brand_id'] = intval($data['brand_id']);
        }
        if (!empty($data['category_id'])) {
            $params['category_id'] = intval($data['category_id']);
        }
        if (!empty($data['item_id'])) {
            $params['item_ids'] = trim($data['item_id']);
        }
        if (!empty($data['chat_count'])) {
            $params['chat_count'] = intval($data['chat_count']);
        }
        if (!empty($data['image_count'])) {
            $params['image_count'] = intval($data['image_count']);
        }
        if (!empty($data['intro'])) {
            $params['intro'] = trim($data['intro']);
        }
        if (!empty($data['prompt'])) {
            $params['prompt'] = trim($data['prompt']);
        }
        if (!empty($data['remarks'])) {
            $params['remarks'] = trim($data['remarks']);
        }
        if (!empty($data['image'])) {
            $params['image'] = trim($data['image']);
        }

        if (!empty($data['coupon_code'])) {
            $coupon_code = trim($data['coupon_code']);
        }

        $coupon_price = 0;

        // 获取代金券的批次信息
        if(!empty($coupon_code)) {
            $couponRemote = new CouponRemote();
            $coupon_info = $couponRemote->getBatchCodeList([$coupon_code]);
            if(!empty($coupon_info)) {
                $coupon_price = $coupon_info[$coupon_code]['value'];
                if(!empty($coupon_info[$coupon_code]['startTimestamp'])) {
                    $params['start_time'] = $coupon_info[$coupon_code]['startTimestamp'];
                }
                if(!empty($coupon_info[$coupon_code]['expireTimestamp'])) {
                    $params['end_time'] = $coupon_info[$coupon_code]['expireTimestamp'];
                }
            }
        }
        $params['coupon_money'] = $coupon_price;
        $params['coupon_code'] = $coupon_code;

        $res = $this->setCouponModel->addCoupon($params);
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