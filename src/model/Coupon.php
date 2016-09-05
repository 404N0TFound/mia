<?php
namespace mia\miagroup\Model;

use \F_Ice;
use mia\miagroup\Lib\Redis;

class Coupon {
    /**
     * 发券后将批次号信息放入redis
     */
    public function setBatchCodeToRedis($BatchCode){
        $batchCodeKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.couponKey.sendCoupon.key'), $BatchCode);
        $redis = new Redis();
        $redis->setex($batchCodeKey, 1, \F_Ice::$ins->workApp->config->get('busconf.rediskey.couponKey.sendCoupon.expire_time'));
        return true;
    }
    /**
     * 检验券是否已发送（查看批次号是否存在于redis中）
     */
    public function getBatchCodeFromRedis($BatchCode) {
        $batchCodeKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.couponKey.sendStatus.key'), $BatchCode);
        $redis = new Redis();
        $batchCodeRes = $redis->exists($batchCodeKey);
        if(!$batchCodeRes){
            return false;
        }
        return  true;
    }
    

}