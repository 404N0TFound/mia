<?php
namespace mia\miagroup\Model;

use \F_Ice;
use mia\miagroup\Data\Redbag\Redbagme;
use mia\miagroup\Data\Redbag\Redbagtadetail;
use mia\miagroup\Data\Redbag\Baseinfo;
use mia\miagroup\Lib\Redis;

class Redbag {

    private $redbagmeData;

    private $rebagtadetailData;

    private $baseinfoData;

    public function __construct() {
        $this->redbagmeData = new Redbagme();
        $this->rebagtadetailData = new Redbagtadetail();
        $this->baseinfoData = new Baseinfo();
    }

    /**
     * 拆分红包
     */
    public function splitRedBag($redBagId) {
        // 判断是否已拆分
        $splitStatusKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.redBagKey.splitStatus.key'), $redBagId);
        $redis = new Redis();
        $splitStatus = $redis->exists($splitStatusKey);
        if ($splitStatus) {
            return false;
        }
        // 获取红包基础信息
        $redbagData = $this->getRedbagBaseInfoById($redBagId);
        if (empty($redbagData)) {
            return false;
        }
        //如果红包总金额没有限制，则无需拆包
        if($redbagData['all_money']  == -1){
            return false;
        }
        // 根据红包规则，拆分红包
        $splitArr = $this->spliteRedBagByRule($redbagData['all_money'], $redbagData['max_money'], $redbagData['min_money']);
        // 拆分完成的红包写入redis
        if (!empty($splitArr)) {
            $this->setSplitedRedBag($redBagId, $splitArr);
        }
        // 设置已拆分状态
        $redis->setex($splitStatusKey, 1, \F_Ice::$ins->workApp->config->get('busconf.rediskey.redBagKey.splitStatus.expire_time'));
        return true;
    }

    /**
     * 领取红包from redis
     */
    public function getRedBagFromRedis($redBagId) {
        // 获取rediskey
        $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.redBagKey.splitRedBag.key'), $redBagId);
        
        // 执行redis指令
        $redis = new Redis();
        $redbag = $redis->lpop($key);
        
        return $redbag;
    }

    /**
     * 写入待领取的红包 redis
     */
    public function setSplitedRedBag($redBagId, $splitedList) {
        // 获取rediskey
        $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.redBagKey.splitRedBag.key'), $redBagId);
        $redis = new Redis();
        foreach ($splitedList as $redbag) {
            $redis->lpush($key, $redbag);
        }
        $redis->expire($key, sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.redBagKey.splitRedBag.expire_time')));
        return true;
    }

    /**
     * 按拆分红包规则拆分红包
     */
    public function spliteRedBagByRule($total, $max, $min) {
        $splitRedbag = array();
        $balance = $total;
        
        while ($balance > $min) {
            $avg = intval(rand($min, $max));
            if (($balance > $avg)) {
                $splitRedbag[] = $avg;
            } else {
                $splitRedbag[] = $balance;
            }
            $balance -= $avg;
        }
        return $splitRedbag;
    }

    /**
     * 获取红包基础信息
     */
    public function getRedbagBaseInfoById($redbagId) {
        $redbagData = $this->baseinfoData->getBatchRedbagByIds([$redbagId])[$redbagId];
        return $redbagData;
    }

    /**
     * 记录本次领取操作
     */
    public function addRedbagDetailInfo($redbagData) {
        $redbagData = $this->rebagtadetailData->addRedbagDetailInfo($redbagData);
        return $redbagData;
    }

    /**
     * 记录个人领取红包信息
     */
    public function addRedbagInfoToMe($redbagData) {
        $redbagData = $this->redbagmeData->addRedbagInfoToMe($redbagData);
        return $redbagData;
    }

    /**
     * 获取红包剩余数量 from redis
     */
    public function getRedbagNumsFromRedis($redBagId) {
        // 获取rediskey
        $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.redBagKey.splitRedBag.key'), $redBagId);
        // 执行redis指令
        $redis = new Redis();
        $nums = $redis->llen($key);
        return $nums;
    }

    /**
     * 判断用户是否领取过红包
     */
    public function isReceivedRedbag($redBagId, $uid) {
        $data = $this->rebagtadetailData->isReceivedRedbag($redBagId, $uid);
        return $data;
    }
    
    /**
     * 重置红包（慎用！会导致红包超发！）
     */
    public function resetRedBag($redBagId) {
        // 获取rediskey
        $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.redBagKey.splitRedBag.key'), $redBagId);
        // 执行redis指令
        $redis = new Redis();
        $redis->del($key);
        return true;
    }
}