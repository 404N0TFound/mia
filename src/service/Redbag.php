<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Redbag;

class Live extends \FS_Service {
    
    public $redbagModel;
    
    public function __construct() {
        $this->redbagModel = new Redbag();
    }
    
    /**
     * 领取红包
     */
    public function getPersonalRedBag($userId, $redBagId) {
    }
    
    /**
     * 拆分红包
     */
    public function splitRedBag($redBagId) {
    }
}