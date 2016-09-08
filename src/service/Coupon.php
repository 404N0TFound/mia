<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Coupon as CouponModel;
use mia\miagroup\Remote\Coupon as CouponRemote;
use mia\miagroup\Service\Live;

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
        if($bindRes === true){
            return $this->succ($bindRes);
        }else{
            return $this->error(1633);
        }
    }


    /**
     * 获取代金券批次剩余数量
     * @param array $batchCodes 代金券批次号
     */
    public function getCouponRemainNums($batchCodes) {
        if (empty($batchCodes)) {
            return $this->error(500);
        }
        $couponNums = $this->couponRemote->getRemainCoupon($batchCodes);
        if(!$couponNums){
            return $this->error(1635);
        }
        return $this->succ($couponNums);
    }
    
    /**
     * 获取代金券批次
     * @param array $batchCodes 代金券批次号
     */
    public function getBatchCodeList($batchCodes) {
        if (empty($batchCodes)) {
            return $this->error(500);
        }
        $batchCodeList = $this->couponRemote->getBatchCodeList($batchCodes);
        if(!$batchCodeList){
            return $this->error(1637);
        }
        return $this->succ($batchCodeList);
    }
    
    /**
     * 检查优惠券是否已过期
     * @param array $batchCodes
     */
    public function checkBatchCodeIsExpired($batchCodes){
        if (empty($batchCodes)) {
            return $this->error(500);
        }
        $batchCodeInfo = $this->getBatchCodeList($batchCodes);
        //如果批次不存在，给出不存在提示
        if($batchCodeInfo['code'] != 0){
            return $this->error($batchCodeInfo['code']);
        }
        
        //当前时间大于过期时间，给出过期提示
        $currentTime = date('Y-m-d H:i:s',time());
        if($currentTime > $batchCodeInfo['data'][$batchCodes[0]]['expire_timestamp']){
            return $this->error(1638);
        }
        return $this->succ(true);
    }
    
    /**
     * 获取个人绑定代金券列表
     * @param int $userId
     * @param array $batchCodes
     * @param int $page
     * @param int $limit
     */
    public function getPersonalCoupons($userId, $batchCodes, $page=1, $limit=1) {
        if (empty($batchCodes) || empty($userId)) {
            return $this->error(500);
        }
        $couponLists = $this->couponRemote->queryUserCouponByBatchCode($userId, $batchCodes, $page, $limit);
        if(!$couponLists){
            return $this->error(1632);
        }
        return $this->succ($couponLists);
    }
    
    /**
     * 检查优惠券是否已领取过
     * @param array $batchCodes
     * @param int $userId
     */
    public function checkIsReceivedCoupon($userId, $batchCodes){
        if (empty($batchCodes) || empty($userId)) {
            return $this->error(500);
        }
        $couponInfo = $this->getPersonalCoupons($userId, $batchCodes);
        if($couponInfo['code'] == 0){
            return $this->error(1631);
        }
        return $this->succ(true);
    }
    
    
    /**
     * 检验代金券批次号是否发发送过
     */
    public function checkBatchCodeIsSent($liveId){
        if (empty($liveId)) {
            return $this->error(500);
        }
        $couponStatus = $this->couponModel->checkBatchCodeIsSent($liveId);
        if($couponStatus == false){
            return $this->error(1636);
        }
        return $this->succ(true);
    }
    
    /**
     * 代金券发送成功后将批次号放入redis，用来验证是否发送过用
     */
    public function setBatchCodeToRedis ($liveId,$batchCode){
        if (empty($liveId) || empty($batchCode)) {
            return $this->error(500);
        }
        $couponRes = $this->couponModel->setBatchCodeToRedis($liveId,$batchCode);
        return $this->succ(true);
    }
    
    /**
     * 发送代金券的时候把发送时间保存到redis
     */
    public function addSendCouponSatrtTime($liveId,$sendTime)
    {
        if (empty($liveId) || empty($sendTime)) {
            return $this->error(500);
        }
        $data = $this->couponModel->addSendCouponSatrtTime($liveId,$sendTime);
        return $this->succ($data);
    }

    /**
     * 获取发送代金券的发送时间
     */
    public function getSendCouponStartTime($liveId)
    {
        if (empty($liveId)) {
            return $this->error(500);
        }
        $data = $this->couponModel->getSendCouponStartTime($liveId);
        return $this->succ($data);
    }
    
}