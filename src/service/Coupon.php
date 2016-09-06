<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Coupon as CouponModel;
use mia\miagroup\Remote\Coupon as CouponRemote;

class Coupon extends \mia\miagroup\Lib\Service {

    public $couponModel;
    public $couponRemote;
    private $version;

    public function __construct($version='android_4_7_0') {
        parent::__construct();
        $this->version = $this->ext_params['version'] ? $this->ext_params['version'] : $version;
        $this->couponModel = new CouponModel();
        $this->couponRemote = new CouponRemote($this->version);
    }

    /**
     * 根据批次绑定代金券
     * @param int type 类型【签到,神舟,积分兑换,】
     * @param int $userId 用户id
     * @param stringt $batchCode 代金券批次编号
     */
    public function bindCoupon($userId, $batchCode) {
        if (empty($userId) || empty($batchCode)) {
            return $this->error(500);
        }
        $bindRes = $this->couponRemote->bindCouponByBatchCode($userId, $batchCode);
        if($bindRes != true){
            return $this->error(1633);
        }
        return $this->succ($bindRes);
    }


    /**
     * 获取代金券批次剩余数量
     * @param string $batchCode 代金券批次号
     */
    public function getCouponRemainNums($batchCode) {
        if (empty($batchCode)) {
            return $this->error(500);
        }
        $couponNums = $this->couponRemote->getRemainCoupon([$batchCode]);
        if(!$couponNums){
            return $this->error(1635);
        }
        return $this->succ($couponNums);
    }
    
    /**
     * 获取个人绑定代金券列表
     * @param int $userId
     * @param string $batchCode
     * @param int $page
     * @param int $limit
     */
    public function getPersonalCoupons($userId, $batchCode, $page=1, $limit=1) {
        if (empty($batchCode) || empty($userId)) {
            return $this->error(500);
        }
        $couponLists = $this->couponRemote->queryUserCouponByBatchCode($userId, [$batchCode], $page, $limit);
        if(!$couponLists){
            return $this->error(1637);
        }
        return $this->succ($couponLists);
    }
    
    /**
     * 检查优惠券是否已领取过
     * @param string $batchCode
     * @param int $userId
     */
    public function checkIsReceivedCoupon($userId, $batchCode){
        if (empty($batchCode) || empty($userId)) {
            return $this->error(500);
        }
        $couponInfo = $this->getPersonalCoupons($userId, $batchCode);
        if($couponInfo['code'] > 0 || $couponInfo['data']['total_count']>0){
            return $this->error(1631);
        }else{
            return $this->succ(0);
        }
        
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
    
    /**
     * 检验代金券批次号是否发发送过
     */
    public function checkBatchCodeIsSended($batchCode){
        
    }
    
    /**
     * 代金券发送成功后将批次号放入redis，用来验证是否发送过用
     */
    public function setBatchCodeToRedis ($batchCode){
        
    }
    
    
}