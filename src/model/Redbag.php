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
     * 领取个人红包
     */
     public function getPersonalRedBag($userId, $redBagId) {
         //从redis里获取可用红包
         //记录本次领取操作
         //红包入账
     }
    
    /**
     * 拆分红包
     */
     public function splitRedBag($redBagId) {
         //获取红包信息
         //根据红包规则，拆分红包
         //拆分完成的红包写入redis
     }
     
     /**
      * 领取红包from redis
      */
     public function getRedBagFromRedis($redBagId) {
         
     }
     
     /**
      * 写入待领取的红包 redis
      */
     public function setSplitedRedBag($redBagId, $splitedList) {
         
     }
}