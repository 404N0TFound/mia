<?php
namespace mia\miagroup\Model;

use \F_Ice;
use mia\miagroup\Lib\Redis;

class Coupon {
    /**
     * 发券后将批次号信息放入redis
     */
    public function setBatchCodeToRedis($liveId,$batchCode){
        $batchCodeKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.couponKey.sendCoupon.key'), $liveId);
        $redis = new Redis();
        $redis->setex($batchCodeKey, $batchCode, \F_Ice::$ins->workApp->config->get('busconf.rediskey.couponKey.sendCoupon.expire_time'));
        return true;
    }
    /**
     * 检验券是否已发送（查看批次号是否存在于redis中）
     */
    public function checkBatchCodeIsSent($liveId) {
        $batchCodeKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.couponKey.sendCoupon.key'), $liveId);
        $redis = new Redis();
        $batchCodeRes = $redis->exists($batchCodeKey);
        if($batchCodeRes){
            return false;
        }
        return  true;
    }

    /**
     * 发送代金券的时候把发送时间保存到redis
     */
    public function addSendCouponSatrtTime($live_id,$sendTime)
    {
        $timeKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.couponKey.send_coupon_start_time.key'), $live_id);
        $redis = new Redis();
        $redis->setex($timeKey, $sendTime, \F_Ice::$ins->workApp->config->get('busconf.rediskey.couponKey.send_coupon_start_time.expire_time'));
        return true;
    }

    /**
     * 获取发送代金券的发送时间
     */
    public function getSendCouponStartTime($live_id)
    {
        $timeKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.couponKey.send_coupon_start_time.key'), $live_id);
        $redis = new Redis();
        $sendTime = $redis->get($timeKey);

        return $sendTime;
    }
    

}