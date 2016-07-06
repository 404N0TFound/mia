<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Redbag as RedbagModel;

class Redbag extends \FS_Service {

    public $redbagModel;

    public function __construct() {
        $this->redbagModel = new RedbagModel();
    }

    /**
     * 领取个人红包
     */
    public function getPersonalRedBag($userId, $redBagId) {
        if (empty($userId) || empty($redBagId)) {
            return $this->error(500);
        }
        
        // 获取红包信息
        $redbaginfo = $this->redbagModel->getRedbagBaseInfoById($redBagId);
        
        // 有效期判断
        // 顺延后的截止日期
        $expireTime = 0;
        if($redbaginfo['receive_delay_day'] != 0){
            $expireTime = $redbaginfo['cretaetime'] + 86400 * $redbaginfo['receive_delay_day'];
        }
        
        // 如果指定日期的截止日期小于当前日期或者顺延后的截止日期小于当前日期，不能领取
        if (($redbaginfo['receive_time'] != 0 && $redbaginfo['receive_time'] < time()) || ($expireTime > 0 && $expireTime < time())) {
            return $this->error(1724);
        }
        
        // 如果总额度没有限制，则直接按照红包最大最小金额随机生成红包金额
        if ($redbaginfo['all_money'] == -1) {
            $redBagPrice = rand($redbaginfo['max_money'], $redbaginfo['min_money']);
        } else {
            // 从redis里获取可用红包
            $redBagPrice = $this->redbagModel->getRedBagFromRedis($redBagId);
            if (!$redBagPrice) { //红包已被领完
                return $this->error(1725);
            }
        }
        
        // 记录本次领取操作
        $redbagData = array();
        $redbagData['redbag_id'] = $redbaginfo['redbag_id'];
        $redbagData['apply_id'] = $redbaginfo['id'];
        $redbagData['money'] = $redBagPrice;
        $redbagData['uid'] = $userId;
        $redbagData['create_time'] = time();
        $redbagDetailResult = $this->redbagModel->addRedbagDetailInfo($redbagData);
        
        // 红包入账
        if ($redbaginfo['use_time'] != 0) {
            // 指定日期
            $redbagData['use_starttime'] = $redbaginfo['use_time'];
            $redbagData['use_endtime'] = $redbaginfo['use_endtime'];
        } else {
            // 顺延日期
            $redbagData['use_starttime'] = time();
            $redbagData['use_endtime'] = time() + 86400 * $redbaginfo['use_delay_day'];
        }
        
        $redbagData['platform_id'] = 1;
        $redbagMeResult = $this->redbagModel->addRedbagInfoToMe($redbagData);
        
        return $this->succ($redBagPrice);
    }

    /**
     * 拆分红包
     */
    public function splitRedBag($redBagId) {
        if (empty($redBagId)) {
            return $this->error(500);
        }
        $splitRes = $this->redbagModel->splitRedBag($redBagId);
        return $this->succ($splitRes);
    }

    /**
     * 获取红包剩余数量
     */
    public function getRedbagNums($redBagId) {
        if (empty($redBagId)) {
            return $this->error(500);
        }
        $redbagNums = $this->redbagModel->getRedbagNumsFromRedis($redBagId);
        if (!$redbagNums || empty($redbagNums)) {
            $redbagNums = 0;
        }
        return $this->succ($redbagNums);
    }

    /**
     * 查看用户是否已经领取过该红包
     */
    public function isReceivedRedbag($redBagId, $uid) {
        if (empty($redBagId)) {
            return $this->error(500);
        }
        $isReceived = $this->redbagModel->isReceivedRedbag($redBagId, $uid);
        return $this->succ($isReceived);
    }
    
    /**
     * 重置红包（慎用！会导致红包超发！）
     */
    public function resetRedBag($redBagId) {
        if (empty($redBagId)) {
            return $this->error(500);
        }
        $this->redbagModel->resetRedBag($redBagId);
        return $this->succ();
    }
}