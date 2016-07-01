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
    	//从redis里获取可用红包
         $redBagPrice = $this->redbagModel->getRedBagFromRedis($redBagId);
         //获取红包信息
         $redbaginfo = $this->redbagModel->getRedbagBaseInfoById($redBagId);
         //记录本次领取操作start
         $redbagData = array();
         $redbagData['apply_id'] = $redbaginfo['redbag_id'];
         $redbagData['money'] = $redBagPrice;
         $redbagData['uid'] = $userId;
         $redbagData['create_time'] = time();
         $redbagDetailResult = $this->redbagModel->addRedbagDetailInfo($redbagData);
         
         //记录本次领取操作end
         //红包入账start
         //有效期判断
         if($baseInfo['receive_time']!=0)
         {
         	//指定日期
         	$redbagData['use_starttime']=time();
         	$redbagData['use_endtime']= $baseInfo['receive_time'];
         }else{
         	//顺延日期
         	$redbagData['use_starttime']=time();
         	$redbagData['use_endtime']= time()+86400*$baseInfo['receive_delay_day'];
         }
         
         $redbagData['platform_id'] = 1;
         $redbagMeResult = $this->redbagModel->addRedbagInfoToMe($redbagData);
         //红包入账end
         
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
    public function getRedbagNums($redBagId){
    	if (empty($redBagId)) {
    		return $this->error(500);
    	}
    	$redbagNums = $this->redbagModel->getRedbagNumsFromRedis($redBagId);
    	if(!$redbagNums || empty ($redbagNums)){
    		$redbagNums = 0;
    	}
    	return $this->succ($redbagNums);
    }
    
    /**
     * 查看用户是否已经领取过该红包
     */
    public function isReceivedRedbag($redBagId,$uid){
    	if (empty($redBagId)) {
    		return $this->error(500);
    	}
    	$isReceived = $this->redbagModel->isReceivedRedbag($redBagId,$uid);
    	return $this->succ($isReceived);
    }
}