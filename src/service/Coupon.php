<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Coupon as CouponModel;
use \mia\miagroup\Remote\Coupon as CouponRemote;

class Coupon extends \mia\miagroup\Lib\Service {

    public $couponModel;
    public $couponRemote;

    public function __construct() {
        parent::__construct();
        $this->couponModel = new CouponModel();
        $this->couponRemote = new CouponRemote();
    }

    /**
     * 根据批次绑定代金券
     * @param int type 类型【签到,神舟,积分兑换,】
     * @param int user_id 用户id
     * @param stringt batch_code 代金券批次编号
     * @return array|bool array(array('batch_codexxx'=>'error inf'))
     */
    public function bindCoupon($type, $userId, $batchCode) {
        if (empty($userId) || empty($batchCode)) {
            return $this->error(500);
        }
        
        $bindRes = $this->couponRemote = bindCouponByBatchCode($type, $userId, $batchCode);
        return $this->succ($bindRes);
    }


    /**
     * 获取代金券批次剩余数量
     * @param string $batchCode 代金券批次号
     * @return array|null
     */
    public function getCouponRemainNums($batchCode) {
        if (empty($batchCode)) {
            return $this->error(500);
        }
        $couponNums = $this->couponRemote->getRemainCoupon([$batchCode]);
        return $this->succ($couponNums);
    }
    
    /**
     * 获取个人绑定代金券列表
     * @param int $user_id
     * @param string $batch_codes
     * @param int $page_no
     * @param int $page_size
     * @return array|null
     */
    public function getPersonalCoupons($user_id, $batch_codes, $page_no, $page_size) {
        if (empty($batchCode) || empty($user_id)) {
            return $this->error(500);
        }
        $couponLists = $this->couponRemote->queryUserCouponByBatchCode($user_id, $batch_codes, $page_no, $page_size);
        return $this->succ($couponLists);
    }

    /**
     * 查看用户是否已经领取过该代金券，用于领取代金券
     */
    public function isReceivedCoupon($couponId, $uid) {
        if (empty($couponId)) {
            return $this->error(500);
        }
        //查出个人领取代金券的详情，如果存在则领取过
    }
    
    
    /**
     * 检验代金券批次号是否可用
     * @param array $batchCode
     * @return int 0为代金券可用，其他返回错误码则为不可用
     */
    public function checkBatchCodeAvailable($batchCode){
        //获取批次详情，再根据详情里的数据，判断批次的有效性
        //或者直接调用判断批次有效性的服务
    }
    
}